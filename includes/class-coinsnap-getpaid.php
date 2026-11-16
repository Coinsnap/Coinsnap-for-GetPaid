<?php
if (!defined('ABSPATH')) {
    exit;
}

use Coinsnap\Client\Webhook;

class CoinsnapGP_Gateway extends GetPaid_Payment_Gateway {
    public const COINSNAP_WEBHOOK_EVENTS = ['New','Expired','Settled','Processing'];
    public const BTCPAY_WEBHOOK_EVENTS = ['InvoiceCreated','InvoiceExpired','InvoiceSettled','InvoiceProcessing'];

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
            add_action( 'wpinv_coinsnap_api_key', [$this, 'renderApiKeyField']);
            add_action( 'wpinv_btcpay_api_key', [$this, 'renderApiKeyField']);
            add_filter( 'wpinv_settings_gateways-coinsnap_sanitize', [$this, 'keepStoredApiKeys']);
        }
        
        // Adding template redirect handling for coinsnap-for-getpaid-btcpay-settings-callback.
        add_action( 'template_redirect', function(){
            global $wp_query;
            $notice = new \Coinsnap\Util\Notice();

            // Only continue on a coinsnap-for-getpaid-btcpay-settings-callback request.
            if (!isset( $wp_query->query_vars['coinsnap-for-getpaid-btcpay-settings-callback'])) {
                return;
            }
            
            $CoinsnapBTCPaySettingsUrl = admin_url('admin.php?page=wpinv-settings&tab=gateways&section=coinsnap');

            // BTCPay posts here from the admin's browser; only the state handed out by btcpayApiUrlHandler() is accepted, once.
            $state = isset($_GET['state']) ? preg_replace('/[^A-Za-z0-9]/', '', (string) wp_unslash($_GET['state'])) : '';
            $stateHost = ($state !== '') ? get_transient('coinsnapgp_btcpay_state_' . $state) : false;
            if ($state !== '') {
                delete_transient('coinsnapgp_btcpay_state_' . $state);
            }
            $btcpay_server_url = is_string($stateHost) ? $stateHost : '';
            if ($btcpay_server_url === '') {
                status_header(403);
                exit();
            }

            $data = [];
            $data['apiKey'] = isset($_POST['apiKey']) ? sanitize_text_field(wp_unslash($_POST['apiKey'])) : null;
            if (isset($_POST['permissions']) && is_array($_POST['permissions'])) {
                foreach (array_map('sanitize_text_field', wp_unslash($_POST['permissions'])) as $key => $value) {
                    $data['permissions'][$key] = $value;
                }
            }

            try {
                $keyWorks = !empty($data['apiKey']) && count((new \Coinsnap\Client\Store($btcpay_server_url, $data['apiKey']))->getStores()) > 0;
            } catch (\Throwable $e) {
                $keyWorks = false;
            }
            if (!$keyWorks) {
                $notice->addNotice('error', __('Error on verifiying redirected API Key with stored BTCPay Server url. Aborting API wizard. Please try again or continue with manual setup.', 'coinsnap-for-getpaid'));
                wp_redirect($CoinsnapBTCPaySettingsUrl);
                exit();
            }

            if (isset($data['apiKey']) && isset($data['permissions'])) {

                $apiData = new \Coinsnap\Client\BTCPayApiAuthorization($data);
                if ($apiData->hasSingleStore() && $apiData->hasRequiredPermissions()) {

                    wpinv_update_option( 'btcpay_server_url', $btcpay_server_url);
                    wpinv_update_option( 'btcpay_api_key', $apiData->getApiKey());
                    wpinv_update_option( 'btcpay_store_id', $apiData->getStoreID());
                    wpinv_update_option( 'coinsnap_provider', 'btcpay');

                    $notice->addNotice('success', __('Successfully received api key and store id from BTCPay Server API. Please finish setup by saving this settings form.', 'coinsnap-for-getpaid'));

                    // Register a webhook.

                    if ($this->registerWebhook($btcpay_server_url, $apiData->getApiKey(), $apiData->getStoreID())) {
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
            exit();
        });
        
        parent::__construct();
    }
    
    public function coinsnapConnectionHandler(){
        
        $_nonce = filter_input(INPUT_POST,'_wpnonce',FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        // Before any API call: it can re-register the webhook and replace its secret.
        if (!wp_verify_nonce((string) $_nonce, 'coinsnapgp-ajax-nonce') || !current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Not allowed'], 403);
        }
        
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
        $_nonce = filter_input(INPUT_POST,'apiNonce',FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        if ( !wp_verify_nonce( $_nonce, 'coinsnapgp-ajax-nonce' ) ) {
            wp_die('Unauthorized!', '', ['response' => 401]);
        }
        
        if ( current_user_can( 'manage_options' ) ) {
            $host = filter_var(trim((string) filter_input(INPUT_POST,'host',FILTER_UNSAFE_RAW)), FILTER_VALIDATE_URL);

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

            $state = wp_generate_password(32, false);
            set_transient('coinsnapgp_btcpay_state_' . $state, $host, 15 * MINUTE_IN_SECONDS);

            try {
		// Create the redirect url to BTCPay instance.
		$url = \Coinsnap\Client\BTCPayApiKey::getAuthorizeUrl(
                    $host,
                    $permissions,
                    'GetPaid',
                    true,
                    true,
                    add_query_arg('state', $state, home_url('?coinsnap-for-getpaid-btcpay-settings-callback')),
                    null
		);

		// The host is saved only by the callback, once a key for it is verified; the stored key never goes to an unverified host.

		// Return the redirect url.
		wp_send_json_success(['url' => $url]);
            }
            
            catch (\Throwable $e) {
                wp_send_json_error("Error processing Ajax request.");
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
                try {
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
                            esc_html_e('GetPaid: Coinsnap connection error. Check the Store ID and API key.', 'coinsnap-for-getpaid');
                            echo '</p></div>';
                    }
                }
                catch (\Throwable $e) {
                    echo '<div class="notice notice-error"><p>';
                    esc_html_e('GetPaid: API connection is not established', 'coinsnap-for-getpaid');
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
            'type' => 'hook',
        );
        
        //  BTCPay fields
        $admin_settings['btcpay_server_url'] = array(
                    'id' => 'btcpay_server_url',
                    'name'       => __( 'BTCPay server URL*', 'coinsnap-for-getpaid' ),
                    'type'        => 'text',
                    'desc'        => __( '<a href="#" class="btcpay-apikey-link">Check connection</a>', 'coinsnap-for-getpaid' ).'<br/><br/><button class="button btcpay-apikey-link" id="btcpay_wizard_button" type="button">'. __('Generate API key','coinsnap-for-getpaid').'</button>',
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
                    'type' => 'hook',
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
        
        $admin_settings['coinsnap_returnurl'] = array(
            'id'   => 'coinsnap_returnurl',
            'name' => __('Return URL after payment', 'coinsnap-for-getpaid'),
            'desc' => __('Custom return URL after successful payment (default URL if blank)', 'coinsnap-for-getpaid'),
            'type' => 'text',
            'std'     => '',
            'size' => 'regular',
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
            'desc' => __('Not used since 1.2.0: a settled invoice is always marked paid through GetPaid.', 'coinsnap-for-getpaid'),
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
        if (filter_input(INPUT_GET, 'getpaid-listener', FILTER_SANITIZE_FULL_SPECIAL_CHARS) !== 'coinsnap') {
            return;
        }
        $provider = $this->get_payment_provider();
        $webhook = get_option('wpinv_settings_coinsnap_webhook');
        $secret = is_array($webhook) ? (string) ($webhook['secret'] ?? '') : '';
        $header = ($provider === 'btcpay') ? 'HTTP_BTCPAY_SIG' : 'HTTP_X_COINSNAP_SIGNATURE';
        $statuses = [
            'settled' => 'publish',
            'processing' => (string) wpinv_get_option('coinsnap_processing_status', 'wpi-processing'),
            'expired' => (string) wpinv_get_option('coinsnap_expired_status', 'wpi-cancelled'),
        ];

        $status = (new \CoinsnapGP\Webhook\WebhookHandler($provider, $secret, $statuses))->handle(
            (string) file_get_contents('php://input'),
            isset($_SERVER[$header]) ? (string) wp_unslash($_SERVER[$header]) : '',
            time()
        );
        status_header($status);
        echo $status === 200 ? 'OK' : ($status === 401 ? 'Unauthorized' : 'Retry');
        exit;
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

        try {
            $webhookReady = $this->webhookExists($this->getApiUrl(), $this->getApiKey(), $this->getStoreId())
                || $this->registerWebhook($this->getApiUrl(), $this->getApiKey(), $this->getStoreId());
        } catch (\Throwable $e) {
            $webhookReady = false;
        }
        if (!$webhookReady) {
            wpinv_set_error( 'Connection error', esc_html__('The payment could not be started. Please try again later.', 'coinsnap-for-getpaid') );
            wpinv_send_back_to_checkout( $invoice );
        }
        
        $amount =  round($invoice->get_total(), 2);
        $currency = strtoupper($invoice->get_currency());
        
        $client = new \Coinsnap\Client\Invoice($this->getApiUrl(), $this->getApiKey());
        $checkInvoice = $this->coinsnapgp_amount_validation($amount, $currency );
                
        if($checkInvoice['result'] === true){

            $redirectUrl = (!empty(wpinv_get_option('coinsnap_returnurl')))? wpinv_get_option('coinsnap_returnurl') : esc_url_raw($this->get_return_url($invoice));

            $buyerEmail = $invoice->get_email();
            $buyerName = $invoice->get_first_name() . ' ' . $invoice->get_last_name();

            $metadata = [];
            $metadata['orderNumber'] = $invoice->get_number();
            $metadata['customerName'] = $buyerName;
            
            if($this->get_payment_provider() === 'btcpay') {
                $metadata['orderId'] = $invoice->get_number();
            }
            
            if($this->get_payment_provider() === 'btcpay' && $currency !== 'BTC'){
                $store = new \Coinsnap\Client\Store($this->getApiUrl(), $this->getApiKey());
                $btcpayCurrencies = $store -> getStoreCurrenciesRates($this->getStoreId(),array($currency));
                $isCurrency = true;
                if(!isset($btcpayCurrencies['result']['error']) && count($btcpayCurrencies['result']['currencies'])>0){
                        if(!isset($btcpayCurrencies['result']['currencies']['BTC_'.$currency])){
                            $isCurrency = false;
                        }
                }
                else {
                    $isCurrency = false;
                }
                    
                // Handle currencies non-supported by BTCPay Server, we need to change them BTC and adjust the amount.
                if( !$isCurrency ){
                        $currency = 'BTC';
                        $rate = 1/$checkInvoice['rate'];
                        $amountBTC = bcdiv(strval($amount), strval($rate), 8);
                        $amount = (float)$amountBTC;
                }
            }
                
            $camount = ($currency === 'BTC')? \Coinsnap\Util\PreciseNumber::parseFloat($amount,8) : \Coinsnap\Util\PreciseNumber::parseFloat($amount,2);
                
            $redirectAutomatically = (wpinv_get_option( 'coinsnap_autoredirect') > 0)? true : false;
            $walletMessage = '';

            try {
                $invoiceData = $client->createInvoice(
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
                )->getData();
            } catch (\Throwable $e) {
                $invoiceData = [];
            }

            if (empty($invoiceData['checkoutLink']) || empty($invoiceData['id'])) {
                wpinv_set_error( 'Payment error', esc_html__('The payment could not be started. Please try again later.', 'coinsnap-for-getpaid') );
                wpinv_send_back_to_checkout( $invoice );
                exit();
            }

            // Only invoices recorded on the GetPaid invoice can pay it, and only for the amount and currency sent.
            $invoices = get_post_meta($invoice->get_id(), '_coinsnap_invoices', true);
            $invoices = is_array($invoices) ? $invoices : [];
            $invoices[(string) $invoiceData['id']] = [
                'amount' => (string) $camount,
                'currency' => $currency,
                'invoice_total' => (string) $invoice->get_total(),
                'invoice_currency' => (string) $invoice->get_currency(),
            ];
            update_post_meta($invoice->get_id(), '_coinsnap_invoices', $invoices);

            wp_redirect($invoiceData['checkoutLink']);
            exit();
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

    // Throws when the API can't be asked; the caller must not treat that as "no webhook".
    public function webhookExists(string $apiUrl, string $apiKey, string $storeId): bool {
        $whClient = new Webhook($apiUrl, $apiKey);
        $stored = get_option('wpinv_settings_coinsnap_webhook');

        if (is_array($stored) && !empty($stored['id']) && !empty($stored['secret'])) {
            try {
                $existing = $whClient->getWebhook($storeId, (string) $stored['id']);
                if (strpos((string) ($existing->getData()['url'] ?? ''), $this->get_webhook_url()) !== false) {
                    return true;
                }
            } catch (\Coinsnap\Exception\RequestException $e) {
                if (!in_array($e->getCode(), [400, 404], true)) {
                    throw $e;
                }
            }
        }
        // Remove webhooks of ours whose secret this site does not hold.
        foreach ($whClient->getWebhooks($storeId) as $webhook) {
            if (strpos((string) $webhook->getData()['url'], $this->get_webhook_url()) !== false) {
                try {
                    $whClient->deleteWebhook($storeId, $webhook->getData()['id']);
                } catch (\Throwable $e) {
                    error_log('Coinsnap: a stale webhook could not be deleted');
                }
            }
        }
        return false;
    }

    public function registerWebhook(string $apiUrl, $apiKey, $storeId): bool {
        try {
            $whClient = new Webhook($apiUrl, $apiKey);
            $webhook_events = ($this->get_payment_provider() === 'btcpay')? self::BTCPAY_WEBHOOK_EVENTS : self::COINSNAP_WEBHOOK_EVENTS;
            $webhook = $whClient->createWebhook($storeId, $this->get_webhook_url(), $webhook_events, null)->getData();
        }
        catch (\Throwable $e) {
            error_log(sprintf('Coinsnap: the webhook could not be registered (status %d)', (int) $e->getCode()));
            return false;
        }
        if (empty($webhook['id']) || empty($webhook['secret'])) {
            return false;
        }
        update_option('wpinv_settings_coinsnap_webhook', ['id' => $webhook['id'], 'secret' => $webhook['secret'], 'url' => $webhook['url'] ?? $this->get_webhook_url()], false);
        return true;
    }

    // The stored key is never printed; an empty field keeps it.
    public function renderApiKeyField($args) {
        $id = ($args['id'] ?? '') === 'btcpay_api_key' ? 'btcpay_api_key' : 'coinsnap_api_key';
        $stored = (string) wpinv_get_option($id, '');
        $hint = ($stored !== '') ? sprintf(
            /* translators: 1: last characters of the stored API key */
            __('A key ending in %1$s is saved. Leave empty to keep it.', 'coinsnap-for-getpaid'),
            substr($stored, -4)
        ) : ($args['desc'] ?? '');
        printf(
            '<input type="password" class="regular-text %1$s" id="wpinv_settings[%2$s]" name="wpinv_settings[%2$s]" value="" autocomplete="new-password"/><p class="description">%3$s</p>',
            esc_attr($id === 'btcpay_api_key' ? 'btcpay' : 'coinsnap'),
            esc_attr($id),
            esc_html($hint)
        );
    }

    public function keepStoredApiKeys($input) {
        if (!is_array($input)) {
            return $input;
        }
        if (array_key_exists('coinsnap_api_key', $input) && trim((string) $input['coinsnap_api_key']) === '') {
            $input['coinsnap_api_key'] = (string) wpinv_get_option('coinsnap_api_key', '');
        }
        if (array_key_exists('btcpay_api_key', $input) && trim((string) $input['btcpay_api_key']) === '') {
            // A key is never sent to a server it was not created on.
            $urlUnchanged = trim((string) ($input['btcpay_server_url'] ?? '')) === (string) wpinv_get_option('btcpay_server_url', '');
            $input['btcpay_api_key'] = $urlUnchanged ? (string) wpinv_get_option('btcpay_api_key', '') : '';
        }
        return $input;
    }
}

add_filter('getpaid_default_gateways', 'coinsnapgp_gateway_register');
function coinsnapgp_gateway_register($gateways)
{
    $gateways['coinsnap'] = 'CoinsnapGP_Gateway';
    return $gateways;
}
