<?php
/*** load classes ***/
require_once SUPERLIST_PAYU_ROOT."classes/superlist-payu-base.php";
require_once SUPERLIST_PAYU_ROOT."classes/superlist-payu-settings.php";
require_once SUPERLIST_PAYU_ROOT."classes/superlist-payu-setup.php";
require_once SUPERLIST_PAYU_ROOT."classes/superlist-payu-credit-card.php";
require_once SUPERLIST_PAYU_ROOT."classes/superlist-payu-shortcodes.php";
//require_once SUPERLIST_PAYU_ROOT."classes/superlist-payu-autoship-payment-gateway.php";

/*** enqueue styles and scripts ***/
function superlist_payu_styles_and_scripts() {
	wp_enqueue_style( 'superlist-payu-css', SUPERLIST_PAYU_ROOT_URL."assets/css/style.css", false );
}
add_action( 'wp_enqueue_scripts', 'superlist_payu_styles_and_scripts' );

