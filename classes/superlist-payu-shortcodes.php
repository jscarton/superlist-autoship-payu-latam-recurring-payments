<?php

class SuperlistPayuShortcodes{

	public function register()
	{
		add_shortcode( 'superlist-payu-test', [$this,"do_test"] );
		add_shortcode( 'superlist-payu-checkout', [$this,"superlist_payu_checkout"] );
		add_shortcode( 'superlist-payu-retrieve', [$this,"superlist_payu_test_retrieve"] );		
	}

	public function do_test($attr)
	{
		global $wpdb;
		//try to create a credit card
		$cc=null;
		$response1=false;
		$response2=false;
		try{
			$cc= new SuperlistPayuCreditCard();
			$response1= $cc->createCreditCard("1","JUAN","4111111111111111","2020/04","VISA","88566968352");
			var_dump($response1);
			var_dump($response1->creditCardToken->creditCardTokenId);
			echo "<br/><br/>";
			var_dump($wpdb->get_row( "SELECT * FROM {$wpdb->prefix}superlist_payu_creditcards WHERE payer_id = 10"));
			echo "<br/><br/>";
		}
		catch (Exception $e)
		{
			var_dump($e);
		}
		//try to charge a credit card
		try{
			$response2= $cc->chargeCreditCard(
				"W".time(), 
				350.65, 
				"COMPRA FROM SUPERLIST",
				"Juan Scarton",
				"jscarton@gmail.com",
				"88566968352", 
				"RUA ESTADOS UNIDOS 242",
				"",
				"SAO PAULO",
				"SP",
				"01427-000",
				"(11)123456789",
				"JUAN JOSE",
				$response1->creditCardToken->creditCardTokenId,
				"1",
				"VISA"
				);
			var_dump($response2);
			$user=wp_get_current_user();
			$meta=get_user_meta($user->ID,"billing_persontype",true);
			echo "<br/>{$user->billing_cellphone} - $meta<br/>";
			echo $cc->dumpIt();
		}
		catch (Exception $e)
		{
			var_dump($e);
		}
		
	} 
	public function superlist_payu_checkout($attr)
	{		
		try{
			$gateway= new SuperlistPayuAutoshipPaymentGateway();
			$res=$gateway->process_stored_payment(new WC_Order(7481),new WC_Autoship_Customer(1));
			var_dump($res);
		}
		catch (Exception $ex)
		{
			var_dump($ex);
		}
	} 

	public function superlist_payu_test_retrieve($attr)
	{
		global $wpdb;
		$table_name= $wpdb->prefix . SuperlistPayuSetup::PREFIX."creditcards";
		$rs=$wpdb->get_row( "SELECT * FROM $table_name WHERE payer_id = 11");
		$token=new SuperlistPayuBase(['data'=>$rs]);
		var_dump($token->data->payment_method);		
	}
}