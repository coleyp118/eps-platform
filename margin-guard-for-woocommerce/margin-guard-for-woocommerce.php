<?php
/**
 * Plugin Name: Margin Guard for WooCommerce
 * Plugin URI: https://cvbpaeqfvnpgylmzhede.supabase.co/functions/v1/margin-guard-pro
 * Description: Protect WooCommerce margins by capping coupon discounts so products do not fall below your configured minimum gross margin.
 * Version: 1.0.2
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * Author: Margin Guard
 * Developer: Margin Guard
 * Developer URI: https://cvbpaeqfvnpgylmzhede.supabase.co/functions/v1/margin-guard-pro
 * Text Domain: margin-guard-for-woocommerce
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

defined( 'ABSPATH' ) || exit;

final class MGFW_Plugin {
    const VERSION = '1.0.2';
    const OPTION_MIN_MARGIN = 'mgfw_min_margin';
    const COST_META = '_mgfw_cost';

    public static function init() {
        add_action( 'before_woocommerce_init', array( __CLASS__, 'declare_compatibility' ) );
        add_action( 'plugins_loaded', array( __CLASS__, 'boot' ) );
    }

    public static function declare_compatibility() {
        if ( class_exists( '\\Automattic\\WooCommerce\\Utilities\\FeaturesUtil' ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
        }
    }

    public static function boot() {
        if ( class_exists( 'MGP_Plugin' ) ) {
            return;
        }
        if ( ! class_exists( 'WooCommerce' ) ) {
            add_action( 'admin_notices', array( __CLASS__, 'woocommerce_required_notice' ) );
            return;
        }

        add_action( 'woocommerce_product_options_pricing', array( __CLASS__, 'product_cost_field' ) );
        add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save_product_cost' ) );
        add_filter( 'woocommerce_coupon_get_discount_amount', array( __CLASS__, 'cap_coupon_discount' ), 20, 5 );

        add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
        add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
        add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( __CLASS__, 'action_links' ) );
    }

    public static function woocommerce_required_notice() {
        if ( ! current_user_can( 'activate_plugins' ) ) {
            return;
        }
        echo '<div class="notice notice-error"><p>' . esc_html__( 'Margin Guard for WooCommerce requires WooCommerce to be installed and active.', 'margin-guard-for-woocommerce' ) . '</p></div>';
    }

    public static function product_cost_field() {
        woocommerce_wp_text_input(
            array(
                'id'                => self::COST_META,
                'label'             => __( 'Cost of goods', 'margin-guard-for-woocommerce' ) . ' (' . get_woocommerce_currency_symbol() . ')',
                'description'       => __( 'Your per-unit cost. Margin Guard uses this value to cap coupon discounts.', 'margin-guard-for-woocommerce' ),
                'desc_tip'          => true,
                'type'              => 'number',
                'custom_attributes' => array(
                    'step' => '0.01',
                    'min'  => '0',
                ),
            )
        );
    }

    public static function save_product_cost( $post_id ) {
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        if ( ! isset( $_POST[ self::COST_META ] ) ) {
            return;
        }

        $raw = wc_clean( wp_unslash( $_POST[ self::COST_META ] ) );
        if ( '' === $raw ) {
            delete_post_meta( $post_id, self::COST_META );
            return;
        }

        $cost = max( 0, (float) wc_format_decimal( $raw ) );
        update_post_meta( $post_id, self::COST_META, wc_format_decimal( $cost ) );
    }

    public static function get_min_margin() {
        $value = (float) get_option( self::OPTION_MIN_MARGIN, 20 );
        return min( 95, max( 0, $value ) );
    }

    public static function cap_coupon_discount( $discount, $discounting_amount, $cart_item, $single, $coupon ) {
        if ( $discount <= 0 || $discounting_amount <= 0 || empty( $cart_item['data'] ) || ! is_a( $cart_item['data'], 'WC_Product' ) ) {
            return $discount;
        }

        $product = $cart_item['data'];
        $product_id = $product->get_id();
        $cost = (float) get_post_meta( $product_id, self::COST_META, true );

        if ( $cost <= 0 && $product->is_type( 'variation' ) ) {
            $cost = (float) get_post_meta( $product->get_parent_id(), self::COST_META, true );
        }
        if ( $cost <= 0 ) {
            return $discount;
        }

        $margin = self::get_min_margin() / 100;
        if ( $margin >= 1 ) {
            return 0;
        }

        $quantity = $single ? 1 : max( 1, isset( $cart_item['quantity'] ) ? (int) $cart_item['quantity'] : 1 );
        $minimum_revenue = ( $cost * $quantity ) / ( 1 - $margin );
        $maximum_discount = max( 0, (float) $discounting_amount - $minimum_revenue );

        return min( (float) $discount, $maximum_discount );
    }

    public static function admin_menu() {
        add_submenu_page(
            'woocommerce',
            __( 'Margin Guard', 'margin-guard-for-woocommerce' ),
            __( 'Margin Guard', 'margin-guard-for-woocommerce' ),
            'manage_woocommerce',
            'margin-guard',
            array( __CLASS__, 'settings_page' )
        );
    }

    public static function register_settings() {
        register_setting(
            'mgfw_settings',
            self::OPTION_MIN_MARGIN,
            array(
                'type'              => 'number',
                'sanitize_callback' => array( __CLASS__, 'sanitize_margin' ),
                'default'           => 20,
            )
        );
    }

    public static function sanitize_margin( $value ) {
        return min( 95, max( 0, (float) $value ) );
    }

    public static function settings_page() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Margin Guard', 'margin-guard-for-woocommerce' ); ?></h1>
            <p><?php esc_html_e( 'Set a global minimum gross margin. Coupon discounts are capped per item when a Cost of goods value is present.', 'margin-guard-for-woocommerce' ); ?></p>
            <form method="post" action="options.php">
                <?php settings_fields( 'mgfw_settings' ); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="mgfw_min_margin"><?php esc_html_e( 'Minimum gross margin', 'margin-guard-for-woocommerce' ); ?></label></th>
                        <td>
                            <input name="<?php echo esc_attr( self::OPTION_MIN_MARGIN ); ?>" id="mgfw_min_margin" type="number" min="0" max="95" step="0.1" value="<?php echo esc_attr( self::get_min_margin() ); ?>" class="small-text" /> %
                            <p class="description"><?php esc_html_e( 'Example: at 20%, a product that costs $80 cannot be discounted below $100.', 'margin-guard-for-woocommerce' ); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <hr />
            <h2><?php esc_html_e( 'Need stricter controls?', 'margin-guard-for-woocommerce' ); ?></h2>
            <p><?php esc_html_e( 'Margin Guard Pro adds product-specific and category-specific floors, under-margin cart blocking, and a margin-risk dashboard.', 'margin-guard-for-woocommerce' ); ?></p>
            <p><a class="button button-secondary" href="https://buy.stripe.com/28E00jgpN7wechce9o2B203" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View Margin Guard Pro', 'margin-guard-for-woocommerce' ); ?></a></p>
        </div>
        <?php
    }

    public static function action_links( $links ) {
        $settings = '<a href="' . esc_url( admin_url( 'admin.php?page=margin-guard' ) ) . '">' . esc_html__( 'Settings', 'margin-guard-for-woocommerce' ) . '</a>';
        array_unshift( $links, $settings );
        return $links;
    }
}

MGFW_Plugin::init();
