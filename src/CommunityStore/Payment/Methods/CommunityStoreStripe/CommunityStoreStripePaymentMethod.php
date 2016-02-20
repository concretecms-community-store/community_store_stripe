<?php
namespace Concrete\Package\CommunityStoreStripe\Src\CommunityStore\Payment\Methods\CommunityStoreStripe;

use Concrete\Package\CommunityStore\Controller\SinglePage\Dashboard\Store;
use Core;
use Config;
use Exception;
use Omnipay\Omnipay;

use \Concrete\Package\CommunityStore\Src\CommunityStore\Payment\Method as PaymentMethod;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Utilities\Calculator as StoreCalculator;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Customer\Customer as StoreCustomer;


class CommunityStoreStripePaymentMethod extends PaymentMethod
{

    public $gatewayNames = array(
        'stripe_button'=>'Stripe',
        'stripe_form'=>'Stripe'
    );

    public function dashboardForm()
    {
        $this->set('mode', Config::get('community_store_stripe.mode'));
        $this->set('gateway',Config::get('community_store_stripe.gateway'));
        $this->set('currency',Config::get('community_store_stripe.currency'));
        $this->set('testPublicApiKey',Config::get('community_store_stripe.testPublicApiKey'));
        $this->set('livePublicApiKey',Config::get('community_store_stripe.livePublicApiKey'));
        $this->set('testPrivateApiKey',Config::get('community_store_stripe.testPrivateApiKey'));
        $this->set('livePrivateApiKey',Config::get('community_store_stripe.livePrivateApiKey'));
        $this->set('form',Core::make("helper/form"));

        $gateways = array(
            'stripe_button'=>'Button',
            'stripe_form'=>'Form'
        );

        $this->set('gateways',$gateways);

        $currencies = array(
        	'USD'=>t('US Dollars'),
        	'CAD'=>t('Canadian Dollar'),
        	'AUD'=>t('Australian Dollar'),
        	'GPB'=>t('British Pound'),
        	'EUR'=>t('Euro'),
            'CHF'=>t('Swiss Franc')
        );

        $this->set('currencies',$currencies);
    }
    
    public function save($data)
    {
        Config::save('community_store_stripe.mode',$data['mode']);
        Config::save('community_store_stripe.gateway',$data['gateway']);
        Config::save('community_store_stripe.currency',$data['currency']);
        Config::save('community_store_stripe.testPublicApiKey',$data['testPublicApiKey']);
        Config::save('community_store_stripe.livePublicApiKey',$data['livePublicApiKey']);
        Config::save('community_store_stripe.testPrivateApiKey',$data['testPrivateApiKey']);
        Config::save('community_store_stripe.livePrivateApiKey',$data['livePrivateApiKey']);
    }
    public function validate($args,$e)
    {
        return $e;
    }
    public function checkoutForm()
    {
        $mode = Config::get('community_store_stripe.mode');
        $this->set('mode',$mode);
        $this->set('gateway',Config::get('community_store_stripe.gateway'));
        $this->set('currency',Config::get('community_store_stripe.currency'));

        if ($mode == 'live') {
            $this->set('publicAPIKey',Config::get('community_store_stripe.livePublicApiKey'));
        } else {
            $this->set('publicAPIKey',Config::get('community_store_stripe.testPublicApiKey'));
        }

        $customer = new StoreCustomer();

        $this->set('email', $customer->getEmail());
        $this->set('form',Core::make("helper/form"));
        $this->set('amount',  number_format(StoreCalculator::getGrandTotal() * 100, 0, '', ''));

        $pmID = PaymentMethod::getByHandle('community_store_stripe')->getPaymentMethodID();
        $this->set('pmID',$pmID);
        $years = array();
        $year = date("Y");
        for($i=0;$i<15;$i++){
            $years[$year+$i] = $year+$i;
        }
        $this->set("years",$years);
    }
    
    public function submitPayment()
    {
        $gatewaytype = Config::get('community_store_stripe.gateway');
        $customer = new StoreCustomer();
        $email = trim($customer->getEmail());

        $gatewaytype = ucfirst(substr($gatewaytype, 0, strpos($gatewaytype, '_')));

        $gateway = Omnipay::create($gatewaytype);
        $currency = Config::get('community_store_stripe.currency');
        $mode =  Config::get('community_store_stripe.mode');

        if ($mode == 'test') {
            $privateKey = Config::get('community_store_stripe.testPrivateApiKey');
        } else {
            $privateKey = Config::get('community_store_stripe.livePrivateApiKey');
        }

        if ($gatewaytype == 'Stripe') {
            $gateway->setApiKey($privateKey);
            $token = $_POST['stripeToken'];
            $response = $gateway->purchase(['amount' =>  number_format(StoreCalculator::getGrandTotal(), 2, '.', ''), 'currency' => $currency, 'token' => $token])->send();
        }
        
        if ($response->isSuccessful()) {
            // payment was successful: update database
            return array('error'=>0, 'transactionReference'=>$response->getTransactionReference());
        } elseif ($response->isRedirect()) {
            // redirect to offsite payment gateway
            $response->redirect();
        } else {
            // payment failed: display message to customer
            return array('error'=>1,'errorMessage'=> $response->getMessage());
        }
    }

    public function getPaymentMethodName(){
        return 'Stripe';
    }

    public function getPaymentMethodDisplayName()
    {
        return $this->getPaymentMethodName();
    }
    
}

return __NAMESPACE__;