<?php
/**
 * AJAX handler — processes add-to-cart requests without page reload.
 * Also handles CSV export (Pro) and dynamic product fetching.
 *
 * @package WooProductTable
 */

defined( 'ABSPATH' ) || exit;

class WPT_Ajax {

    // ── Singleton ────────────────────────────────────────────────────────────────

    private static ?WPT_Ajax $instance = null;

    public static function instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    // ── Hook registration ────────────────────────────────────────────────────────

    public function register_hooks(): void {
        // Add to cart — available to guests and logged-in users.
        add_action( 'wp_ajax_wpt_add_to_cart',        array( $this, 'handle_add_to_cart' ) );
        add_action( 'wp_ajax_nopriv_wpt_add_to_cart', array( $this, 'handle_add_to_cart' ) );

        // CSV export — Pro only, requires login.
        add_action( 'wp_ajax_wpt_export_csv', array( $this, 'handle_export_csv' ) );
    }

    // ── Add to cart ──────────────────────────────────────────────────────────────

    public function handle_add_to_cart(): void {
        // Verify nonce.
        if ( ! check_ajax_referer( 'wpt_ajax_nonce', 'nonce', false ) ) {
            wp_send_json_error(
                array( 'message' => __( 'Security check failed. Please refresh and try again.', 'woo-product-table' ) ),
                403
            );
        }

        $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
        $quantity   = isset( $_POST['quantity'] )   ? absint( $_POST['quantity'] )   : 1;

        if ( $product_id <= 0 ) {
            wp_send_json_error(
                array( 'message' => __( 'Invalid product.', 'woo-product-table' ) ),
                400
            );
        }

        // Clamp quantity to 1 minimum.
        $quantity = max( 1, $quantity );

        $product = wc_get_product( $product_id );

        if ( ! $product || ! $product->exists() ) {
            wp_send_json_error(
                array( 'message' => __( 'Product not found.', 'woo-product-table' ) ),
                404
            );
        }

        if ( ! $product->is_purchasable() ) {
            wp_send_json_error(
                array( 'message' => __( 'This product cannot be purchased.', 'woo-product-table' ) ),
                400
            );
        }

        if ( ! $product->is_in_stock() ) {
            wp_send_json_error(
                array( 'message' => __( 'This product is out of stock.', 'woo-product-table' ) ),
                400
            );
        }

        // Validate max purchase quantity.
        $max = $product->get_max_purchase_quantity();
        if ( $max > 0 && $quantity > $max ) {
            wp_send_json_error(
                array(
                    'message' => sprintf(
                        /* translators: %d: maximum quantity */
                        __( 'You can only add %d of this item to the cart.', 'woo-product-table' ),
                        $max
                    ),
                ),
                400
            );
        }

        $added = WC()->cart->add_to_cart( $product_id, $quantity );

        if ( false === $added ) {
            // WooCommerce may have added notices with the real reason.
            $notices = wc_get_notices( 'error' );
            $message = ! empty( $notices )
                ? wp_strip_all_tags( $notices[0]['notice'] )
                : __( 'Could not add the product to your cart.', 'woo-product-table' );

            wp_send_json_error( array( 'message' => $message ), 500 );
        }

        // Successful add: return updated cart count & totals.
        WC()->cart->calculate_totals();

        wp_send_json_success( array(
            'message'    => sprintf(
                /* translators: %s: product name */
                __( '"%s" has been added to your cart.', 'woo-product-table' ),
                $product->get_name()
            ),
            'cart_count' => WC()->cart->get_cart_contents_count(),
            'cart_total' => WC()->cart->get_cart_total(),
            'cart_url'   => wc_get_cart_url(),
        ) );
    }

    // ── CSV Export (Pro) ─────────────────────────────────────────────────────────

    public function handle_export_csv(): void {
        if ( ! WPT_IS_PRO ) {
            wp_send_json_error(
                array( 'message' => __( 'CSV export is a Pro feature. Please upgrade to WooCommerce Product Table Pro.', 'woo-product-table' ) ),
                403
            );
        }

        if ( ! check_ajax_referer( 'wpt_ajax_nonce', 'nonce', false ) ) {
            wp_send_json_error(
                array( 'message' => __( 'Security check failed.', 'woo-product-table' ) ),
                403
            );
        }

        if ( ! is_user_logged_in() ) {
            wp_send_json_error(
                array( 'message' => __( 'You must be logged in to export.', 'woo-product-table' ) ),
                401
            );
        }

        // Build filename.
        $filename = 'products-' . gmdate( 'Y-m-d' ) . '.csv';

        // Stream headers.
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $filename ) . '"' );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );

        $output = fopen( 'php://output', 'w' );

        // BOM for Excel UTF-8 compatibility.
        fputs( $output, "\xEF\xBB\xBF" );

        // Header row.
        fputcsv( $output, array(
            __( 'ID', 'woo-product-table' ),
            __( 'Name', 'woo-product-table' ),
            __( 'SKU', 'woo-product-table' ),
            __( 'Price', 'woo-product-table' ),
            __( 'Stock Status', 'woo-product-table' ),
            __( 'Rating', 'woo-product-table' ),
            __( 'Categories', 'woo-product-table' ),
            __( 'URL', 'woo-product-table' ),
        ) );

        // Fetch all products.
        $query = new WP_Query( array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ) );

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $product = wc_get_product( get_the_ID() );
                if ( ! $product ) {
                    continue;
                }

                $cats = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'names' ) );
                fputcsv( $output, array(
                    $product->get_id(),
                    $product->get_name(),
                    $product->get_sku(),
                    $product->get_price(),
                    $product->is_in_stock() ? 'In Stock' : 'Out of Stock',
                    $product->get_average_rating(),
                    is_array( $cats ) ? implode( ', ', $cats ) : '',
                    get_permalink( $product->get_id() ),
                ) );
            }
        }
        wp_reset_postdata();

        fclose( $output );
        exit;
    }
}
