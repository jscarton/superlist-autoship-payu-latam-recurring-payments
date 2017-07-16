<?php

//require payu sdk
require_once SUPERLIST_PAYU_ROOT.'includes/payu/lib/PayU.php';

class SuperlistPayuCreditCard extends SuperlistPayuBase
{
    public function __construct()
    {
        parent::__construct([]);
        $settings=new SuperlistPayuSettings();
        $this->plugin_settings=$settings->getPluginSettings();
        $this->endpoints=$settings->getEndpoints($this->plugin_settings->sandbox_mode);
        $this->credentials=$settings->getPayuCredentials($this->plugin_settings->sandbox_mode);
    }

    public function createCreditCard($CUSTOMER_ID,$PAYER_NAME,$CREDIT_CARD_NUMBER,$CREDIT_CARD_EXPIRATION_DATE,$PAYMENT_METHOD,$PAYER_DNI)
    {        
        //set payu api endpoints
        Environment::setPaymentsCustomUrl($this->endpoints->payments_custom_url);
        Environment::setReportsCustomUrl($this->endpoints->reports_custom_url); 
        Environment::setSubscriptionsCustomUrl($this->endpoints->subscription_custom_url); 
        //set payu credentials
        PayU::$apiKey = $this->credentials->api_key; //Ingrese aquí su propio apiKey.
        PayU::$apiLogin = $this->credentials->api_login; //Ingrese aquí su propio apiLogin.
        PayU::$merchantId = $this->credentials->merchant_id; //Ingrese aquí su Id de Comercio.
        PayU::$language = SupportedLanguages::PT; //Seleccione el idioma.
        PayU::$isTest = ($this->plugin_settings->test_mode=='yes')?true:false; //Dejarlo True cuando sean pruebas.
        $parameters = [
            //Ingrese aquí el nombre del pagador.
            PayUParameters::PAYER_NAME => $PAYER_NAME,
            //Ingrese aquí el identificador del pagador.
            PayUParameters::PAYER_ID => $CUSTOMER_ID,
            //Ingrese aquí el documento de identificación del comprador.
            PayUParameters::PAYER_DNI => $PAYER_DNI,
            //Ingrese aquí el número de la tarjeta de crédito
            PayUParameters::CREDIT_CARD_NUMBER => $CREDIT_CARD_NUMBER,
            //Ingrese aquí la fecha de vencimiento de la tarjeta de crédito
            PayUParameters::CREDIT_CARD_EXPIRATION_DATE => $CREDIT_CARD_EXPIRATION_DATE,
            //Ingrese aquí el nombre de la tarjeta de crédito
            PayUParameters::PAYMENT_METHOD => $PAYMENT_METHOD
        ];
            
        $response = PayUTokens::create($parameters);   
        //$response= PayUPayments::doPing();
        if($response){
            return $response;
        }
    }

    function chargeCreditCard($order_reference, $order_value, $order_description,$buyer_name,$buyer_email,$buyer_dni, $buyer_street,$buyer_street_2,$buyer_city, $buyer_state,$buyer_postal_code,$buyer_phone,$payer_name,$payer_token,$intallments_number, $payment_method)
    {
        //set payu api endpoints
        Environment::setPaymentsCustomUrl($this->endpoints->payments_custom_url);
        Environment::setReportsCustomUrl($this->endpoints->reports_custom_url); 
        Environment::setSubscriptionsCustomUrl($this->endpoints->subscription_custom_url); 
        //set payu credentials
        PayU::$apiKey = $this->credentials->api_key; //Ingrese aquí su propio apiKey.
        PayU::$apiLogin = $this->credentials->api_login; //Ingrese aquí su propio apiLogin.
        PayU::$merchantId = $this->credentials->merchant_id; //Ingrese aquí su Id de Comercio.
        PayU::$language = SupportedLanguages::PT; //Seleccione el idioma.
        PayU::$isTest = ($this->plugin_settings->test_mode=='yes')?true:false; //Dejarlo True cuando sean pruebas. 

        $parameters = [
            //Ingrese aquí el identificador de la cuenta.
            PayUParameters::ACCOUNT_ID => $this->credentials->account_id,
            //Ingrese aquí el código de referencia.
            PayUParameters::REFERENCE_CODE => $order_reference,
            //Ingrese aquí la descripción.
            PayUParameters::DESCRIPTION => $order_description,
            
            // -- Valores --
            //Ingrese aquí el valor.        
            PayUParameters::VALUE => $order_value,
            //Ingrese aquí la moneda.
            PayUParameters::CURRENCY => "BRL",
            
            // -- Comprador 
            //Ingrese aquí el nombre del comprador.
            PayUParameters::BUYER_NAME => $buyer_name,
            //Ingrese aquí el email del comprador.
            PayUParameters::BUYER_EMAIL => $buyer_email,
            //Ingrese aquí el teléfono de contacto del comprador.
            PayUParameters::BUYER_CONTACT_PHONE => $buyer_phone,
            //Ingrese aquí el documento de contacto del comprador.
            PayUParameters::BUYER_DNI => $buyer_dni,
            // or 
            //PayUParameters::BUYER_CNPJ => "32593371000110",

            
            //Ingrese aquí la dirección del comprador.
            PayUParameters::BUYER_STREET => $buyer_street,
            PayUParameters::BUYER_STREET_2 => $buyer_street_2,
            PayUParameters::BUYER_CITY => $buyer_city,
            PayUParameters::BUYER_STATE => $buyer_state,
            PayUParameters::BUYER_COUNTRY => "BR",
            PayUParameters::BUYER_POSTAL_CODE => $buyer_postal_code,
            PayUParameters::BUYER_PHONE => $buyer_phone,
            
            // -- pagador --
            //Ingrese aquí el nombre del pagador.
            PayUParameters::PAYER_NAME => $payer_name,  
            
            // DATOS DEL TOKEN      
            PayUParameters::TOKEN_ID => $payer_token,
                
            //Ingrese aquí el nombre de la tarjeta de crédito
            //"VISA"||"MASTERCARD"||"AMEX"||"DINERS"||"ELO"||"HIPERCARD"
            PayUParameters::PAYMENT_METHOD => $payment_method,
            
            //Ingrese aquí el número de cuotas.
            PayUParameters::INSTALLMENTS_NUMBER => $intallments_number,
            //Ingrese aquí el nombre del pais.
            PayUParameters::COUNTRY => PayUCountries::BR, 
            
            //IP del pagadador
            PayUParameters::IP_ADDRESS => $this->getUserIP(),  
        ];

        //$response = PayUPayments::doAuthorization($parameters);
        $response=PayUPayments::doAuthorizationAndCapture($parameters);

        if ($response) {
            return $response;
        }
 

    }

    public function ping()
    {
            //set payu api endpoints
        Environment::setPaymentsCustomUrl($this->endpoints->payments_custom_url);
        Environment::setReportsCustomUrl($this->endpoints->reports_custom_url); 
        Environment::setSubscriptionsCustomUrl($this->endpoints->subscription_custom_url); 
        //set payu credentials
        PayU::$apiKey = $this->credentials->api_key; //Ingrese aquí su propio apiKey.
        PayU::$apiLogin = $this->credentials->api_login; //Ingrese aquí su propio apiLogin.
        PayU::$merchantId = $this->credentials->merchant_id; //Ingrese aquí su Id de Comercio.
        PayU::$language = SupportedLanguages::PT; //Seleccione el idioma.
        PayU::$isTest = ($this->plugin_settings->test_mode=='yes')?true:false; //Dejarlo True cuando sean pruebas. 
            $response = PayUReports::doPing();
            return $response;
    }

    private function getUserIP()
    {
        if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
            //check ip from share internet
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            //to check ip is pass from proxy
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    public function deleteCreditCard($credit_card_id) {
        return true;
    }

    public function dumpIt()
    {
        $arrDump=[
            "settings"=>$this->plugin_settings->dump(),
            "endpoints"=>$this->endpoints->dump(),
            "credentials"=>$this->credentials->dump()
        ];
        return json_encode($arrDump);
    }

    public function retrieveCardDataByUserId($user_id)
    {
        global $wpdb;
        $table_name= $wpdb->prefix . SuperlistPayuSetup::PREFIX."creditcards";
        $rs=$wpdb->get_row( "SELECT * FROM $table_name WHERE payer_id = $user_id");
        if ($rs)
            return new SuperlistPayuBase(['data'=>$rs]);
        return NULL;
    }
}