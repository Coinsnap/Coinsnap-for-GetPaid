<?php
if (!defined('ABSPATH')) {
    exit;
}

class CoinsnapGP_Gateway extends GetPaid_Payment_Gateway {
    public const WEBHOOK_EVENTS = ['New', 'Expired', 'Settled', 'Processing'];

    public function __construct(){
        $this->id = 'coinsnap';
        $this->title = __('Bitcoin + Lightning', 'coinsnap-for-getpaid');
        $this->method_title = __('Coinsnap', 'coinsnap-for-getpaid');
        $this->supports = array('subscription', 'addons');
        add_action('init', array($this, 'process_webhook'));
        add_action('admin_notices', array($this, 'coinsnap_notice'));
        parent::__construct();
    }
    
    public function coinsnap_notice(){
        
        $page = (filter_input(INPUT_GET,'page',FILTER_SANITIZE_FULL_SPECIAL_CHARS ))? filter_input(INPUT_GET,'page',FILTER_SANITIZE_FULL_SPECIAL_CHARS ) : '';
        $tab = (filter_input(INPUT_GET,'tab',FILTER_SANITIZE_FULL_SPECIAL_CHARS ))? filter_input(INPUT_GET,'tab',FILTER_SANITIZE_FULL_SPECIAL_CHARS ) : '';
        
        if($page === 'wpinv-settings' && $tab === 'gateways'){
            $coinsnap_url = $this->getApiUrl();
            $coinsnap_api_key = $this->getApiKey();
            $coinsnap_store_id = $this->getStoreId();
            $coinsnap_webhook_url = $this->get_webhook_url();
                
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
                        
                        if ( !$this->webhookExists( $coinsnap_store_id, $coinsnap_api_key, $coinsnap_webhook_url ) ) {
                            if ( ! $this->registerWebhook( $coinsnap_store_id, $coinsnap_api_key, $coinsnap_webhook_url ) ) {
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

        $admin_settings['coinsnap_store_id'] = array(
            'id'   => 'coinsnap_store_id',
            'name' => __('Store ID', 'coinsnap-for-getpaid'),
            'desc' => __('Enter Store ID', 'coinsnap-for-getpaid'),
            'type' => 'text',
        );
        $admin_settings['coinsnap_api_key'] = array(
            'id'   => 'coinsnap_api_key',
            'name' => __('API Key', 'coinsnap-for-getpaid'),
            'desc' => __('Enter API Key', 'coinsnap-for-getpaid'),
            'type' => 'text',
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

        $notify_json = file_get_contents('php://input');

        $notify_ar = json_decode($notify_json, true);
        $invoice_id = $notify_ar['invoiceId'];

        try {
            $client = new \Coinsnap\Client\Invoice($this->getApiUrl(), $this->getApiKey());
            $csinvoice = $client->getInvoice($this->getStoreId(), $invoice_id);
            $status = $csinvoice->getData()['status'];
            $order_id = $csinvoice->getData()['orderId'];
        } catch (\Throwable $e) {

            echo "Error";
            exit;
        }

        $order_status = 'wpi-pending';


        if ($status == 'Expired') {
            $order_status = wpinv_get_option('coinsnap_expired_status');
        } elseif ($status == 'Processing') {
            $order_status = wpinv_get_option('coinsnap_processing_status');
        } elseif ($status == 'Settled') {
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




    public function process_payment($invoice, $submission_data, $submission)
    {

        $webhook_url = $this->get_webhook_url();

        if (!$this->webhookExists($this->getStoreId(), $this->getApiKey(), $webhook_url)) {
            if (!$this->registerWebhook($this->getStoreId(), $this->getApiKey(), $webhook_url)) {
                echo "unable to set Webhook url";
                exit;
            }
        }

        $amount =  $invoice->get_total();
        $redirectUrl = esc_url_raw($this->get_return_url($invoice));


        $amount = round($amount, 2);
        $buyerEmail = $invoice->get_email();
        $buyerName = $invoice->get_first_name() . ' ' . $invoice->get_last_name();


        $metadata = [];
        $metadata['orderNumber'] = $invoice->get_number();
        $metadata['customerName'] = $buyerName;


        $client = new \Coinsnap\Client\Invoice($this->getApiUrl(), $this->getApiKey());
        $camount = \Coinsnap\Util\PreciseNumber::parseFloat($amount, 2);

        $csinvoice = $client->createInvoice(
            $this->getStoreId(),
            $invoice->get_currency(),
            $camount,
            $invoice->get_number(),
            $buyerEmail,
            $buyerName,
            $redirectUrl,
            COINSNAP_GETPAID_REFERRAL_CODE,
            $metadata
        );


        $payurl = $csinvoice->getData()['checkoutLink'];
        wp_redirect($payurl);
        exit;
    }



    public function get_webhook_url()
    {
        return esc_url_raw(add_query_arg(array('getpaid-listener' => 'coinsnap'), home_url('index.php')));
    }
    public function getApiKey()
    {
        return wpinv_get_option('coinsnap_api_key');
    }
    public function getStoreId()
    {
        return wpinv_get_option('coinsnap_store_id');
    }
    public function getApiUrl()
    {
        return 'https://app.coinsnap.io';
    }

    public function webhookExists(string $storeId, string $apiKey, string $webhook): bool
    {
        try {
            $whClient = new \Coinsnap\Client\Webhook($this->getApiUrl(), $apiKey);
            $Webhooks = $whClient->getWebhooks($storeId);


            foreach ($Webhooks as $Webhook) {
                //self::deleteWebhook($storeId,$apiKey, $Webhook->getData()['id']);
                if ($Webhook->getData()['url'] == $webhook) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
            return false;
        }

        return false;
    }
    public function registerWebhook(string $storeId, string $apiKey, string $webhook): bool
    {
        try {
            $whClient = new \Coinsnap\Client\Webhook($this->getApiUrl(), $apiKey);

            $webhook = $whClient->createWebhook(
                $storeId,   //$storeId
                $webhook, //$url
                self::WEBHOOK_EVENTS,
                null    //$secret
            );

            return true;
        } catch (\Throwable $e) {
            return false;
        }

        return false;
    }

    public function deleteWebhook(string $storeId, string $apiKey, string $webhookid): bool
    {

        try {
            $whClient = new \Coinsnap\Client\Webhook($this->getApiUrl(), $apiKey);

            $webhook = $whClient->deleteWebhook(
                $storeId,   //$storeId
                $webhookid, //$url
            );
            return true;
        } catch (\Throwable $e) {

            return false;
        }
    }
}

add_filter('getpaid_default_gateways', 'register_GetPaid_coinsnap');
function register_my_custom_gateway($gateways)
{
    $gateways['coinsnap'] = 'CoinsnapGP_Gateway';
    return $gateways;
}
