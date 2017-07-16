<?php
$wc_autoship_path = dirname( dirname( dirname( __FILE__ ) ) ) . '/woocommerce-autoship/';
require_once( $wc_autoship_path . 'classes/wc-autoship.php' );
require_once( $wc_autoship_path . 'classes/payment-gateway/wc-autoship-payment-gateway.php' );
require_once( $wc_autoship_path . 'classes/payment-gateway/wc-autoship-payment-response.php' );
require_once( $wc_autoship_path . 'classes/wc-autoship-customer.php' );

class SuperlistPayuAutoshipPaymentGateway extends WC_Autoship_Payment_Gateway{

	private $generated_token=false;

	public function __construct() {
		// WooCommerce fields
		$this->id = 'superlist_payu';
		$this->icon = '';
		$this->order_button_text = __( 'Checkout with your Credit Card', 'superlist-payu' );
		$this->has_fields = true;
		$this->method_title = __( "Superlist + Payu Payment Gateway ", 'superlist-payu' );
		$this->method_description = __( 
			"Payu payment method supporting creditcard tokenization for safe store of payment information",
			'superlist-payu'
		);
		$this->description = $this->method_description;
		//$this->notify_url = admin_url( '/admin-ajax.php?action=wc_autoship_paypal_payments_ipn_callback' );
		// WooCommerce settings
		$this->init_form_fields();
		$this->init_settings();
		// Assign settings
		$this->title=__( 'Checkout with your Credit Card', 'superlist-payu' );
		$settingObj=new SuperlistPayuSettings();
		$this->plugin_settings = new SuperlistPayuBase($settingObj->getPluginSettings());
		$this->minimum_order_amount=floatval($settingObj->minimum_order_amount);
		// Supports
		$this->supports = array(
			'refunds'
		);
		// Payment gateway hooks
		add_action( 
			'woocommerce_update_options_payment_gateways_' . $this->id, 
			array( $this, 'process_admin_options' )
		);
//		add_action(
//			'woocommerce_api_wc_autoship_paypal_gateway',
//			array( $this, 'api_callback' )
//		);
	}

	public function init_form_fields()
	{
		$this->form_fields = array(
			'enabled' => array(
				'title' => __( 'Enable/Disable', 'wc-autoship' ),
				'type' => 'checkbox',
				'label' => __( 'Enable ' . $this->method_title, 'wc-autoship' ),
				'default' => 'yes',
				'id' => 'superlist_payu_method_enabled'
			),
			'title' => array(
				'title' => __( 'Checkout Title', 'wc-autoship' ),
				'type' => 'text',
				'description' => __( 
					'This controls the title which the user sees during checkout.', 'wc-autoship'
				),
				'default' => __( 'PayPal', 'wc-autoship' ),
				'desc_tip' => true,
				'id' => 'superlist_payu_checkout_title'
			)
			);
	}

	public function payment_fields() {
		$current_user=wp_get_current_user();
		$token=null;
		if ($current_user)
		{
			$token=$this->retrieveTokenFromDB($current_user->ID);
		}
		include dirname( dirname( __FILE__ ) ) . '/templates/frontend/payment-fields.php';
	}
	/**
	* Get the field names posted by the payment_fields form
	* @return string[]
	*/
	public function get_payment_field_names(){
		$field_names=[
			"superlist-payu-use-this",
			"superlist-payu-card-name",
			"superlist-payu-number",
			"superlist-payu-expiry",
			"superlist-payu-cvc"
		];
	}

	public function process_payment( $order_id ) {
		global $wpdb;
		$woocommerce = WC();
		
		// Get order
		$order = new WC_Order( $order_id );


		if ($this->plugin_settings->local_test_mode=='yes')
		{
			$order->add_order_note( __( 'Superlist+Payu: local test mode ON.', 'superlist-payu' ) );
			$order->payment_complete(time());
			return array(
							'result'   => 'success',
							'redirect' => $this->get_return_url( $order ),
						);
		}
		// Get totals
		$total = $order->get_total();
		//$total = 17.50;
		$total_shipping = $order->get_total_shipping();
		$total_tax = $order->get_total_tax();

		$precision = get_option( 'woocommerce_price_num_decimals' );

		//initialize the Payu Integration
		$CreditCardAPI= new SuperlistPayuCreditCard();
		//create and store the card on payu
		$customer= $order->get_user();
		if (!$customer)
		{
			wc_add_notice(
				__( 'Error: guest checkout is not currently supported', 'superlist-payu' ),
				'error'
			);
			return;
		}
		else
		{
			try{
				if ($total<$this->minimum_order_amount)
					throw new Exception("valor mínimo para compras R$ ".number_format($this->minimum_order_amount,2,',','.'), 500);
					
				$CUSTOMER_ID=$customer->ID;
				$customer_billing_dni=(intval($customer->billing_persontype)==1)?$customer->billing_cpf:$customer->billing_cnpj;
				$customer_billing_dni=str_replace([".","-","/"],["","",""], $customer_billing_dni);
				if (isset($_POST['superlist-payu-use-this']) && $_POST['superlist-payu-use-this']=='new'){
					$PAYER_NAME=trim($_POST['superlist-payu-card-name']);
					$CREDIT_CARD_NUMBER=trim(str_replace(" ","",$_POST['superlist-payu-number']));
					$expiration_date=trim(str_replace([" ","/"],["","-"],$_POST['superlist-payu-expiry']));
					$CREDIT_CARD_EXPIRATION_DATE=date ("Y/m",strtotime("28-".$expiration_date));
					$PAYMENT_METHOD=trim(strtoupper($_POST['superlist-payu-card-type']));
					if(intval($customer->billing_persontype)!==1)
					{
						if( $_POST['superlist-payu-cpf']!=='')
							$PAYER_DNI=trim(str_replace([".","-"],["",""], $_POST['superlist-payu-cpf']));
						else
							throw new Exception("O campo cpf não pode estar em branco", 500);
					}
					else
						$PAYER_DNI=trim($customer_billing_dni);
					$response=$CreditCardAPI->createCreditCard($CUSTOMER_ID,$PAYER_NAME,$CREDIT_CARD_NUMBER,$CREDIT_CARD_EXPIRATION_DATE,$PAYMENT_METHOD,$PAYER_DNI);	
					//store the token for future processing
					$this->generated_token=$response->creditCardToken->creditCardTokenId;
					$order->add_order_note( __( 'Superlist+Payu: new payment method saved ('.$PAYER_DNI.').', 'superlist-payu' ) );
				}
				else
				{
					$token= $this->retrieveTokenFromDB($customer->ID);
					if (!$token)
					{
						throw new Exception("Error retrieving the stored payment method", 500);
					}
					$this->generated_token=$token->data->credit_card_token_id;
					$PAYER_NAME=$token->data->payer_name;
					$PAYMENT_METHOD=$token->data->payment_method;
					$payment_method_id=$token->data->id;
				}
				//now make the charge to the stored card
				add_post_meta( $order->id, 'payu_dni_'.time(),$customer_billing_dni); 
				$response_transaction=$CreditCardAPI->chargeCreditCard(
					"SLO_".$order->id."_".time(), 
					number_format(floatval($total),2,".",""), 
					"SUPERLIST ORDER #".$order->id." Placed by ".$order->billing_first_name." ".$order->billing_last_name,
					$order->billing_first_name." ".$order->billing_last_name,
					$order->billing_email,
					$customer_billing_dni,
					$order->billing_address_1." ".$order->billing_number,
					$order->billing_address_2." ".$order->billing_neighborhood,
					$order->billing_city, 
					$order->billing_state,
					$order->billing_postcode,
					str_replace(" ","",$customer->billing_cellphone),
					$PAYER_NAME,
					$this->generated_token,
					"1", 
					$PAYMENT_METHOD);
				$order->add_order_note( __( 'Superlist+Payu: using this payment method.'.$PAYER_NAME, 'superlist-payu' ) );
				add_post_meta( $order->id, 'payu_responsex_'.time(), json_encode($response_transaction) );
				add_post_meta( $order->id, 'payu_config_'.time(), json_encode($CreditCardAPI->dumpIt()) );
				if ($response_transaction)
				{
					if ($response_transaction->transactionResponse->state!="DECLINED" && $response_transaction->transactionResponse->state!="ERROR")
					{

						add_post_meta( $order->id, 'superlist_payment_method', $this->id);
						add_post_meta( $order->id, 'superlist_payment_flag'  , $PAYMENT_METHOD);
						
						// Payment has been successful
						$order->add_order_note( __( 'Superlist+Payu payment completed.', 'superlist-payu' ) );
						$order->add_order_note( __( 'transactionId:'.$response_transaction->transactionResponse->transactionId, 'superlist-payu' ) );
						$order->add_order_note( __( 'transactionId:'.$response_transaction->transactionResponse->state, 'superlist-payu' ) );
						// Mark order as Paid
						$order->payment_complete( $response_transaction->transactionResponse->transactionId);
						// Empty the cart (Very important step)
						$woocommerce->cart->empty_cart();
						if(isset($_POST['superlist-payu-use-this']) && $_POST['superlist-payu-use-this']=='new'){
							//ensure only one token by customer
							$this->deleteTokenFromDB($CUSTOMER_ID);
							//store token on table
							$payment_method_id=$this->storeTokenOnDB($response);
							//create the autoship customer 
							$wc_autoship_customer = new WC_Autoship_Customer( $customer->ID );
							$payment_method_data = [];
							$wc_autoship_customer->store_payment_method($this->id, $payment_method_id, $payment_method_data);
						}
						add_post_meta( $order->id, 'superlist_payu_token_id', $payment_method_id );
						// Redirect to thank you page
						return array(
							'result'   => 'success',
							'redirect' => $this->get_return_url( $order ),
						);
					}
					else
					{
						if (!isset($response_transaction->transactionResponse->responseMessage))
							throw new Exception( $this->getResponseCodeMessage($response_transaction->transactionResponse->responseCode),500);
						else
							throw new Exception($response_transaction->transactionResponse->responseMessage, 500);
							
					}
					
				}

			}
			catch(Exception $e)
			{
				throw new Exception( $e->getMessage() );
			}
		}
		return;
	}
	
	/**
	 * Process an order using a stored payment method
	 * @param WC_Order $order
	 * @param WC_Autoship_Customer $customer
	 * @return WC_Autoship_Payment_Response
	 */
	public function process_stored_payment( WC_Order $order, WC_Autoship_Customer $customer ) {
		// init WC por si las fly
		WC();
		if ($this->plugin_settings->local_test_mode=='yes')
		{
			$order->add_order_note( __( 'Superlist+Payu: local test mode ON.', 'superlist-payu' ) );
			$order->payment_complete(time());
			return array(
							'result'   => 'success',
							'redirect' => $this->get_return_url( $order ),
						);
		}
		var_dump($order->id);
		var_dump($order->get_total());
		var_dump($this->minimum_order_amount);			
		// Create payment response
		$payment_response = new WC_Autoship_Payment_Response();
		//validate minimun ammount of purchase
		if ($order->get_total()<$this->minimum_order_amount){
			$payment_response->success = false;
			$payment_response->status = "101 - valor mínimo para compras R$ ".number_format($this->minimum_order_amount,2,',','.');
			return $payment_response;
		}
		//initialize the Payu Integration
		$CreditCardAPI= new SuperlistPayuCreditCard();
		//get the stored token
		$user=$customer->get_user();
		$token= $this->retrieveTokenFromDB($user->ID);
		if (!$token)
		{			
			wp_mail( "juan.scarton@superlist.com", "autoship:TOKEN NOT FOUND", "token not found for CUSTOMER_ID=".$user->ID, [], []);
			// No billing ID
			$payment_response->success = false;
			$payment_response->status = "SUPERLIST: TOKEN NOT FOUND";
			return $payment_response;
		
		}
		else
		{
			try{
				wp_mail( "juan.scarton@superlist.com", "autoship: TRYING", "trying payment for CUSTOMER_ID=".$user->ID." total=".$order->get_total(), [], []);
				$customer_billing_dni=$token->data->payer_dni;
				$customer_billing_dni=str_replace([".","-"],["",""], $customer_billing_dni);
				add_post_meta( $order->id, 'using_billing_dni', $customer_billing_dni );
				
				//now make the charge to the stored card				
				$response_transacction=$CreditCardAPI->chargeCreditCard(
					"SLO_".$order->id."_".time(), 
					$order->get_total(), 
					"SUPERLIST RECURRING ORDER #".$order->id." Placed by ".$order->billing_first_name." ".$order->billing_last_name,
					$order->billing_first_name." ".$order->billing_last_name,
					$order->billing_email,
					$customer_billing_dni, 
					$order->billing_address_1,
					$order->billing_address_2,
					$order->billing_city, 
					$order->billing_state,
					$order->billing_postcode,
					str_replace(" ","",$user->billing_cellphone),
					$token->data->payer_name,
					$token->data->credit_card_token_id,
					"1", 
					$token->data->payment_method);
					add_post_meta( $order->id, 'payu_response', json_encode($response_transacction));				
				if ($response_transacction)
				{
					if ($response_transacction->transactionResponse->state!="DECLINED" && $response_transacction->transactionResponse->state!="ERROR")
					{
						add_post_meta( $order->id, 'superlist_payment_method', $this->id);
						add_post_meta( $order->id, 'superlist_payment_flag'  , $token->data->payment_method);
					
						// Payment success
						$payment_response->success = true;
						$payment_response->transaction_id = $response_transacction->transactionResponse->transactionId;
						$payment_response->payment_id = $token->data->id;
						$payment_response->status = "Order processed with Superlist PayU Payment Gateway";
						wp_mail( "juan.scarton@superlist.com", "autoship:SUCCESS", "SUCCESS for CUSTOMER_ID=".$user->ID, [], []);
						return $payment_response;
					}
					else
					{
						$eMessage="";
						if (!isset($response_transaction->transactionResponse->responseMessage))
							$eMessage=$this->getResponseCodeMessage($response_transaction->transactionResponse->responseCode);
						else
							$eMessage=$this->getResponseCodeMessage($response_transaction->transactionResponse->responseMessage);
						wp_mail( "juan.scarton@superlist.com", "autoship:ERROR", "Error processing payment for CUSTOMER_ID=".$user->ID." with message:".$eMessage, [], []);
						$payment_response->success = false;
						$payment_response->status = $eMessage;
						return $payment_response;
					}
				}
				else{
					wp_mail( "juan.scarton@superlist.com", "autoship:NO RESPONSE", "Error processing payment for CUSTOMER_ID=".$user->ID, [], []);
					$payment_response->success = false;
					$payment_response->status = "NO RESPONSE";
				}

			}
			catch(Exception $e)
			{
				// Payment failed
				$payment_response->success = false;
				$payment_response->status = $e->getMessage();
				wp_mail( "juan.scarton@superlist.com", "autoship:EXCEPTION", "For CUSTOMER_ID:".$user->ID." Message:".$e->getMessage(), [], []);
			}
		}
		return $payment_response;
	}

	public function store_payment_method( WC_Autoship_Customer $customer, $payment_fields = array() ) {
		try{
			
			if (isset($_POST['superlist-payu-use-this']) && $_POST['superlist-payu-use-this']=='new')
			{
				//initialize the Payu Integration
				$CreditCardAPI= new SuperlistPayuCreditCard();
				//store the new credit card
				$user=$customer->get_user();
				$CUSTOMER_ID=$user->ID;
				//Set new credit card data
				$PAYER_NAME=trim($_POST['superlist-payu-card-name']);
				$CREDIT_CARD_NUMBER=trim(str_replace(" ","",$_POST['superlist-payu-number']));
				if(empty($_POST['superlist-payu-expiry'])){
					$expiration_date = trim($_POST['validade_1'].'-'.$_POST['validade_2']);
				}else{
					$expiration_date=trim(str_replace([" ","/"],["","-"],$_POST['superlist-payu-expiry']));
				}
				$CREDIT_CARD_EXPIRATION_DATE=date ("Y/m",strtotime("28-".$expiration_date));
				$PAYMENT_METHOD=trim(strtoupper($_POST['superlist-payu-card-type']));
				$customer_billing_dni=(intval($user->billing_persontype)==1)?$user->billing_cpf:$user->billing_cnpj;
				if (intval($user->billing_persontype)!=1 && intval($user->billing_persontype)!=2)
					throw new Exception("Erro: tipo de pessoa desconhecido",500);
				$PAYER_DNI=isset($_POST['superlist-payu-dni'])?trim(strtoupper($_POST['superlist-payu-dni'])):$customer_billing_dni;
				$data=[
				'user_id'=>$CUSTOMER_ID,
				'cardholder'=>$PAYER_NAME,
				'cardnumber'=>$CREDIT_CARD_NUMBER,
				'expiration'=>$CREDIT_CARD_EXPIRATION_DATE,
				'method'=>$PAYMENT_METHOD,
				'dni'=>$PAYER_DNI
				];
				//wp_mail("juan.scarton@superlist.com","changing payment method attempt",json_encode($data),[],[]);
				$response=$CreditCardAPI->createCreditCard($CUSTOMER_ID,$PAYER_NAME,$CREDIT_CARD_NUMBER,$CREDIT_CARD_EXPIRATION_DATE,$PAYMENT_METHOD,$PAYER_DNI);	
				//wp_mail("juan.scarton@superlist.com","changing payment method response",json_encode($response),[],[]);
				//check if there is a previous card
				$token= $this->retrieveTokenFromDB($CUSTOMER_ID);
				if ($response->code=="SUCCESS" && isset($response->creditCardToken))
				{
					//remove previous card from db
					if (!is_null($token))
					{
						$y=$this->deleteTokenFromDB($CUSTOMER_ID);
					
					}
					//store token on table
					$payment_method_id=$this->storeTokenOnDB($response);
					//create the autoship customer 
					$payment_method_data = [];
					$customer->store_payment_method($this->id, $payment_method_id, $payment_method_data);
					
					return true;
				}
			}
			else{
				//return get_permalink($this->plugin_settings->edit_method_page_id);
			}
		}
		catch (Exception $e){
			//return false;
			//exit;
		}
		//return false;
	}
	
	public function validate_fields() {
		return true;
	}
	
	/**
	 * Get the payment method description for a customer in HTML format
	 * @param WC_Autoship_Customer $customer
	 * @return string
	 */
	public function get_payment_method_description( WC_Autoship_Customer $customer ) {
		$payment_method_data = $customer->get_payment_method_data();
		if ( empty( $payment_method_data ) ) {
			return '';
		}
		$description = array( '<div class="paypal-description">' );
		$description[] = '<img src="https://www.paypalobjects.com/webstatic/mktg/logo/pp_cc_mark_37x23.jpg" '
				. 'alt="PayPal" /><br />';
		if ( isset( $payment_method_data['email'] ) ) {
			$description[] = ' <span>' . esc_html( $payment_method_data['email'] ) . '</span>';
		}
		$description[] = '</div>';
		return implode( '', $description );
	}

	public function process_admin_options() {
		parent::process_admin_options();
	}

	private function retrieveTokenFromDB($user_id)
	{
		global $wpdb;
		$table_name= $wpdb->prefix . SuperlistPayuSetup::PREFIX."creditcards";
		$rs=$wpdb->get_row( "SELECT * FROM $table_name WHERE payer_id = $user_id");
		if ($rs)
			return new SuperlistPayuBase(['data'=>$rs]);
		return NULL;
	}

	private function deleteTokenFromDB($user_id)
	{
		global $wpdb;
		$table_name= $wpdb->prefix . SuperlistPayuSetup::PREFIX."creditcards";
		$response=$wpdb->delete( $table_name, array( 'payer_id' => $user_id ) );
		return $response;
	}

	private function storeTokenOnDB($response)
	{
		global $wpdb;
		$table_name= $wpdb->prefix . SuperlistPayuSetup::PREFIX."creditcards";
			$data=[
				"credit_card_token_id"=>$response->creditCardToken->creditCardTokenId,
				"payer_name"=>$response->creditCardToken->name,
				"payer_id"=>$response->creditCardToken->payerId,
				"payer_dni"=>$response->creditCardToken->identificationNumber,
				"payment_method"=>$response->creditCardToken->paymentMethod,
				"payment_maskednumber"=>$response->creditCardToken->maskedNumber
			];
			$format=[
				"%s",
				"%s",
				"%d",
				"%s",
				"%s",
				"%s"
			];
			$wpdb->insert($table_name,$data,$format);
			return $wpdb->insert_id;
	}

	private function getResponseCodeMessage($response_code)
	{
		$response_messages=[
			"ERROR"=>"Ocorreu um erro geral.",
			"APPROVED"=>"A transação foi aprovada.",
			"ANTIFRAUD_REJECTED"=>"A transação foi rejeitada pelo sistema anti fraude.",
			"PAYMENT_NETWORK_REJECTED"=>"A rede financeira rejeitou a transação.",
			"ENTITY_DECLINED"=>"A transação foi rejeitada pela rede financeira. Por favor, informe-se no seu banco ou na sua operadora de cartão de crédito.",
			"INTERNAL_PAYMENT_PROVIDER_ERROR"=>"Ocorreu um erro no sistema tentando processar o pagamento.",
			"INACTIVE_PAYMENT_PROVIDER"=>"O fornecedor de pagamentos não estava ativo.",
			"DIGITAL_CERTIFICATE_NOT_FOUND"=>"A rede financeira relatou um erro na autenticação.",
			"INVALID_EXPIRATION_DATE_OR_SECURITY_CODE"=>"O código de segurança ou a data de expiração estava inválido.",
			"INVALID_RESPONSE_PARTIAL_APPROVAL"=>"Tipo de resposta inválida. A entidade financeira aprovou parcialmente a transação e deve ser cancelado automaticamente pelo sistema.",
			"INSUFFICIENT_FUNDS"=>"A conta não tinha crédito suficiente.",
			"CREDIT_CARD_NOT_AUTHORIZED_FOR_INTERNET_TRANSACTIONS"=>"O cartão de crédito não estava autorizado para transações pela Internet.",
			"INVALID_TRANSACTION"=>"A rede financeira relatou que a transação foi inválida.",
			"INVALID_CARD"=>"O cartão é inválido.",
			"EXPIRED_CARD"=>"O cartão já expirou.",
			"RESTRICTED_CARD"=>"O cartão apresenta uma restrição.",
			"CONTACT_THE_ENTITY"=>"Você deve entrar em contato com o banco.",
			"REPEAT_TRANSACTION"=>"Deve-se repetir a transação.",
			"ENTITY_MESSAGING_ERROR"=>"A rede financeira relatou um erro de comunicações com o banco.",
			"BANK_UNREACHABLE"=>"O banco não se encontrava disponível.",
			"EXCEEDED_AMOUNT"=>"A transação excede um montante estabelecido pelo banco.",
			"NOT_ACCEPTED_TRANSACTION"=>"A transação não foi aceita pelo banco por algum motivo.",
			"ERROR_CONVERTING_TRANSACTION_AMOUNTS"=>"Ocorreu um erro convertendo os montantes para a moeda de pagamento.",
			"EXPIRED_TRANSACTION"=>"A transação expirou.",
			"PENDING_TRANSACTION_REVIEW"=>"A transação foi parada e deve ser revista, isto pode ocorrer por filtros de segurança.",
			"PENDING_TRANSACTION_CONFIRMATION"=>"A transação está pendente de confirmação.",
			"PENDING_TRANSACTION_TRANSMISSION"=>"A transação está pendente para ser transmitida para a rede financeira. Normalmente isto se aplica para transações com formas de pagamento em dinheiro.",
			"PAYMENT_NETWORK_BAD_RESPONSE"=>"A mensagem retornada pela rede financeira é inconsistente.",
			"PAYMENT_NETWORK_NO_CONNECTION"=>"Não foi possível realizar a conexão com a rede financeira.",
			"PAYMENT_NETWORK_NO_RESPONSE"=>"A rede financeira não respondeu.",
		];
		if (isset($response_messages[$response_code]))
			return $response_messages[$response_code];
		else
			return $response_messages["ERROR"];
	}
}
