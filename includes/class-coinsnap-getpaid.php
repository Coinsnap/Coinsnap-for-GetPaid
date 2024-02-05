<?php

if (!defined('ABSPATH')) {
    exit;
}

define('COINSNAP_REFERRAL_CODE', 'D15432');

require_once(dirname(__FILE__) . "/library/autoload.php");

class GetPaidGateway_coinsnap extends GetPaid_Payment_Gateway
{
    public const WEBHOOK_EVENTS = ['New','Expired','Settled','Processing'];

    public function __construct()
    {
        $this->id = 'coinsnap';
        $this->title = __('Bitcoin + Lightning', 'getpaid-coinsnap');
        $this->method_title = __('Coinsnap', 'getpaid-coinsnap');
        $this->supports     = array( 'subscription',  'addons' );
        add_action('init', array( $this, 'process_webhook'));
        parent::__construct();

    }

    public function admin_settings($admin_settings)
    {

        $statuses = wpinv_get_invoice_statuses(true, true, $this);
        $admin_settings['coinsnap_desc']['std']  = __('Pay using Bitcoin + Lightning', 'getpaid-coinsnap');

        $admin_settings['coinsnap_store_id'] = array(
            'id'   => 'coinsnap_store_id',
            'name' => __('Store ID', 'getpaid-coinsnap'),
            'desc' => __('Enter Store ID', 'getpaid-coinsnap'),
            'type' => 'text',
        );
        $admin_settings['coinsnap_api_key'] = array(
            'id'   => 'coinsnap_api_key',
            'name' => __('API Key', 'getpaid-coinsnap'),
            'desc' => __('Enter API Key', 'getpaid-coinsnap'),
            'type' => 'text',
        );
        $admin_settings['coinsnap_expired_status'] = array(
            'id'   => 'coinsnap_expired_status',
            'name' => __('Expired Status', 'getpaid-coinsnap'),
            'desc' => __('Select Expired Status', 'getpaid-coinsnap'),
            'type'        => 'select',
            'std'         => 'wpi-cancelled',
            'options'     => $statuses,
        );
        $admin_settings['coinsnap_settled_status'] = array(
            'id'   => 'coinsnap_settled_status',
            'name' => __('Settled Status', 'getpaid-coinsnap'),
            'desc' => __('Select Settled Status', 'getpaid-coinsnap'),
            'type'        => 'select',
            'std'         => 'publish',
            'options'     => $statuses,
        );
        $admin_settings['coinsnap_processing_status'] = array(
            'id'   => 'coinsnap_processing_status',
            'name' => __('Processing Status', 'getpaid-coinsnap'),
            'desc' => __('Select Processing Status', 'getpaid-coinsnap'),
            'type'        => 'select',
            'std'         => 'wpi-processing',
            'options'     => $statuses,
        );

        return $admin_settings;

    }





    public function process_webhook()
    {

        if (! isset($_GET['getpaid-listener']) || $_GET['getpaid-listener'] !== 'coinsnap') {
            return;
        }

        $notify_json = file_get_contents('php://input');

        $notify_ar = json_decode($notify_json, true);
        $invoice_id = $notify_ar['invoiceId'];



        try {
            $client = new \Coinsnap\Client\Invoice($this->getApiUrl(), $this->getApiKey());
            $csinvoice = $client->getInvoice($this->getStoreId(), $invoice_id);
            $status = $csinvoice->getData()['status'] ;
            $order_id = $csinvoice->getData()['orderId'] ;


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
                $invoice->add_note(__('Payment transaction - '.$status, 'invoicing'), false, false, true);
                $invoice->save();
            }
        }

        echo "OK";
        exit;

    }




    public function process_payment($invoice, $submission_data, $submission)
    {

        $webhook_url = $this->get_webhook_url();

        if (! $this->webhookExists($this->getStoreId(), $this->getApiKey(), $webhook_url)) {
            if (! $this->registerWebhook($this->getStoreId(), $this->getApiKey(), $webhook_url)) {
                echo "unable to set Webhook url";
                exit;
            }
        }

        $amount =  $invoice->get_total();
        $redirectUrl = esc_url_raw($this->get_return_url($invoice));


        $amount = round($amount, 2);
        $buyerEmail = $invoice->get_email();
        $buyerName = $invoice->get_first_name() . ' ' .$invoice->get_last_name();


        $metadata = [];
        $metadata['orderNumber'] = $invoice->get_number();
        $metadata['customerName'] = $buyerName;


        $checkoutOptions = new \Coinsnap\Client\InvoiceCheckoutOptions();
        $checkoutOptions->setRedirectURL($redirectUrl);
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
            COINSNAP_REFERRAL_CODE,
            $metadata,
            $checkoutOptions
        );


        $payurl = $csinvoice->getData()['checkoutLink'] ;
        wp_redirect($payurl);
        exit;

    }



    public function get_webhook_url()
    {
        return esc_url_raw(add_query_arg(array( 'getpaid-listener' => 'coinsnap' ), home_url('index.php')));
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
    $gateways['coinsnap'] = 'GetPaidGateway_coinsnap';
    return $gateways;
}
