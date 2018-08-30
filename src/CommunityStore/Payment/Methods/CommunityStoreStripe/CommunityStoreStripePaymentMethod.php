<?php
namespace Concrete\Package\CommunityStoreStripe\Src\CommunityStore\Payment\Methods\CommunityStoreStripe;

use Core;
use Log;
use Config;
use Exception;
use Stripe\Stripe;
use Stripe\Charge;
use Stripe\Error;
use Session;
use Concrete\Package\CommunityStore\Src\CommunityStore\Payment\Method as StorePaymentMethod;
use Concrete\Package\CommunityStore\Src\CommunityStore\Utilities\Calculator as StoreCalculator;
use Concrete\Package\CommunityStore\Src\CommunityStore\Customer\Customer as StoreCustomer;
use Concrete\Package\CommunityStore\Src\CommunityStore\Utilities\Price as StorePrice;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Cart\Cart as StoreCart;

class CommunityStoreStripePaymentMethod extends StorePaymentMethod
{
    public $gatewayNames = [
        'stripe_button' => 'Stripe',
        'stripe_form' => 'Stripe',
    ];

    private function getCurrencies()
    {
        return [
            'USD' => t('US Dollar'),
            'EUR' => t('Euro'),
            'GBP' => t('British Pounds Sterling'),
            'AUD' => t('Australian Dollar'),
            'BRL' => t('Brazilian Real'),
            'CAD' => t('Canadian Dollar'),
            'CLP' => t('Chilean Peso'),
            'CZK' => t('Czech Koruna'),
            'DKK' => t('Danish Krone'),
            'HKD' => t('Hong Kong Dollar'),
            'HUF' => t('Hungarian Forint'),
            'IRR' => t('Iranian Rial'),
            'ILS' => t('Israeli Shekel'),
            'JPY' => t('Japanese Yen'),
            'MYR' => t('Malaysian Ringgit'),
            'MXN' => t('Mexican Peso'),
            'NZD' => t('New Zealand Dollar'),
            'NOK' => t('Norwegian Krone'),
            'PHP' => t('Philippine Peso'),
            'PLN' => t('Polish Zloty'),
            'RUB' => t('Russian Rubles'),
            'SGD' => t('Singapore Dollar'),
            'KRW' => t('South Korean Won'),
            'SEK' => t('Swedish Krona'),
            'CHF' => t('Swiss Franc)'),
            'TWD' => t('Taiwan New Dollar'),
            'THB' => t('Thai Baht'),
            'TRY' => t('Turkish Lira'),
            'VND' => t('Vietnamese Dong'),
        ];
    }

    public function dashboardForm()
    {
        $this->set('stripeMode', Config::get('community_store_stripe.mode'));
        $this->set('stripeGateway', Config::get('community_store_stripe.gateway'));
        $this->set('stripeCurrency', Config::get('community_store_stripe.currency'));
        $this->set('stripeTestPublicApiKey', Config::get('community_store_stripe.testPublicApiKey'));
        $this->set('stripeLivePublicApiKey', Config::get('community_store_stripe.livePublicApiKey'));
        $this->set('stripeTestPrivateApiKey', Config::get('community_store_stripe.testPrivateApiKey'));
        $this->set('stripeLivePrivateApiKey', Config::get('community_store_stripe.livePrivateApiKey'));
        $this->set('form', Core::make("helper/form"));

        $gateways = [
            'stripe_button' => 'Button',
            'stripe_form' => 'Form',
        ];

        $this->set('stripeGateways', $gateways);

        $this->set('stripeCurrencies', $this->getCurrencies());
    }

    public function save(array $data = [])
    {
        Config::save('community_store_stripe.mode', $data['stripeMode']);
        Config::save('community_store_stripe.gateway', $data['stripeGateway']);
        Config::save('community_store_stripe.currency', $data['stripeCurrency']);
        Config::save('community_store_stripe.testPublicApiKey', $data['stripeTestPublicApiKey']);
        Config::save('community_store_stripe.livePublicApiKey', $data['stripeLivePublicApiKey']);
        Config::save('community_store_stripe.testPrivateApiKey', $data['stripeTestPrivateApiKey']);
        Config::save('community_store_stripe.livePrivateApiKey', $data['stripeLivePrivateApiKey']);
    }

    public function validate($args, $e)
    {
        return $e;
    }

    public function checkoutForm()
    {
        $mode = Config::get('community_store_stripe.mode');
        $this->set('mode', $mode);
        $this->set('gateway', Config::get('community_store_stripe.gateway'));
        $this->set('currency', Config::get('community_store_stripe.currency'));

        if ($mode == 'live') {
            $this->set('publicAPIKey', Config::get('community_store_stripe.livePublicApiKey'));
        } else {
            $this->set('publicAPIKey', Config::get('community_store_stripe.testPublicApiKey'));
        }

        $customer = new StoreCustomer();

        $this->set('email', $customer->getEmail());
        $this->set('form', Core::make("helper/form"));
        $currencyMultiplier = StorePrice::getCurrencyMultiplier(Config::get('community_store_stripe.currency'));
        $this->set('amount', number_format(StoreCalculator::getGrandTotal() * $currencyMultiplier, 0, '', ''));

        $pmID = StorePaymentMethod::getByHandle('community_store_stripe')->getID();
        $this->set('pmID', $pmID);
        $years = [];
        $year = date("Y");
        for ($i = 0; $i < 15; ++$i) {
            $years[$year + $i] = $year + $i;
        }
        $this->set("years", $years);
    }

    public function submitPayment()
    {
        $customer = new StoreCustomer();

        $currency = Config::get('community_store_stripe.currency');
        $currencyMultiplier = StorePrice::getCurrencyMultiplier($currency);
        $mode = Config::get('community_store_stripe.mode');

        if ($mode == 'test') {
            $privateKey = Config::get('community_store_stripe.testPrivateApiKey');
        } else {
            $privateKey = Config::get('community_store_stripe.livePrivateApiKey');
        }

        \Stripe\Stripe::setApiKey($privateKey);
        $token = $_POST['stripeToken'];
        $genericError = false;

        try {
            if ($currencyMultiplier === 1) {
                $amount = StoreCalculator::getGrandTotal();
            } elseif ($currencyMultiplier === 100) {
                $amount = round(StoreCalculator::getGrandTotal(), 2);
            }

            $cart = StoreCart::getCart();

            if ($cart) {
                foreach ($cart as $item) {
                    $products[] = $item['product']['object']->getName() . ($item['product']['object']->getSKU() ? '(' . $item['product']['object']->getSKU() . ')' : '') .  ($item['product']['qty'] > 1 ? ' x' . $item['product']['qty'] : '') ;
                }

                $transactionDescription = implode(', ' , $products);
            }

            $response = \Stripe\Charge::create([
                "amount" => $amount * $currencyMultiplier,
                "currency" => $currency,
                "source" => $token,
                "description"=>$transactionDescription,
                "statement_descriptor"=> substr(\Config::get('concrete.site'), 0, 22)

            ]);

            return ['error' => 0, 'transactionReference' => $response->id];
        } catch (\Stripe\Error\Card $e) {
            // Since it's a decline, \Stripe\Error\Card will be caught
            $body = $e->getJsonBody();
            $err = $body['error'];

            return ['error' => 1, 'errorMessage' => $err['message']];
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
            foreach ($errors as $error) {
                $body = $error->getJsonBody();
                $err = $body['error'];
                Log::addEntry('Stripe error.' . "\n" . 'Status is:' . $error->getHttpStatus() . "\n" . 'Type is:' . $err['type'] . "\n" . 'Code is:' . $err['code'] . "\n" . 'Param is:' . $err['param'] . "\n" . 'Message is:' . $err['message'] . "\n", t('Community Store Stripe'));
            }

            return ['error' => 1, 'errorMessage' => t('Something went wrong with this transaction.')];
        }
    }

    public function getPaymentMinimum() {
        return 0.5;
    }

    public function getName()
    {
        return 'Stripe';
    }
}

return __NAMESPACE__;
