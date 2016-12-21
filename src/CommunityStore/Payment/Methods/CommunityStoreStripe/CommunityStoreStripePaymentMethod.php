<?php
namespace Concrete\Package\CommunityStoreStripe\Src\CommunityStore\Payment\Methods\CommunityStoreStripe;

use Concrete\Package\CommunityStore\Controller\SinglePage\Dashboard\Store;
use Core;
use Log;
use Config;
use Exception;
use \Stripe\Stripe;
use \Stripe\Charge;
use Stripe\Error;

use \Concrete\Package\CommunityStore\Src\CommunityStore\Payment\Method as StorePaymentMethod;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Utilities\Calculator as StoreCalculator;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Customer\Customer as StoreCustomer;

class CommunityStoreStripePaymentMethod extends StorePaymentMethod
{

    public $gatewayNames = array(
        'stripe_button'=>'Stripe',
        'stripe_form'=>'Stripe'
    );

    public function dashboardForm()
    {
        $this->set('stripeMode', Config::get('community_store_stripe.mode'));
        $this->set('stripeGateway',Config::get('community_store_stripe.gateway'));
        $this->set('stripeCurrency',Config::get('community_store_stripe.currency'));
        $this->set('stripeTestPublicApiKey',Config::get('community_store_stripe.testPublicApiKey'));
        $this->set('stripeLivePublicApiKey',Config::get('community_store_stripe.livePublicApiKey'));
        $this->set('stripeTestPrivateApiKey',Config::get('community_store_stripe.testPrivateApiKey'));
        $this->set('stripeLivePrivateApiKey',Config::get('community_store_stripe.livePrivateApiKey'));
        $this->set('form',Core::make("helper/form"));

        $gateways = array(
            'stripe_button'=>'Button',
            'stripe_form'=>'Form'
        );

        $this->set('stripeGateways',$gateways);

        $currencies = array(
        	'USD'=>t('US Dollars'),
        	'CAD'=>t('Canadian Dollar'),
        	'AUD'=>t('Australian Dollar'),
        	'GBP'=>t('British Pound'),
        	'EUR'=>t('Euro'),
            'CHF'=>t('Swiss Franc')
        );

        $this->set('stripeCurrencies',$currencies);
    }
    
    public function save(array $data = [])
    {
        Config::save('community_store_stripe.mode',$data['stripeMode']);
        Config::save('community_store_stripe.gateway',$data['stripeGateway']);
        Config::save('community_store_stripe.currency',$data['stripeCurrency']);
        Config::save('community_store_stripe.testPublicApiKey',$data['stripeTestPublicApiKey']);
        Config::save('community_store_stripe.livePublicApiKey',$data['stripeLivePublicApiKey']);
        Config::save('community_store_stripe.testPrivateApiKey',$data['stripeTestPrivateApiKey']);
        Config::save('community_store_stripe.livePrivateApiKey',$data['stripeLivePrivateApiKey']);
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

        $pmID = StorePaymentMethod::getByHandle('community_store_stripe')->getID();
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
        $customer = new StoreCustomer();
        
        $currency = Config::get('community_store_stripe.currency');
        $mode =  Config::get('community_store_stripe.mode');

        if ($mode == 'test') {
            $privateKey = Config::get('community_store_stripe.testPrivateApiKey');
        } else {
            $privateKey = Config::get('community_store_stripe.livePrivateApiKey');
        }

        \Stripe\Stripe::setApiKey($privateKey);
        $token = $_POST['stripeToken'];
        $genericError = false;
        
        try {
            $response = \Stripe\Charge::create(array("amount" => StoreCalculator::getGrandTotal()*100, "currency" => $currency, "source" => $token));
            return array('error'=>0, 'transactionReference'=>$response->id);
        } catch(\Stripe\Error\Card $e) {
            // Since it's a decline, \Stripe\Error\Card will be caught
            $body = $e->getJsonBody();
            $err  = $body['error'];
            return array('error'=>1,'errorMessage'=> $err['message']);

        } catch (\Stripe\Error\RateLimit $e) {
        // Too many requests made to the API too quickly
            $genericError = true;
            $errors[] = $e;
        } catch (\Stripe\Error\InvalidRequest $e) {
            $genericError = true;
            $errors[] = $e;
        // Invalid parameters were supplied to Stripe's API
        } catch (\Stripe\Error\Authentication $e) {
            $genericError = true;
            $errors[] = $e;
        // Authentication with Stripe's API failed
        // (maybe you changed API keys recently)
        } catch (\Stripe\Error\ApiConnection $e) {
            $genericError = true;
            $errors[] = $e;
        // Network communication with Stripe failed
        } catch (\Stripe\Error\Base $e) {
            $genericError = true;
            $errors[] = $e;
        // Display a very generic error to the user, and maybe send
        // yourself an email
        } catch (Exception $e) {
            $genericError = true;
            $errors[] = $e;
        // Something else happened, completely unrelated to Stripe
        }
        if ($genericError) {
            foreach($errors as $error) {
                $body = $error->getJsonBody();
                $err  = $body['error'];
                Log::addEntry('Stripe error.'."\n".'Status is:' . $error->getHttpStatus() . "\n".'Type is:' . $err['type'] . "\n".'Code is:' . $err['code'] . "\n".'Param is:' . $err['param'] . "\n".'Message is:' . $err['message'] . "\n", t('Community Store Stripe'));
            }
           return array('error'=>1,'errorMessage'=> t('Something went wrong with this transation.'));
        }

    }

    public function getName()
    {
        return 'Stripe';
    }
    
}

return __NAMESPACE__;