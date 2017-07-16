<?php
/*
Plugin Name: Woocommerce Autoship PayU LATAM Recurring Payments
Plugin URI: http://github.com/jscarton/woocomerce-autoship-payu-latam-recurring-payments
Description: Add payu recurring payments as autoship payment gateway
Version: 1.0.0
Author: Juan Scarton
Author URI: http://github.com/jscarton
License: GPLV3
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'SUPERLIST_AUTOSHIP_PAYU_LATAM_RECURRING_PAYMENTS_VERSION', '1.0.0' );
define( 'SUPERLIST_PAYU_ROOT', plugin_dir_path( __FILE__ ) );
define( 'SUPERLIST_PAYU_ROOT_URL', plugin_dir_url( __FILE__ ) );
include_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once SUPERLIST_PAYU_ROOT."includes/superlist-payu-loader.php";
//echo '<div class="error">';
//var_dump(is_plugin_active( 'woocommerce-autoship/wc-autoship.php' ));
//echo '</div>';

if ( is_plugin_active( 'woocommerce/woocommerce.php' ) &&  is_plugin_active( 'woocommerce-autoship/woocommerce-autoship.php' )) {
	
	function superlist_payu_activate() {
		SuperlistPayuSetup::activate();
	}
	register_activation_hook( __FILE__, 'superlist_payu_activate' );
	function superlist_payu_deactivate() {
		SuperlistPayuSetup::deactivate();
	}
	register_deactivation_hook( __FILE__, 'superlist_payu_deactivate' );
	function superlist_payu_uninstall() {
		SuperlistPayuSetup::uninstall();
	}
	register_uninstall_hook( __FILE__, 'superlist_payu_uninstall' );
	
	//register shortcodes
	$shortcodes=new SuperlistPayuShortcodes();
	$shortcodes->register();

	function superlist_payu_load_gateway_class() {
		// Initialize WooCommerce
		if ( is_plugin_active( 'woocommerce-autoship/woocommerce-autoship.php' ) && function_exists( 'WC' ) ) {
			WC();
			// Include gateway class
			require_once SUPERLIST_PAYU_ROOT."classes/superlist-payu-autoship-payment-gateway.php";
		}
	}
	add_action( 'plugins_loaded', 'superlist_payu_load_gateway_class' );

	function superlist_payu_payments_register_gateway( $methods ) {
		if ( is_plugin_active( 'woocommerce-autoship/woocommerce-autoship.php' ) ) {
			$methods[] = 'SuperlistPayuAutoshipPaymentGateway';
		}
		return $methods;
	}
	add_filter( 'woocommerce_payment_gateways', 'superlist_payu_payments_register_gateway' );

	function superlist_payu_payments_load_for_functions()
	{
		require_once SUPERLIST_PAYU_ROOT."includes/superlist-payu-loader.php";
		echo "Lo hizo";
	}
	add_action ("superlist_payu_payments_functions_init",'superlist_payu_payments_load_for_functions');
	
	if ( is_admin() ) {
		// Register admin settings
		$settings = new SuperlistPayuSettings();
		$settings->register();
		
		// System status
		/*function superlist_payu_system_status_notices() {
			if ( isset( $_GET['tab'] ) && $_GET['tab'] == 'wc_autoship' ) {
				return;
			}
			
			$errors = SuperlistPayuSetup::get_system_errors();
			foreach ( $errors as $message ) {
				if ( $message != '' ) {
					?>
					<div class="error">
						<?php echo $message; ?>
					</div>
					<?php
				}
			}
			
			if ( count( $errors ) < 1 ) {
				$warnings = SuperlistPayuSetup::get_system_warnings();
				foreach ( $warnings as $message ) {
					if ( $message != '' ) {
						?>
						<div class="update-nag">
							<?php echo $message; ?>
						</div>
						<?php
					}
				}
			}
			
			SuperlistPayuSetup::print_admin_alerts();
		}
		add_action( 'admin_notices', 'superlist_payu_system_status_notices' );*/
		
		// Register actions
		/*require_once( 'classes/superlist-payu-admin-actions.php' );
		$admin_actions = new SuperlistPayuAdminActions( __FILE__ );
		$admin_actions->add_actions();*/
	}
	
	/*function superlist_payu_woocommerce_init() {
		// Check system status
		if ( SuperlistPayuSetup::system_status_is_ok() ) {
			// Register actions
			require_once( 'classes/superlist-payu-actions.php' );
			$actions = new SuperlistPayuActions( __FILE__ );
			$actions->add_actions();
			// Register filters
			require_once( 'classes/superlist-payu-filters.php' );
			$filters = new SuperlistPayuFilters( __FILE__ );
			$filters->add_filters();
			// Add shortcodes
			require_once( 'classes/superlist-payu-shortcodes.php' );
			$shortcodes = new SuperlistPayuShortcodes( __FILE__ );
			$shortcodes->add_shortcodes();
		}
	}
	add_action( 'woocommerce_init', 'superlist_payu_woocommerce_init' );*/

// 	function wc_autoship_plugin_update_message() {

// 	}
// 	$plugin_file   = basename( __FILE__ );
// 	$plugin_folder = basename( dirname( __FILE__ ) );
// 	$update_hook = "in_plugin_update_message-{$plugin_folder}/{$plugin_file}";
// 	add_action( $update_hook, 'wc_autoship_plugin_update_message', 20, 2 );
}