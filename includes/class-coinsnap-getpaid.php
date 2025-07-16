<?php
if (!defined('ABSPATH')) {
    exit;
}

use Coinsnap\Client\Webhook;

class CoinsnapGP_Gateway extends GetPaid_Payment_Gateway {
    public const WEBHOOK_EVENTS = ['New', 'Expired', 'Settled', 'Processing'];

    public function __construct(){
        $this->id = 'coinsnap';
        $this->title = __('Bitcoin + Lightning', 'coinsnap-for-getpaid');
        $this->method_title = __('Coinsnap', 'coinsnap-for-getpaid');
        $this->supports = array('subscription', 'addons');
        add_action('init', array($this, 'process_webhook'));
        if (is_admin()) {
            add_action('admin_notices', array($this, 'coinsnap_notice'));
            add_action( 'admin_enqueue_scripts', [$this, 'enqueueAdminScripts'] );
            add_action( 'wp_ajax_coinsnapgp_connection_handler', [$this, 'coinsnapConnectionHandler'] );
            add_action( 'wp_ajax_coinsnapgp_btcpay_server_apiurl_handler', [$this, 'btcpayApiUrlHandler']);
        }
        
        // Adding template redirect handling for coinsnap-for-getpaid-btcpay-settings-callback.
        add_action( 'template_redirect', function(){
            global $wp_query;
            $notice = new \Coinsnap\Util\Notice();

            // Only continue on a coinsnap-for-getpaid-btcpay-settings-callback request.
            if (!isset( $wp_query->query_vars['coinsnap-for-getpaid-btcpay-settings-callback'])) {
                return;
            }
            
            if(!isset($wp_query->query_vars['coinsnap-for-getpaid-btcpay-nonce']) || !wp_verify_nonce($wp_query->query_vars['coinsnap-for-getpaid-btcpay-nonce'],'coinsnapgp-btcpay-nonce')){
                return;
            }

            $CoinsnapBTCPaySettingsUrl = admin_url('admin.php?page=wpinv-settings&tab=gateways&section=coinsnap');
            
            $btcpay_server_url = wpinv_get_option( 'btcpay_server_url');
            $btcpay_api_key  = filter_input(INPUT_POST,'apiKey',FILTER_SANITIZE_FULL_SPECIAL_CHARS);

            $client = new \Coinsnap\Client\Store($btcpay_server_url,$btcpay_api_key);
            if (count($client->getStores()) < 1) {
                $messageAbort = __('Error on verifiying redirected API Key with stored BTCPay Server url. Aborting API wizard. Please try again or continue with manual setup.', 'coinsnap-for-getpaid');
                $notice->addNotice('error', $messageAbort);
                wp_redirect($CoinsnapBTCPaySettingsUrl);
            }

            // Data does get submitted with url-encoded payload, so parse $_POST here.
            if (!empty($_POST)) {
                $data['apiKey'] = filter_input(INPUT_POST,'apiKey',FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? null;
                if(isset($_POST['permissions'])){
                    $permissions = array_map('sanitize_text_field', wp_unslash($_POST['permissions']));
                    if(is_array($permissions)){
                        foreach ($permissions as $key => $value) {
                            $data['permissions'][$key] = sanitize_text_field($permissions[$key] ?? null);
                        }
                    }
                }
            }

            if (isset($data['apiKey']) && isset($data['permissions'])) {

                $apiData = new \Coinsnap\Client\BTCPayApiAuthorization($data);
                if ($apiData->hasSingleStore() && $apiData->hasRequiredPermissions()) {

                    wpinv_update_option( 'btcpay_api_key', $apiData->getApiKey());
                    wpinv_update_option( 'btcpay_store_id', $apiData->getStoreID());
                    wpinv_update_option( 'coinsnap_provider', 'btcpay');

                    $notice->addNotice('success', __('Successfully received api key and store id from BTCPay Server API. Please finish setup by saving this settings form.', 'coinsnap-for-getpaid'));

                    // Register a webhook.

                    if ($this->registerWebhook($this->getApiUrl(), $apiData->getApiKey(), $apiData->getStoreID())) {
                        $messageWebhookSuccess = __( 'Successfully registered a new webhook on BTCPay Server.', 'coinsnap-for-getpaid' );
                        $notice->addNotice('success', $messageWebhookSuccess, true );
                    }
                    else {
                        $messageWebhookError = __( 'Could not register a new webhook on the store.', 'coinsnap-for-getpaid' );
                        $notice->addNotice('error', $messageWebhookError );
                    }
                    wp_redirect($CoinsnapBTCPaySettingsUrl);
                    exit();
                }
                else {
                    $notice->addNotice('error', __('Please make sure you only select one store on the BTCPay API authorization page.', 'coinsnap-for-getpaid'));
                    wp_redirect($CoinsnapBTCPaySettingsUrl);
                    exit();
                }
            }

            $notice->addNotice('error', __('Error processing the data from Coinsnap. Please try again.', 'coinsnap-for-getpaid'));
            wp_redirect($CoinsnapBTCPaySettingsUrl);
        });
        
        parent::__construct();
    }
    
    public function coinsnapConnectionHandler(){
        
        $_nonce = filter_input(INPUT_POST,'_wpnonce',FILTER_SANITIZE_STRING);
        
        if(empty($this->getApiUrl()) || empty($this->getApiKey())){
            $response = [
                    'result' => false,
                    'message' => __('GetPaid: empty gateway URL or API Key', 'coinsnap-for-getpaid')
            ];
            $this->sendJsonResponse($response);
        }
        
        $_provider = $this->get_payment_provider();
        $client = new \Coinsnap\Client\Invoice($this->getApiUrl(),$this->getApiKey());
        $store = new \Coinsnap\Client\Store($this->getApiUrl(),$this->getApiKey());
        $currency = wpinv_get_currency();
        
        
        if($_provider === 'btcpay'){
            try {
                $storePaymentMethods = $store->getStorePaymentMethods($this->getStoreId());

                if ($storePaymentMethods['code'] === 200) {
                    if($storePaymentMethods['result']['onchain'] && !$storePaymentMethods['result']['lightning']){
                        $checkInvoice = $client->checkPaymentData(0,$currency,'bitcoin','calculation');
                    }
                    elseif($storePaymentMethods['result']['lightning']){
                        $checkInvoice = $client->checkPaymentData(0,$currency,'lightning','calculation');
                    }
                }
            }
            catch (\Exception $e) {
                $response = [
                        'result' => false,
                        'message' => __('GetPaid: API connection is not established', 'coinsnap-for-getpaid')
                ];
                $this->sendJsonResponse($response);
            }
        }
        else {
            $checkInvoice = $client->checkPaymentData(0,$currency,'coinsnap','calculation');
        }
        
        if(isset($checkInvoice) && $checkInvoice['result']){
            $connectionData = __('Min order amount is', 'coinsnap-for-getpaid') .' '. $checkInvoice['min_value'].' '.$currency;
        }
        else {
            $connectionData = __('No payment method is configured', 'coinsnap-for-getpaid');
        }
        
        $_message_disconnected = ($_provider !== 'btcpay')? 
            __('GetPaid: Coinsnap server is disconnected', 'coinsnap-for-getpaid') :
            __('GetPaid: BTCPay server is disconnected', 'coinsnap-for-getpaid');
        $_message_connected = ($_provider !== 'btcpay')?
            __('GetPaid: Coinsnap server is connected', 'coinsnap-for-getpaid') : 
            __('GetPaid: BTCPay server is connected', 'coinsnap-for-getpaid');
        
        if( wp_verify_nonce($_nonce,'coinsnapgp-ajax-nonce') ){
            $response = ['result' => false,'message' => $_message_disconnected];

            try {
                $this_store = $store->getStore($this->getStoreId());
                
                if ($this_store['code'] !== 200) {
                    $this->sendJsonResponse($response);
                }
                
                $webhookExists = $this->webhookExists($this->getApiUrl(), $this->getApiKey(), $this->getStoreId());

                if($webhookExists) {
                    $response = ['result' => true,'message' => $_message_connected.' ('.$connectionData.')'];
                    $this->sendJsonResponse($response);
                }

                $webhook = $this->registerWebhook( $this->getApiUrl(), $this->getApiKey(), $this->getStoreId());
                $response['result'] = (bool)$webhook;
                $response['message'] = $webhook ? $_message_connected.' ('.$connectionData.')' : $_message_disconnected.' (Webhook)';
            }
            catch (\Exception $e) {
                $response['message'] =  __('GetPaid: API connection is not established', 'coinsnap-for-getpaid');
            }

            $this->sendJsonResponse($response);
        }      
    }

    private function sendJsonResponse(array $response): void {
        echo wp_json_encode($response);
        exit();
    }
    
    public function enqueueAdminScripts() {
	// Register the CSS file
	wp_register_style( 'coinsnapgp-admin-styles', COINSNAPGP_URL . 'assets/css/backend-style.css', array(), COINSNAPGP_VERSION );
	// Enqueue the CSS file
	wp_enqueue_style( 'coinsnapgp-admin-styles' );
        //  Enqueue admin fileds handler script
        wp_enqueue_script('coinsnapgp-admin-fields', COINSNAPGP_URL . 'assets/js/adminFields.js',[ 'jquery' ],COINSNAPGP_VERSION,true);
        wp_enqueue_script('coinsnapgp-connection-check', COINSNAPGP_URL . 'assets/js/connectionCheck.js',[ 'jquery' ],COINSNAPGP_VERSION,true);
        wp_localize_script('coinsnapgp-connection-check', 'coinsnapgp_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'  => wp_create_nonce( 'coinsnapgp-ajax-nonce' )
        ));
    }
    
    /**
     * Handles the BTCPay server AJAX callback from the settings form.
     */
    public function btcpayApiUrlHandler() {
        $_nonce = filter_input(INPUT_POST,'apiNonce',FILTER_SANITIZE_STRING);
        if ( !wp_verify_nonce( $_nonce, 'coinsnapgp-ajax-nonce' ) ) {
            wp_die('Unauthorized!', '', ['response' => 401]);
        }
        
        if ( current_user_can( 'manage_options' ) ) {
            $host = filter_var(filter_input(INPUT_POST,'host',FILTER_SANITIZE_STRING), FILTER_VALIDATE_URL);

            if ($host === false || (substr( $host, 0, 7 ) !== "http://" && substr( $host, 0, 8 ) !== "https://")) {
                wp_send_json_error("Error validating BTCPayServer URL.");
            }

            $permissions = array_merge([
		'btcpay.store.canviewinvoices',
		'btcpay.store.cancreateinvoice',
		'btcpay.store.canviewstoresettings',
		'btcpay.store.canmodifyinvoices'
            ],
            [
		'btcpay.store.cancreatenonapprovedpullpayments',
		'btcpay.store.webhooks.canmodifywebhooks',
            ]);

            try {
		// Create the redirect url to BTCPay instance.
		$url = \Coinsnap\Client\BTCPayApiKey::getAuthorizeUrl(
                    $host,
                    $permissions,
                    'GetPaid',
                    true,
                    true,
                    home_url('?coinsnap-for-getpaid-btcpay-settings-callback'),
                    null
		);

		// Store the host to options before we leave the site.
		wpinv_update_option('btcpay_server_url', $host);

		// Return the redirect url.
		wp_send_json_success(['url' => $url]);
            }
            
            catch (\Throwable $e) {
                
            }
	}
        wp_send_json_error("Error processing Ajax request.");
    }
    
    public function coinsnap_notice(){
        
        $notice = new \Coinsnap\Util\Notice();
        $notice->showNotices();
        
        $page = (filter_input(INPUT_GET,'page',FILTER_SANITIZE_FULL_SPECIAL_CHARS ))? filter_input(INPUT_GET,'page',FILTER_SANITIZE_FULL_SPECIAL_CHARS ) : '';
        $tab = (filter_input(INPUT_GET,'tab',FILTER_SANITIZE_FULL_SPECIAL_CHARS ))? filter_input(INPUT_GET,'tab',FILTER_SANITIZE_FULL_SPECIAL_CHARS ) : '';
        
        if($page === 'wpinv-settings' && $tab === 'gateways' && !isset($_COOKIE['coinsnap_notices'])){
            $coinsnap_url = $this->getApiUrl();
            $coinsnap_api_key = $this->getApiKey();
            $coinsnap_store_id = $this->getStoreId();
                
            if(!isset($coinsnap_store_id) || empty($coinsnap_store_id)){
                    echo '<div class="notice notice-error"><p>';
                    esc_html_e('GetPaid: Coinsnap Store ID is not set', 'coinsnap-for-getpaid');
                    echo '</p></div>';
            }

            if(!isset($coinsnap_api_key) || empty($coinsnap_api_key)){
                    echo '<div class="notice notice-error"><p>';
                    esc_html_e('GetPaid: Coinsnap API Key is not set', 'coinsnap-for-getpaid');
                    echo '</p></div>';
            }
                
            if(!empty($coinsnap_api_key) && !empty($coinsnap_store_id)){
                $client = new \Coinsnap\Client\Store($coinsnap_url, $coinsnap_api_key);
                $store = $client->getStore($coinsnap_store_id);
                if ($store['code'] === 200){
                        echo '<div class="notice notice-success"><p>';
                        esc_html_e('GetPaid: Established connection to Coinsnap Server', 'coinsnap-for-getpaid');
                        echo '</p></div>';
                        
                        if ( !$this->webhookExists( $coinsnap_url, $coinsnap_api_key, $coinsnap_store_id ) ) {
                            if ( ! $this->registerWebhook( $coinsnap_url, $coinsnap_api_key, $coinsnap_store_id ) ) {
                                echo '<div class="notice notice-error"><p>';
                                esc_html_e('GetPaid: Unable to create webhook on Coinsnap Server', 'coinsnap-for-getpaid');
                                echo '</p></div>';
                            }
                            else {
                                echo '<div class="notice notice-success"><p>';
                                esc_html_e('GetPaid: Successfully registered a new webhook on Coinsnap Server', 'coinsnap-for-getpaid');
                                echo '</p></div>';
                            }
                        }
                        else {
                            echo '<div class="notice notice-info"><p>';
                            esc_html_e('GetPaid: Webhook already exists, skipping webhook creation', 'coinsnap-for-getpaid');
                            echo '</p></div>';
                        }
                }
                else {
                        echo '<div class="notice notice-error"><p>';
                        esc_html_e('GetPaid: Coinsnap connection error:', 'coinsnap-for-getpaid');
                        echo esc_html($store['result']['message']);
                        echo '</p></div>';
                }
            }
        }
    }

    public function admin_settings($admin_settings){

        $statuses = wpinv_get_invoice_statuses(true, true, $this);
        $admin_settings['coinsnap_desc']['std']  = __('Pay using Bitcoin + Lightning', 'coinsnap-for-getpaid');
        
        $admin_settings['coinsnap_provider'] = array(
                    'id'   => 'coinsnap_provider',
                    'name' => __( 'Payment provider', 'coinsnap-for-getpaid' ),
                    'desc' => __( 'Select payment provider', 'coinsnap-for-getpaid' ),
                    'type'        => 'select',
                    'options'   => [
                        'coinsnap'  => 'Coinsnap',
                        'btcpay'    => 'BTCPay Server'
                    ]
         );

        //  Coinsnap fields
        $admin_settings['coinsnap_store_id'] = array(
            'id'   => 'coinsnap_store_id',
            'name' => __('Store ID', 'coinsnap-for-getpaid'),
            'desc' => __('Enter Store ID', 'coinsnap-for-getpaid'),
            'class'=> 'coinsnap',
            'type' => 'text',
        );
        $admin_settings['coinsnap_api_key'] = array(
            'id'   => 'coinsnap_api_key',
            'name' => __('API Key', 'coinsnap-for-getpaid'),
            'desc' => __('Enter API Key', 'coinsnap-for-getpaid'),
            'class'=> 'coinsnap',
            'type' => 'text',
        );
        
        //  BTCPay fields
        $admin_settings['btcpay_server_url'] = array(
                    'id' => 'btcpay_server_url',
                    'name'       => __( 'BTCPay server URL*', 'coinsnap-for-getpaid' ),
                    'type'        => 'text',
                    'desc'        => __( '<a href="#" class="btcpay-apikey-link">Check connection</a>', 'coinsnap-for-getpaid' ).'<br/><br/><button class="button btcpay-apikey-link" id="btcpay_wizard_button" target="_blank">'. __('Generate API key','coinsnap-for-getpaid').'</button>',
                    'std'     => '',
                'size' => 'regular',
                    'class' => 'btcpay'
                );
            
        $admin_settings['btcpay_store_id'] = array(
                    'id'   => 'btcpay_store_id',
                    'name' => __( 'Store ID*', 'coinsnap-for-getpaid' ),
                    'desc' => __( 'Enter Store ID', 'coinsnap-for-getpaid' ),
                    'type' => 'text',
                    'std'     => '',
                'size' => 'regular',
                    'class' => 'btcpay'
                );
        $admin_settings['btcpay_api_key'] = array(
                    'id'   => 'btcpay_api_key',
                    'name' => __( 'API Key*', 'coinsnap-for-getpaid' ),
                    'desc' => __( 'Enter API Key', 'coinsnap-for-getpaid' ),
                    'type' => 'text',
                    'std'     => '',
                'size' => 'regular',
                    'class' => 'btcpay'
                );
        
        $admin_settings['coinsnap_autoredirect'] = array(
            'id'   => 'coinsnap_autoredirect',
            'name' => __('Redirect after payment', 'coinsnap-for-getpaid'),
            'desc' => __('Redirect after payment to Thank you page automatically', 'coinsnap-for-getpaid'),
            'type' => 'checkbox',
            'value'=> 1,
            'std' => 1
        );
        $admin_settings['coinsnap_expired_status'] = array(
            'id'   => 'coinsnap_expired_status',
            'name' => __('Expired Status', 'coinsnap-for-getpaid'),
            'desc' => __('Select Expired Status', 'coinsnap-for-getpaid'),
            'type'        => 'select',
            'std'         => 'wpi-cancelled',
            'options'     => $statuses,
        );
        $admin_settings['coinsnap_settled_status'] = array(
            'id'   => 'coinsnap_settled_status',
            'name' => __('Settled Status', 'coinsnap-for-getpaid'),
            'desc' => __('Select Settled Status', 'coinsnap-for-getpaid'),
            'type'        => 'select',
            'std'         => 'publish',
            'options'     => $statuses,
        );
        $admin_settings['coinsnap_processing_status'] = array(
            'id'   => 'coinsnap_processing_status',
            'name' => __('Processing Status', 'coinsnap-for-getpaid'),
            'desc' => __('Select Processing Status', 'coinsnap-for-getpaid'),
            'type'        => 'select',
            'std'         => 'wpi-processing',
            'options'     => $statuses,
        );

        return $admin_settings;
    }

    public function process_webhook(){

        if (null === filter_input(INPUT_GET,'getpaid-listener',FILTER_SANITIZE_FULL_SPECIAL_CHARS) || filter_input(INPUT_GET,'getpaid-listener',FILTER_SANITIZE_FULL_SPECIAL_CHARS) !== 'coinsnap') {
            return;
        }
        
        try {
            // First check if we have any input
            $rawPostData = file_get_contents("php://input");
            if (!$rawPostData) {
                    wp_die('No raw post data received', '', ['response' => 400]);
            }

            // Get headers and check for signature
            $headers = getallheaders();
            $signature = null; $payloadKey = null;
            $_provider = ($this->get_payment_provider() === 'btcpay')? 'btcpay' : 'coinsnap';
                
            foreach ($headers as $key => $value) {
                if ((strtolower($key) === 'x-coinsnap-sig' && $_provider === 'coinsnap') || (strtolower($key) === 'btcpay-sig' && $_provider === 'btcpay')) {
                        $signature = $value;
                        $payloadKey = strtolower($key);
                }
            }

            // Handle missing or invalid signature
            if (!isset($signature)) {
                wp_die('Authentication required', '', ['response' => 401]);
            }

            // Validate the signature
            $webhook = get_option( 'wpinv_settings_coinsnap_webhook');
            if (!Webhook::isIncomingWebhookRequestValid($rawPostData, $signature, $webhook['secret'])) {
                wp_die('Invalid authentication signature', '', ['response' => 401]);
            }

            // Parse the JSON payload
            $postData = json_decode($rawPostData, false, 512, JSON_THROW_ON_ERROR);

            if (!isset($postData->invoiceId)) {
                wp_die('No Coinsnap invoiceId provided', '', ['response' => 400]);
            }
            
            $invoice_id = esc_html($postData->invoiceId);
            
            if(strpos($invoice_id,'test_') !== false){
                wp_die('Successful webhook test', '', ['response' => 200]);
            }
            
            $client = new \Coinsnap\Client\Invoice($this->getApiUrl(), $this->getApiKey());
            $csinvoice = $client->getInvoice($this->getStoreId(), $invoice_id);
            $status = $csinvoice->getData()['status'];
            $order_id = $csinvoice->getData()['orderId'];
            
            $order_status = 'pending';
            if ($status == 'Expired'){ $order_status = give_get_option('coinsnap_expired_status'); }
            else if ($status == 'Processing'){ $order_status = give_get_option('coinsnap_processing_status'); }
            else if ($status == 'Settled'){ $order_status = give_get_option('coinsnap_settled_status'); }
            
            $order_status = 'wpi-pending';

            if ($status == 'Expired') {
                $order_status = wpinv_get_option('coinsnap_expired_status');
            }
            elseif ($status == 'Processing') {
                $order_status = wpinv_get_option('coinsnap_processing_status');
            }
            elseif ($status == 'Settled') {
                $order_status = wpinv_get_option('coinsnap_settled_status');
            }

            if (isset($order_id)) {
                $invoice = wpinv_get_invoice($order_id);
                if ($invoice && $this->id == $invoice->get_gateway()) {
                    $invoice->set_status($order_status);
                    $invoice->add_note(esc_html('Payment transaction - ' . $status, 'invoicing'), false, false, true);
                    $invoice->save();
                }
            }

            echo "OK";
            exit;
        }
        catch (JsonException $e) {
            wp_die('Invalid JSON payload', '', ['response' => 400]);
        }
        catch (\Throwable $e) {
            wp_die('Internal server error', '', ['response' => 500]);
        }
    }
    
    public function coinsnapgp_amount_validation( $amount, $currency ) {
        $client =new \Coinsnap\Client\Invoice($this->getApiUrl(), $this->getApiKey());
        $store = new \Coinsnap\Client\Store($this->getApiUrl(), $this->getApiKey());
        
        try {
            $this_store = $store->getStore($this->getStoreId());
            $_provider = $this->get_payment_provider();
            if($_provider === 'btcpay'){
                try {
                    $storePaymentMethods = $store->getStorePaymentMethods($this->getStoreId());

                    if ($storePaymentMethods['code'] === 200) {
                        if(!$storePaymentMethods['result']['onchain'] && !$storePaymentMethods['result']['lightning']){
                            $errorMessage = __( 'No payment method is configured on BTCPay server', 'coinsnap-for-getpaid' );
                            $checkInvoice = array('result' => false,'error' => esc_html($errorMessage));
                        }
                    }
                    else {
                        $errorMessage = __( 'Error store loading. Wrong or empty Store ID', 'coinsnap-for-getpaid' );
                        $checkInvoice = array('result' => false,'error' => esc_html($errorMessage));
                    }

                    if($storePaymentMethods['result']['onchain'] && !$storePaymentMethods['result']['lightning']){
                        $checkInvoice = $client->checkPaymentData((float)$amount,strtoupper( $currency ),'bitcoin');
                    }
                    elseif($storePaymentMethods['result']['lightning']){
                        $checkInvoice = $client->checkPaymentData((float)$amount,strtoupper( $currency ),'lightning');
                    }
                }
                catch (\Throwable $e){
                    $errorMessage = __( 'API connection is not established', 'coinsnap-for-getpaid' );
                    $checkInvoice = array('result' => false,'error' => esc_html($errorMessage));
                }
            }
            else {
                $checkInvoice = $client->checkPaymentData((float)$amount,strtoupper( $currency ));
            }
        }
        catch (\Throwable $e){
            $errorMessage = __( 'API connection is not established', 'coinsnap-for-getpaid' );
            $checkInvoice = array('result' => false,'error' => esc_html($errorMessage));
        }
        return $checkInvoice;
    }

    public function process_payment($invoice, $submission_data, $submission){

        if (!$this->webhookExists($this->getApiUrl(), $this->getApiKey(), $this->getStoreId())) {
            if (!$this->registerWebhook($this->getApiUrl(), $this->getApiKey(), $this->getStoreId())) {
                wpinv_set_error( 'Connection error', esc_html__('Unable to set Webhook URL.', 'coinsnap-for-getpaid') );
                wpinv_send_back_to_checkout( $invoice );
            }
        }
        
        $amount =  round($invoice->get_total(), 2);
        $currency = $invoice->get_currency();
        
        $client = new \Coinsnap\Client\Invoice($this->getApiUrl(), $this->getApiKey());
        $checkInvoice = $this->coinsnapgp_amount_validation($amount,strtoupper( $currency ));
                
        if($checkInvoice['result'] === true){

            $redirectUrl = esc_url_raw($this->get_return_url($invoice));


            $buyerEmail = $invoice->get_email();
            $buyerName = $invoice->get_first_name() . ' ' . $invoice->get_last_name();

            $metadata = [];
            $metadata['orderNumber'] = $invoice->get_number();
            $metadata['customerName'] = $buyerName;

            $redirectAutomatically = (wpinv_get_option( 'coinsnap_autoredirect') > 0)? true : false;
            $walletMessage = '';

            $camount = \Coinsnap\Util\PreciseNumber::parseFloat($amount, 2);

            $csinvoice = $client->createInvoice(
                $this->getStoreId(),
                $currency,
                $camount,
                $invoice->get_number(),
                $buyerEmail,
                $buyerName,
                $redirectUrl,
                COINSNAPGP_REFERRAL_CODE,
                $metadata,
                $redirectAutomatically,
                $walletMessage
            );

            $payurl = $csinvoice->getData()['checkoutLink'];
            wp_redirect($payurl);
        }
        else {
            
            if($checkInvoice['error'] === 'currencyError'){
                $errorMessage = sprintf( 
                /* translators: 1: Currency */
                __( 'Currency %1$s is not supported by Coinsnap', 'coinsnap-for-getpaid' ), strtoupper( $currency ));
            }      
            elseif($checkInvoice['error'] === 'amountError'){
                $errorMessage = sprintf( 
                /* translators: 1: Amount, 2: Currency */
                __( 'Invoice amount cannot be less than %1$s %2$s', 'coinsnap-for-getpaid' ), $checkInvoice['min_value'], strtoupper( $currency ));
            }
            else {
                $errorMessage = $checkInvoice['error'];
            }
            wpinv_set_error( 'Payment error', $errorMessage );
            wpinv_send_back_to_checkout( $invoice );
        }
        exit;
    }



    public function get_webhook_url()
    {
        return esc_url_raw(add_query_arg(array('getpaid-listener' => 'coinsnap'), home_url('index.php')));
    }
    
    private function get_payment_provider() {
        return (wpinv_get_option( 'coinsnap_provider') === 'btcpay')? 'btcpay' : 'coinsnap';
    }

    public function getApiKey() {
        return ($this->get_payment_provider() === 'btcpay')? wpinv_get_option( 'btcpay_api_key') : wpinv_get_option( 'coinsnap_api_key', '' );
    }
    
    public function getStoreId() {
	return ($this->get_payment_provider() === 'btcpay')? wpinv_get_option( 'btcpay_store_id') : wpinv_get_option( 'coinsnap_store_id', '' );
    }
    
    public function getApiUrl() {
        return ($this->get_payment_provider() === 'btcpay')? wpinv_get_option( 'btcpay_server_url') : COINSNAP_SERVER_URL;
    }

    public function webhookExists(string $apiUrl, string $apiKey, string $storeId): bool {
	$whClient = new Webhook( $apiUrl, $apiKey );
	if ($storedWebhook = get_option( 'wpinv_settings_coinsnap_webhook')) {
            
            try {
		$existingWebhook = $whClient->getWebhook( $storeId, $storedWebhook['id'] );
                
                if($existingWebhook->getData()['id'] === $storedWebhook['id'] && strpos( $existingWebhook->getData()['url'], $storedWebhook['url'] ) !== false){
                    return true;
		}
            }
            catch (\Throwable $e) {
		$errorMessage = __( 'Error fetching existing Webhook. Message: ', 'coinsnap-for-getpaid' ).$e->getMessage();
            }
	}
        try {
            $storeWebhooks = $whClient->getWebhooks( $storeId );
            foreach($storeWebhooks as $webhook){
                if(strpos( $webhook->getData()['url'], $this->get_webhook_url() ) !== false){
                    $whClient->deleteWebhook( $storeId, $webhook->getData()['id'] );
                }
            }
        }
        catch (\Throwable $e) {
            $errorMessage = sprintf( 
                /* translators: 1: StoreId */
                __( 'Error fetching webhooks for store ID %1$s Message: ', 'coinsnap-for-getpaid' ), $storeId).$e->getMessage();
        }
        
	return false;
    }
    
    public function registerWebhook(string $apiUrl, $apiKey, $storeId){
        try {
            $whClient = new Webhook( $apiUrl, $apiKey );
            $webhook = $whClient->createWebhook(
                $storeId,   //$storeId
		$this->get_webhook_url(), //$url
		self::WEBHOOK_EVENTS,   //$specificEvents
		null    //$secret
            );

            update_option(
                'wpinv_settings_coinsnap_webhook',
                [
                    'id' => $webhook->getData()['id'],
                    'secret' => $webhook->getData()['secret'],
                    'url' => $webhook->getData()['url']
                ]
            );

            return $webhook;
                        
	}
        catch (\Throwable $e) {
            $errorMessage = __('Error creating a new webhook on Coinsnap instance: ', 'coinsnap-for-getpaid' ) . $e->getMessage();
            throw new PaymentGatewayException(esc_html($errorMessage));
	}

	return null;
    }
}

add_filter('getpaid_default_gateways', 'coinsnapgp_gateway_register');
function coinsnapgp_gateway_register($gateways)
{
    $gateways['coinsnap'] = 'CoinsnapGP_Gateway';
    return $gateways;
}
