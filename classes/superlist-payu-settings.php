<?php


require_once('superlist-payu-setup.php');

class SuperlistPayuSettings {

	//account data for sandbox testing
	const SUPERLIST_PAYU_SANDBOX_MERCHANT_ID="508029";
	const SUPERLIST_PAYU_SANDBOX_ACCOUNT_ID="512327";
	const SUPERLIST_PAYU_SANDBOX_API_LOGIN="pRRXKOl8ikMmt9u";
	const SUPERLIST_PAYU_SANDBOX_API_KEY="4Vj8eK4rloUd272L48hsrarnUA";

	//production endpoints
	const SUPERLIST_PAYU_PAYMENTS_CUSTOM_URL="https://api.payulatam.com/payments-api/4.0/service.cgi";
	const SUPERLIST_PAYU_REPORTS_CUSTOM_URL="https://api.payulatam.com/reports-api/4.0/service.cgi";
	const SUPERLIST_PAYU_SUBSCRIPTION_CUSTOM_URL="https://api.payulatam.com/payments-api/rest/v4.3/";

	//sandbox endpoints
	const SUPERLIST_PAYU_SANDBOX_PAYMENTS_CUSTOM_URL="https://sandbox.api.payulatam.com/payments-api/4.0/service.cgi";
	const SUPERLIST_PAYU_SANDBOX_REPORTS_CUSTOM_URL="https://sandbox.api.payulatam.com/reports-api/4.0/service.cgi";
	const SUPERLIST_PAYU_SANDBOX_SUBSCRIPTION_CUSTOM_URL="https://sandbox.api.payulatam.com/payments-api/rest/v4.3/";

	
	public function register() {
		add_filter(
			'woocommerce_settings_tabs_array',
			array( $this, 'add_settings_tab' ),
			50,
			1
		);
		
		add_action( 
			'woocommerce_settings_tabs_superlist_payu', 
			array( $this, 'settings_tab' )
		);
		
		add_action( 
			'woocommerce_update_options_superlist_payu', 
			array( $this, 'update_options' )
		);
	}
	
	public function add_settings_tab( $tabs ) {
		$tabs['superlist_payu'] = __( 'Superlist Payu Latam', 'superlist-payu' );
		return $tabs;
	}
	
	public function get_settings() {
		$superlist_payu_settings = array(
			array(
				'name' => __( 'Superlist Payu Latam Payment Gateway Settings', 'superlist-payu' ),
				'type' => 'title',
				'desc' => __( 'Enter general system settings for Superlist Payu Latam.', 'superlist-payu' ),
				'id' => 'superlist_payu_settings'
			),
			array(
				'name' => __( 'Merchant ID', 'superlist-payu' ),
				'desc' => __( 'Type your Payu\'s Merchant ID.', 'superlist-payu' ),
				'desc_tip' => false,
				'type' => 'text',
				'id' => 'superlist_payu_merchant_id'
			),
			array(
				'name' => __( 'Account ID', 'superlist-payu' ),
				'desc' => __( 'Type your Payu\'s Account ID.', 'superlist-payu' ),
				'desc_tip' => false,
				'type' => 'text',
				'id' => 'superlist_payu_account_id'
			),
			array(
				'name' => __( 'API Login', 'superlist-payu' ),
				'desc' => __( 'Type your Payu\'s API login.', 'superlist-payu' ),
				'desc_tip' => false,
				'type' => 'text',
				'id' => 'superlist_payu_api_login'
			),
			array(
				'name' => __( 'API Key', 'superlist-payu' ),
				'desc' => __( 'Type your Payu\'s API Key.', 'superlist-payu' ),
				'desc_tip' => false,
				'type' => 'text',
				'id' => 'superlist_payu_api_key'
			),
			array(
				'name' => __( 'Enable Sandbox Mode', 'superlist-payu' ),
				'desc' => __( 'Select to enable PayU sanbox.', 'superlist-payu' ),
				'desc_tip' => false,
				'type' => 'checkbox',
				'id' => 'superlist_payu_sandbox_mode'
			),
			array(
				'name' => __( 'Enable Test Mode on Production', 'superlist-payu' ),
				'desc' => __( 'Select to enable Test transactions on production webservice.', 'superlist-payu' ),
				'desc_tip' => false,
				'type' => 'checkbox',
				'id' => 'superlist_payu_test_mode'
			),
			array(
				'name' => __( 'Enable Local Test Mode', 'superlist-payu' ),
				'desc' => __( 'Select to always return success on local.', 'superlist-payu' ),
				'desc_tip' => false,
				'type' => 'checkbox',
				'id' => 'superlist_payu_local_test_mode'
			),
			array(
				'name' => __( 'Select main language', 'superlist-payu' ),
				'desc' => __( 'The main language accepted on woocommerce store', 'superlist-payu' ),
				'desc_tip' => true,
				'type' => 'select',
				'id' => 'superlist_payu_language',
				'options' => array(
					'' => __( 'Use site language', 'superlist-payu' ),
					'pt' => __( 'Portugues', 'superlist-payu' ),
					'es' => __( 'Spanish', 'superlist-payu' ),
					'en' => __( 'English', 'superlist-payu' ),
				)
			),
			array(
				'name' => __( 'Checkout Page', 'superlist-payu' ),
				'desc' => __( 'The page for make payments for an autoship order', 'superlist-payu' ),
				'desc_tip' => true,
				'type' => 'single_select_page',
				'id' => 'superlist_payu_checkout_page_id'
			),
			array(
				'name' => __( 'Update Payment Method Page', 'superlist-payu' ),
				'desc' => __( 'The page for payment Method updates.', 'superlist-payu' ),
				'desc_tip' => true,
				'type' => 'single_select_page',
				'id' => 'superlist_payu_edit_method_page_id'
			),
			array(
				'name' => __( 'Minimum Order Amount', 'superlist-payu' ),
				'desc' => __( 'The minimum amount of purchase.', 'superlist-payu' ),
				'desc_tip' => true,
				'type' => 'number',
				'id' => 'superlist_payu_minimum_order_amount'
			),
			array(
				'name' => __( 'Enable Debug Mode', 'superlist-payu' ),
				'desc' => __( 'Select to send every request debug trace via email.', 'superlist-payu' ),
				'desc_tip' => false,
				'type' => 'checkbox',
				'id' => 'superlist_payu_debug_mode'
			),
			array(
				'type' => 'sectionend',
				'id' => 'superlist_payu_section_end'
			)
		);
		$settings = apply_filters( 'superlist_payu_settings', $superlist_payu_settings );
		return $settings;
	}
	
	public function settings_tab() {
		woocommerce_admin_fields( $this->get_settings() );
	}
	
	public function update_options() {
		woocommerce_update_options( $this->get_settings() );
	}

	public function __get($name)
	{
		return get_option("superlist_payu_".$name);
	}

	public function getEndpoints($is_sandbox="no")
	{
		if ($is_sandbox=="yes")
		{
			return [
				'payments_custom_url'=>self::SUPERLIST_PAYU_SANDBOX_PAYMENTS_CUSTOM_URL,
				'reports_custom_url'=>self::SUPERLIST_PAYU_SANDBOX_REPORTS_CUSTOM_URL,
				'subscription_custom_url'=>self::SUPERLIST_PAYU_SANDBOX_SUBSCRIPTION_CUSTOM_URL,
			];
		}
		else
		{
			return [
				'payments_custom_url'=>self::SUPERLIST_PAYU_PAYMENTS_CUSTOM_URL,
				'reports_custom_url'=>self::SUPERLIST_PAYU_REPORTS_CUSTOM_URL,
				'subscription_custom_url'=>self::SUPERLIST_PAYU_SUBSCRIPTION_CUSTOM_URL,
			];	
		}
	}

	public function getPayuCredentials($is_sandbox="no")
	{
		if ($is_sandbox=="yes")
		{
			return [
				'account_id'=>self::SUPERLIST_PAYU_SANDBOX_ACCOUNT_ID,
				'merchant_id'=>self::SUPERLIST_PAYU_SANDBOX_MERCHANT_ID,
				'api_login'=>self::SUPERLIST_PAYU_SANDBOX_API_LOGIN,
				'api_key'=>self::SUPERLIST_PAYU_SANDBOX_API_KEY,
			];
		}
		else
		{
			return [
				'account_id'=>$this->account_id,
				'merchant_id'=>$this->merchant_id,
				'api_login'=>$this->api_login,
				'api_key'=>$this->api_key,
			];	
		}
	}

	public function getPluginSettings()
	{
		return [
			'sandbox_mode'=>$this->sandbox_mode,
			'test_mode'=>$this->test_mode,
			'local_test_mode'=>$this->local_test_mode,
			'language'=>$this->language,
			'checkout_page_id'=>$this->checkout_page_id,
			'edit_method_page_id'=>$this->edit_method_page_id,
			'debug_mode'=>$this->debug_mode,
		];
	}
	
}