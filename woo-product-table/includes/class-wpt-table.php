<?php
/**
 * Table renderer — handles the [woo_product_table] shortcode, asset enqueuing,
 * and HTML generation for the product table.
 *
 * @package WooProductTable
 */

defined( 'ABSPATH' ) || exit;

class WPT_Table {

    // ── Singleton ────────────────────────────────────────────────────────────────

    private static ?WPT_Table $instance = null;

    public static function instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    // ── Available columns definition ─────────────────────────────────────────────

    public static function available_columns(): array {
        return array(
            'image'       => __( 'Image',       'woo-product-table' ),
            'name'        => __( 'Name',        'woo-product-table' ),
            'price'       => __( 'Price',       'woo-product-table' ),
            'stock'       => __( 'Stock',       'woo-product-table' ),
            'quantity'    => __( 'Qty',         'woo-product-table' ),
            'add_to_cart' => __( 'Add to Cart', 'woo-product-table' ),
        );
    }

    // ── Hook registration ────────────────────────────────────────────────────────

    public function register_hooks(): void {
        add_shortcode( 'woo_product_table', array( $this, 'render_shortcode' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    // ── Asset enqueueing ─────────────────────────────────────────────────────────

    public function enqueue_assets(): void {
        // Only enqueue when a page/post uses the shortcode (or always — simpler).
        wp_register_style(
            'wpt-table',
            WPT_PLUGIN_URL . 'assets/css/wpt-table.css',
            array(),
            WPT_VERSION
        );

        wp_register_script(
            'wpt-table',
            WPT_PLUGIN_URL . 'assets/js/wpt-table.js',
            array( 'jquery' ),
            WPT_VERSION,
            true   // load in footer
        );

        wp_localize_script(
            'wpt-table',
            'wptData',
            array(
                'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
                'nonce'          => wp_create_nonce( 'wpt_ajax_nonce' ),
                'addingText'     => __( 'Adding…', 'woo-product-table' ),
                'addedText'      => __( 'Added!',  'woo-product-table' ),
                'addToCartText'  => __( 'Add to cart', 'woo-product-table' ),
                'errorText'      => __( 'Error. Please try again.', 'woo-product-table' ),
                'isPro'          => WPT_IS_PRO,
            )
        );
    }

    // ── Shortcode ────────────────────────────────────────────────────────────────

    /**
     * Render the [woo_product_table] shortcode.
     *
     * Accepted attributes:
     *   category  – slug (or comma-separated slugs) to pre-filter by category
     *   per_page  – override items-per-page from settings
     *   sort      – column to sort by: name|price|rating
     *   order     – ASC|DESC
     */
    public function render_shortcode( array $atts = array() ): string {
        $settings = $this->get_settings();

        $atts = shortcode_atts(
            array(
                'category' => '',
                'per_page' => $settings['per_page'],
                'sort'     => $settings['default_sort'],
                'order'    => $settings['default_order'],
            ),
            $atts,
            'woo_product_table'
        );

        // Sanitise.
        $per_page  = absint( $atts['per_page'] );
        $sort      = in_array( $atts['sort'], array( 'name', 'price', 'rating' ), true ) ? $atts['sort'] : 'name';
        $order     = in_array( strtoupper( $atts['order'] ), array( 'ASC', 'DESC' ), true ) ? strtoupper( $atts['order'] ) : 'ASC';
        $category  = sanitize_text_field( $atts['category'] );

        // Enqueue assets now that we know the shortcode is used.
        wp_enqueue_style( 'wpt-table' );
        wp_enqueue_script( 'wpt-table' );

        $products   = $this->query_products( $sort, $order, $per_page, $category );
        $columns    = $settings['visible_columns'];
        $categories = $this->get_categories();

        ob_start();
        $this->render_table_html( $products, $columns, $categories, $sort, $order, $per_page, $category );
        return ob_get_clean();
    }

    // ── Settings helper ──────────────────────────────────────────────────────────

    public function get_settings(): array {
        $defaults = array(
            'visible_columns' => array( 'image', 'name', 'price', 'stock', 'quantity', 'add_to_cart' ),
            'default_sort'    => 'name',
            'default_order'   => 'ASC',
            'per_page'        => 10,
        );
        $saved = get_option( 'wpt_settings', array() );
        return wp_parse_args( $saved, $defaults );
    }

    // ── Product query ────────────────────────────────────────────────────────────

    private function query_products(
        string $sort,
        string $order,
        int $per_page,
        string $category_slug = ''
    ): array {
        $map = array(
            'name'   => array( 'orderby' => 'title', 'order' => $order ),
            'price'  => array( 'orderby' => 'meta_value_num', 'order' => $order, 'meta_key' => '_price' ),
            'rating' => array( 'orderby' => 'meta_value_num', 'order' => $order, 'meta_key' => '_wc_average_rating' ),
        );

        $args = array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => $per_page > 0 ? $per_page : 10,
            'orderby'        => $map[ $sort ]['orderby'],
            'order'          => $map[ $sort ]['order'],
        );

        if ( ! empty( $map[ $sort ]['meta_key'] ) ) {
            $args['meta_key'] = $map[ $sort ]['meta_key'];
        }

        if ( ! empty( $category_slug ) ) {
            $slugs = array_map( 'trim', explode( ',', $category_slug ) );
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'product_cat',
                    'field'    => 'slug',
                    'terms'    => $slugs,
                ),
            );
        }

        $query = new WP_Query( $args );
        $products = array();

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $products[] = wc_get_product( get_the_ID() );
            }
        }
        wp_reset_postdata();

        return array_filter( $products ); // remove nulls
    }

    // ── Category list ────────────────────────────────────────────────────────────

    private function get_categories(): array {
        $terms = get_terms( array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => true,
        ) );

        if ( is_wp_error( $terms ) ) {
            return array();
        }

        $cats = array();
        foreach ( $terms as $term ) {
            $cats[] = array(
                'slug' => $term->slug,
                'name' => $term->name,
            );
        }
        return $cats;
    }

    // ── HTML rendering ───────────────────────────────────────────────────────────

    private function render_table_html(
        array $products,
        array $columns,
        array $categories,
        string $sort,
        string $order,
        int $per_page,
        string $active_category
    ): void {
        $settings = $this->get_settings();
        ?>
        <div class="wpt-wrapper"
             data-sort="<?php echo esc_attr( $sort ); ?>"
             data-order="<?php echo esc_attr( $order ); ?>"
             data-per-page="<?php echo esc_attr( $per_page ); ?>">

            <?php $this->render_filter_bar( $categories, $active_category ); ?>

            <?php if ( WPT_IS_PRO ) : ?>
                <div class="wpt-export-bar">
                    <button class="wpt-btn wpt-btn--export" id="wpt-export-csv">
                        <?php esc_html_e( 'Export CSV', 'woo-product-table' ); ?>
                    </button>
                </div>
            <?php else : ?>
                <div class="wpt-pro-notice">
                    <span class="wpt-pro-badge">PRO</span>
                    <?php esc_html_e( 'CSV export, custom columns &amp; pagination controls are available in', 'woo-product-table' ); ?>
                    <a href="https://example.com/woo-product-table-pro" target="_blank" rel="noopener noreferrer">
                        <?php esc_html_e( 'WooCommerce Product Table Pro', 'woo-product-table' ); ?>
                    </a>.
                </div>
            <?php endif; ?>

            <div class="wpt-table-wrap">
                <table class="wpt-table" role="grid" aria-label="<?php esc_attr_e( 'Product Table', 'woo-product-table' ); ?>">
                    <thead>
                        <tr>
                            <?php $this->render_thead( $columns, $sort, $order ); ?>
                        </tr>
                    </thead>
                    <tbody id="wpt-tbody">
                        <?php
                        if ( empty( $products ) ) {
                            $colspan = count( $columns );
                            printf(
                                '<tr><td colspan="%d" class="wpt-no-products">%s</td></tr>',
                                esc_attr( $colspan ),
                                esc_html__( 'No products found.', 'woo-product-table' )
                            );
                        } else {
                            foreach ( $products as $product ) {
                                $this->render_row( $product, $columns );
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <?php if ( WPT_IS_PRO ) : ?>
                <div class="wpt-pagination" id="wpt-pagination" aria-label="<?php esc_attr_e( 'Pagination', 'woo-product-table' ); ?>">
                    <?php $this->render_pagination( count( $products ), $per_page ); ?>
                </div>
            <?php endif; ?>

            <div class="wpt-messages" aria-live="polite" aria-atomic="true"></div>
        </div>
        <?php
    }

    // ── Filter bar ───────────────────────────────────────────────────────────────

    private function render_filter_bar( array $categories, string $active_category ): void {
        ?>
        <div class="wpt-filter-bar" role="search">
            <div class="wpt-filter-group">
                <label for="wpt-search" class="screen-reader-text">
                    <?php esc_html_e( 'Search products', 'woo-product-table' ); ?>
                </label>
                <input
                    type="search"
                    id="wpt-search"
                    class="wpt-search"
                    placeholder="<?php esc_attr_e( 'Search products…', 'woo-product-table' ); ?>"
                    aria-label="<?php esc_attr_e( 'Search products', 'woo-product-table' ); ?>"
                />
            </div>

            <div class="wpt-filter-group">
                <label for="wpt-category" class="screen-reader-text">
                    <?php esc_html_e( 'Filter by category', 'woo-product-table' ); ?>
                </label>
                <select id="wpt-category" class="wpt-category-filter" aria-label="<?php esc_attr_e( 'Filter by category', 'woo-product-table' ); ?>">
                    <option value=""><?php esc_html_e( 'All Categories', 'woo-product-table' ); ?></option>
                    <?php foreach ( $categories as $cat ) : ?>
                        <option value="<?php echo esc_attr( $cat['slug'] ); ?>"
                            <?php selected( $active_category, $cat['slug'] ); ?>>
                            <?php echo esc_html( $cat['name'] ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="wpt-filter-group wpt-filter-group--inline">
                <label for="wpt-instock" class="wpt-checkbox-label">
                    <input
                        type="checkbox"
                        id="wpt-instock"
                        class="wpt-instock-filter"
                    />
                    <?php esc_html_e( 'In Stock Only', 'woo-product-table' ); ?>
                </label>
            </div>

            <div class="wpt-filter-group">
                <button type="button" class="wpt-btn wpt-btn--clear" id="wpt-clear-filters">
                    <?php esc_html_e( 'Clear', 'woo-product-table' ); ?>
                </button>
            </div>
        </div>
        <?php
    }

    // ── Table head ───────────────────────────────────────────────────────────────

    private function render_thead( array $columns, string $sort, string $order ): void {
        $sortable = array( 'name', 'price', 'rating' );
        $labels   = self::available_columns();

        foreach ( $columns as $col ) {
            $label = $labels[ $col ] ?? ucfirst( $col );

            if ( in_array( $col, $sortable, true ) ) {
                $next_order = ( $sort === $col && $order === 'ASC' ) ? 'DESC' : 'ASC';
                $aria_sort  = '';
                $icon       = '<span class="wpt-sort-icon" aria-hidden="true">⇅</span>';

                if ( $sort === $col ) {
                    $aria_sort = $order === 'ASC' ? 'ascending' : 'descending';
                    $icon      = $order === 'ASC'
                        ? '<span class="wpt-sort-icon wpt-sort-icon--asc" aria-hidden="true">↑</span>'
                        : '<span class="wpt-sort-icon wpt-sort-icon--desc" aria-hidden="true">↓</span>';
                }

                printf(
                    '<th scope="col" class="wpt-col wpt-col--%1$s wpt-sortable %2$s" data-sort="%1$s" data-order="%3$s" role="columnheader" aria-sort="%4$s" tabindex="0">%5$s %6$s</th>',
                    esc_attr( $col ),
                    $sort === $col ? 'wpt-sorted' : '',
                    esc_attr( $next_order ),
                    esc_attr( $aria_sort ?: 'none' ),
                    esc_html( $label ),
                    $icon // already escaped above
                );
            } else {
                printf(
                    '<th scope="col" class="wpt-col wpt-col--%s" role="columnheader">%s</th>',
                    esc_attr( $col ),
                    esc_html( $label )
                );
            }
        }
    }

    // ── Table row ────────────────────────────────────────────────────────────────

    public function render_row( WC_Product $product, array $columns ): void {
        $in_stock   = $product->is_in_stock();
        $stock_class = $in_stock ? 'wpt-instock' : 'wpt-outofstock';
        ?>
        <tr class="wpt-row <?php echo esc_attr( $stock_class ); ?>"
            data-product-id="<?php echo esc_attr( $product->get_id() ); ?>"
            data-product-name="<?php echo esc_attr( strtolower( $product->get_name() ) ); ?>"
            data-instock="<?php echo $in_stock ? '1' : '0'; ?>"
            data-price="<?php echo esc_attr( $product->get_price() ); ?>"
            data-rating="<?php echo esc_attr( $product->get_average_rating() ); ?>">
            <?php foreach ( $columns as $col ) : ?>
                <?php $this->render_cell( $col, $product ); ?>
            <?php endforeach; ?>
        </tr>
        <?php
    }

    // ── Cell rendering ───────────────────────────────────────────────────────────

    private function render_cell( string $col, WC_Product $product ): void {
        switch ( $col ) {
            case 'image':
                $thumb = $product->get_image( 'woocommerce_thumbnail', array( 'class' => 'wpt-product-image' ) );
                $link  = get_permalink( $product->get_id() );
                printf(
                    '<td class="wpt-cell wpt-cell--image"><a href="%s" aria-label="%s">%s</a></td>',
                    esc_url( $link ),
                    esc_attr( $product->get_name() ),
                    $thumb // WC-generated, safe
                );
                break;

            case 'name':
                $link = get_permalink( $product->get_id() );
                printf(
                    '<td class="wpt-cell wpt-cell--name"><a href="%s" class="wpt-product-link">%s</a></td>',
                    esc_url( $link ),
                    esc_html( $product->get_name() )
                );
                break;

            case 'price':
                printf(
                    '<td class="wpt-cell wpt-cell--price">%s</td>',
                    $product->get_price_html() // WC-generated, already escaped
                );
                break;

            case 'stock':
                $in_stock    = $product->is_in_stock();
                $status_text = $in_stock
                    ? __( 'In Stock', 'woo-product-table' )
                    : __( 'Out of Stock', 'woo-product-table' );
                $badge_class = $in_stock ? 'wpt-badge--instock' : 'wpt-badge--outofstock';
                printf(
                    '<td class="wpt-cell wpt-cell--stock"><span class="wpt-badge %s">%s</span></td>',
                    esc_attr( $badge_class ),
                    esc_html( $status_text )
                );
                break;

            case 'quantity':
                if ( $product->is_in_stock() && $product->is_purchasable() && ! $product->is_type( 'external' ) ) {
                    $max_qty = $product->get_max_purchase_quantity();
                    printf(
                        '<td class="wpt-cell wpt-cell--quantity">
                            <input
                                type="number"
                                class="wpt-qty"
                                value="1"
                                min="1"
                                max="%s"
                                step="1"
                                aria-label="%s"
                                data-product-id="%d"
                            />
                        </td>',
                        $max_qty > 0 ? esc_attr( $max_qty ) : '',
                        esc_attr__( 'Quantity', 'woo-product-table' ),
                        esc_attr( $product->get_id() )
                    );
                } else {
                    echo '<td class="wpt-cell wpt-cell--quantity">—</td>';
                }
                break;

            case 'add_to_cart':
                if ( $product->is_purchasable() && $product->is_in_stock() && ! $product->is_type( 'external' ) ) {
                    printf(
                        '<td class="wpt-cell wpt-cell--add-to-cart">
                            <button
                                type="button"
                                class="wpt-btn wpt-atc-btn button alt"
                                data-product-id="%d"
                                data-product-type="%s"
                                aria-label="%s"
                            >%s</button>
                        </td>',
                        esc_attr( $product->get_id() ),
                        esc_attr( $product->get_type() ),
                        esc_attr( sprintf( __( 'Add %s to cart', 'woo-product-table' ), $product->get_name() ) ),
                        esc_html__( 'Add to cart', 'woo-product-table' )
                    );
                } elseif ( $product->is_type( 'external' ) ) {
                    printf(
                        '<td class="wpt-cell wpt-cell--add-to-cart">
                            <a href="%s" class="wpt-btn button" target="_blank" rel="noopener noreferrer">%s</a>
                        </td>',
                        esc_url( $product->get_product_url() ),
                        esc_html( $product->get_button_text() ?: __( 'Buy product', 'woo-product-table' ) )
                    );
                } else {
                    echo '<td class="wpt-cell wpt-cell--add-to-cart">—</td>';
                }
                break;

            default:
                echo '<td class="wpt-cell"></td>';
                break;
        }
    }

    // ── Pagination (Pro only) ────────────────────────────────────────────────────

    private function render_pagination( int $total, int $per_page ): void {
        if ( ! WPT_IS_PRO ) {
            return;
        }
        $pages = (int) ceil( $total / max( 1, $per_page ) );
        if ( $pages <= 1 ) {
            return;
        }
        for ( $i = 1; $i <= $pages; $i++ ) {
            printf(
                '<button type="button" class="wpt-page-btn %s" data-page="%d">%d</button>',
                $i === 1 ? 'wpt-page-btn--active' : '',
                $i,
                $i
            );
        }
    }
}
