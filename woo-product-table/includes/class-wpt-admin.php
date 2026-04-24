<?php
/**
 * Admin settings page — registered under WooCommerce > Product Table.
 * Handles: visible columns, default sort/order, items per page.
 *
 * @package WooProductTable
 */

defined( 'ABSPATH' ) || exit;

class WPT_Admin {

    // ── Singleton ────────────────────────────────────────────────────────────────

    private static ?WPT_Admin $instance = null;

    public static function instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    // ── Hook registration ────────────────────────────────────────────────────────

    public function register_hooks(): void {
        add_filter( 'woocommerce_get_settings_pages', array( $this, 'add_settings_page' ) );
        add_action( 'admin_menu',                     array( $this, 'add_submenu_page' ) );
        add_action( 'admin_init',                     array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts',          array( $this, 'enqueue_admin_assets' ) );
    }

    // ── Add under WooCommerce > Product Table (direct submenu) ───────────────────

    public function add_submenu_page(): void {
        add_submenu_page(
            'woocommerce',
            __( 'Product Table Settings', 'woo-product-table' ),
            __( 'Product Table', 'woo-product-table' ),
            'manage_woocommerce',
            'wpt-settings',
            array( $this, 'render_settings_page' )
        );
    }

    // ── WooCommerce Settings API integration (optional tab approach) ─────────────

    public function add_settings_page( array $pages ): array {
        // We use a direct submenu instead, so just return pages untouched.
        return $pages;
    }

    // ── Settings registration (WordPress Settings API) ───────────────────────────

    public function register_settings(): void {
        register_setting(
            'wpt_settings_group',
            'wpt_settings',
            array(
                'sanitize_callback' => array( $this, 'sanitize_settings' ),
                'default'           => array(
                    'visible_columns' => array( 'image', 'name', 'price', 'stock', 'quantity', 'add_to_cart' ),
                    'default_sort'    => 'name',
                    'default_order'   => 'ASC',
                    'per_page'        => 10,
                ),
            )
        );

        // ── Section: Display ──────────────────────────────────────────────────────
        add_settings_section(
            'wpt_section_display',
            __( 'Display Settings', 'woo-product-table' ),
            array( $this, 'render_section_display' ),
            'wpt-settings'
        );

        add_settings_field(
            'wpt_visible_columns',
            __( 'Visible Columns', 'woo-product-table' ),
            array( $this, 'render_field_columns' ),
            'wpt-settings',
            'wpt_section_display'
        );

        add_settings_field(
            'wpt_per_page',
            __( 'Items Per Page', 'woo-product-table' ),
            array( $this, 'render_field_per_page' ),
            'wpt-settings',
            'wpt_section_display'
        );

        // ── Section: Sorting ──────────────────────────────────────────────────────
        add_settings_section(
            'wpt_section_sort',
            __( 'Default Sorting', 'woo-product-table' ),
            array( $this, 'render_section_sort' ),
            'wpt-settings'
        );

        add_settings_field(
            'wpt_default_sort',
            __( 'Default Sort Column', 'woo-product-table' ),
            array( $this, 'render_field_default_sort' ),
            'wpt-settings',
            'wpt_section_sort'
        );

        add_settings_field(
            'wpt_default_order',
            __( 'Default Sort Order', 'woo-product-table' ),
            array( $this, 'render_field_default_order' ),
            'wpt-settings',
            'wpt_section_sort'
        );
    }

    // ── Sanitisation ─────────────────────────────────────────────────────────────

    public function sanitize_settings( $input ): array {
        $clean = array();

        // Visible columns — array of allowed column keys.
        $allowed_cols = array_keys( WPT_Table::available_columns() );
        $clean['visible_columns'] = array();
        if ( ! empty( $input['visible_columns'] ) && is_array( $input['visible_columns'] ) ) {
            foreach ( $input['visible_columns'] as $col ) {
                if ( in_array( $col, $allowed_cols, true ) ) {
                    $clean['visible_columns'][] = $col;
                }
            }
        }
        // Ensure at least name is always visible.
        if ( empty( $clean['visible_columns'] ) ) {
            $clean['visible_columns'] = array( 'name', 'price', 'add_to_cart' );
        }

        // Default sort.
        $allowed_sorts = array( 'name', 'price', 'rating' );
        $clean['default_sort'] = in_array( $input['default_sort'] ?? '', $allowed_sorts, true )
            ? $input['default_sort']
            : 'name';

        // Default order.
        $clean['default_order'] = in_array( strtoupper( $input['default_order'] ?? '' ), array( 'ASC', 'DESC' ), true )
            ? strtoupper( $input['default_order'] )
            : 'ASC';

        // Per page — only 10, 25, 50 allowed (Pro gate lifted for admins).
        $allowed_per_page = array( 10, 25, 50 );
        $per_page = absint( $input['per_page'] ?? 10 );
        $clean['per_page'] = in_array( $per_page, $allowed_per_page, true ) ? $per_page : 10;

        return $clean;
    }

    // ── Admin asset ──────────────────────────────────────────────────────────────

    public function enqueue_admin_assets( string $hook ): void {
        if ( 'woocommerce_page_wpt-settings' !== $hook ) {
            return;
        }
        // Inline minimal admin style — no need for a separate file.
        $css = '
            .wpt-admin-columns { list-style:none; margin:0; padding:0; display:flex; flex-wrap:wrap; gap:8px; }
            .wpt-admin-columns li { background:#f0f0f1; border:1px solid #c3c4c7; border-radius:3px; padding:4px 10px; cursor:grab; }
            .wpt-admin-columns li label { display:flex; align-items:center; gap:6px; cursor:pointer; }
            .wpt-pro-badge { background:#f0a500; color:#fff; font-size:10px; font-weight:700; padding:1px 5px; border-radius:2px; text-transform:uppercase; }
            .wpt-upgrade-notice { background:#fff8e1; border-left:4px solid #f0a500; padding:10px 14px; margin-top:8px; }
        ';
        wp_add_inline_style( 'wp-admin', $css );
    }

    // ── Page render ──────────────────────────────────────────────────────────────

    public function render_settings_page(): void {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'woo-product-table' ) );
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'WooCommerce Product Table', 'woo-product-table' ); ?></h1>

            <?php
            // Show success notice after save.
            if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ) {
                printf(
                    '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                    esc_html__( 'Settings saved.', 'woo-product-table' )
                );
            }
            ?>

            <?php if ( ! WPT_IS_PRO ) : ?>
                <div class="wpt-upgrade-notice notice notice-warning">
                    <p>
                        <span class="wpt-pro-badge">PRO</span>
                        <strong><?php esc_html_e( 'Upgrade to Pro', 'woo-product-table' ); ?></strong> —
                        <?php esc_html_e( 'Unlock CSV export, custom columns drag-and-drop ordering, and advanced pagination controls.', 'woo-product-table' ); ?>
                        <a href="https://chandananvithahr.github.io/woo-product-table-site/#pricing" target="_blank" rel="noopener noreferrer">
                            <?php esc_html_e( 'Learn more →', 'woo-product-table' ); ?>
                        </a>
                    </p>
                </div>
            <?php endif; ?>

            <form method="post" action="options.php">
                <?php
                settings_fields( 'wpt_settings_group' );
                do_settings_sections( 'wpt-settings' );
                submit_button( __( 'Save Settings', 'woo-product-table' ) );
                ?>
            </form>

            <hr />

            <h2><?php esc_html_e( 'Shortcode Usage', 'woo-product-table' ); ?></h2>
            <p><?php esc_html_e( 'Place the shortcode anywhere on your site:', 'woo-product-table' ); ?></p>
            <table class="widefat striped" style="max-width:700px">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Shortcode', 'woo-product-table' ); ?></th>
                        <th><?php esc_html_e( 'Description', 'woo-product-table' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>[woo_product_table]</code></td>
                        <td><?php esc_html_e( 'Display all products using saved settings.', 'woo-product-table' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>[woo_product_table category="shirts"]</code></td>
                        <td><?php esc_html_e( 'Show only products in the "shirts" category.', 'woo-product-table' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>[woo_product_table sort="price" order="ASC" per_page="25"]</code></td>
                        <td><?php esc_html_e( 'Sort by price ascending, show 25 items.', 'woo-product-table' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>[woo_product_table sort="rating" order="DESC"]</code></td>
                        <td><?php esc_html_e( 'Sort by rating highest first.', 'woo-product-table' ); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    // ── Section descriptions ─────────────────────────────────────────────────────

    public function render_section_display(): void {
        echo '<p>' . esc_html__( 'Control which columns are shown and how many products appear per page.', 'woo-product-table' ) . '</p>';
    }

    public function render_section_sort(): void {
        echo '<p>' . esc_html__( 'Set the default column and direction when the table first loads.', 'woo-product-table' ) . '</p>';
    }

    // ── Field: Visible Columns ───────────────────────────────────────────────────

    public function render_field_columns(): void {
        $settings = WPT_Table::instance()->get_settings();
        $active   = $settings['visible_columns'];
        $all_cols = WPT_Table::available_columns();

        // Pro-only columns (placeholders for future features).
        $pro_cols = array(); // e.g. 'custom_field'

        echo '<ul class="wpt-admin-columns">';
        foreach ( $all_cols as $key => $label ) {
            $checked  = in_array( $key, $active, true );
            $is_pro   = in_array( $key, $pro_cols, true ) && ! WPT_IS_PRO;
            $disabled = $is_pro ? 'disabled' : '';
            printf(
                '<li>
                    <label>
                        <input type="checkbox" name="wpt_settings[visible_columns][]" value="%s" %s %s />
                        %s
                        %s
                    </label>
                </li>',
                esc_attr( $key ),
                checked( $checked, true, false ),
                esc_attr( $disabled ),
                esc_html( $label ),
                $is_pro ? '<span class="wpt-pro-badge">PRO</span>' : ''
            );
        }
        echo '</ul>';
        echo '<p class="description">' . esc_html__( 'Check the columns you want to display in the product table.', 'woo-product-table' ) . '</p>';
    }

    // ── Field: Per Page ──────────────────────────────────────────────────────────

    public function render_field_per_page(): void {
        $settings = WPT_Table::instance()->get_settings();
        $current  = (int) $settings['per_page'];
        $options  = array( 10, 25, 50 );

        echo '<select name="wpt_settings[per_page]">';
        foreach ( $options as $val ) {
            printf(
                '<option value="%d" %s>%d</option>',
                $val,
                selected( $current, $val, false ),
                $val
            );
        }
        echo '</select>';

        if ( ! WPT_IS_PRO ) {
            echo '<p class="description"><span class="wpt-pro-badge">PRO</span> ' .
                esc_html__( 'Custom per-page values and front-end pagination controls are available in Pro.', 'woo-product-table' ) .
                '</p>';
        }
    }

    // ── Field: Default Sort ──────────────────────────────────────────────────────

    public function render_field_default_sort(): void {
        $settings = WPT_Table::instance()->get_settings();
        $current  = $settings['default_sort'];
        $options  = array(
            'name'   => __( 'Name',   'woo-product-table' ),
            'price'  => __( 'Price',  'woo-product-table' ),
            'rating' => __( 'Rating', 'woo-product-table' ),
        );

        echo '<select name="wpt_settings[default_sort]">';
        foreach ( $options as $val => $label ) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr( $val ),
                selected( $current, $val, false ),
                esc_html( $label )
            );
        }
        echo '</select>';
    }

    // ── Field: Default Order ─────────────────────────────────────────────────────

    public function render_field_default_order(): void {
        $settings = WPT_Table::instance()->get_settings();
        $current  = $settings['default_order'];

        echo '<select name="wpt_settings[default_order]">';
        printf( '<option value="ASC" %s>%s</option>', selected( $current, 'ASC', false ), esc_html__( 'Ascending (A → Z / Low → High)', 'woo-product-table' ) );
        printf( '<option value="DESC" %s>%s</option>', selected( $current, 'DESC', false ), esc_html__( 'Descending (Z → A / High → Low)', 'woo-product-table' ) );
        echo '</select>';
    }
}
