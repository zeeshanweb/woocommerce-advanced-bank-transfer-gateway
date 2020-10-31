<?php
/*
 * Plugin Name: Advanced bank transfer payment gateway
 * Plugin URI: https://khanzeeshan.in/
 * Description: Advanced Bank transfer payment gateway.
 * Author: Zeeshan
 * Author URI: https://khanzeeshan.in/
 * Text Domain: wc-payment-gateways-abtpg
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) 
{
	exit;
}

/**
 * Required minimums and constants
 */
define( 'WC_ABTPG_VERSION', '1.0.0' ); // WRCS: DEFINED_VERSION.
define( 'WC_ABTPG_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
define( 'WC_ABTPG_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
function woocommerce_abtpg_missing_wc_notice() 
{
	/* translators: 1. URL link. */
	echo '<div class="error"><p><strong>' . sprintf( esc_html__( 'Advanced Bank trsnafer gateway requires WooCommerce to be installed and active. You can download %s here.', 'woocommerce-gateway-abtpg' ), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>' ) . '</strong></p></div>';
}

add_action( 'plugins_loaded', 'woocommerce_gateway_abtpg_init' );
function woocommerce_gateway_abtpg_init()
{
	if ( ! class_exists( 'WooCommerce' ) ) 
	{
		add_action( 'admin_notices', 'woocommerce_abtpg_missing_wc_notice' );
		return;
	}
	if ( ! class_exists( 'WC_Abtpg' ) )
	{
		class WC_Abtpg
		{
			/**
			 * @var Singleton The reference the *Singleton* instance of this class
			 */
			private static $instance;

			/**
			 * Returns the *Singleton* instance of this class.
			 *
			 * @return Singleton The *Singleton* instance.
			 */
			public static function get_instance() 
			{
				if ( null === self::$instance ) {
					self::$instance = new self();
				}
				return self::$instance;
			}
			/**
			 * Protected constructor to prevent creating a new instance of the
			 * *Singleton* via the `new` operator from outside of this class.
			 */
			private function __construct() 
			{
				add_action( 'admin_init', array( $this, 'install' ) );
				$this->init();
			}
			/**
			 * Init the plugin after plugins_loaded so environment variables are set.
			 *
			 * @since 1.0.0
			 * @version 1.0.0
			 */
			public function init()
			{
				require_once dirname( __FILE__ ) . '/includes/class-wc-gateway-abtpg.php';
				require_once dirname( __FILE__ ) . '/includes/class-wc-gateway-abtpg-hook.php';
				add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateways' ) );
			}
			/**
			 * Add the gateways to WooCommerce.
			 *
			 * @since 1.0.0
			 * @version 1.0.0
			 */
			public function add_gateways( $methods ) 
			{
				$methods[] = 'WC_Gateway_Abtpg'; // gateway class name is here
	            return $methods;
			}
			/**
			 * Handles upgrade routines.
			 *
			 * @since 1.0.0
			 * @version 1.0.0
			 */
			public function install() 
			{
				if ( ! is_plugin_active( plugin_basename( __FILE__ ) ) ) 
				{
					return;
				}
				$this->update_plugin_version();
			}
			/**
			 * Updates the plugin version in db
			 *
			 * @since 1.0.0
			 * @version 1.0.0
			 */
			public function update_plugin_version() 
			{
				delete_option( 'wc_abtpg_version' );
				update_option( 'wc_abtpg_version', WC_ABTPG_VERSION );
			}
		}
		WC_Abtpg::get_instance();
	}
}