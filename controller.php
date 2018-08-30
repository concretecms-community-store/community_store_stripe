<?php      

namespace Concrete\Package\CommunityStoreStripe;

use Package;
use Whoops\Exception\ErrorException;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Payment\Method as PaymentMethod;

defined('C5_EXECUTE') or die(_("Access Denied."));

class Controller extends Package
{
    protected $pkgHandle = 'community_store_stripe';
    protected $appVersionRequired = '5.7.2';
    protected $pkgVersion = '1.1';

    public function on_start()
    {
        require __DIR__ . '/vendor/autoload.php';
    }

    public function getPackageDescription()
    {
        return t("Stripe Payment Method for Community Store");
    }

    public function getPackageName()
    {
        return t("Stripe Payment Method");
    }
    
    public function install()
    {
        $installed = Package::getInstalledHandles();
        if(!(is_array($installed) && in_array('community_store',$installed)) ) {
            throw new ErrorException(t('This package requires that Community Store be installed'));
        } else {
            $pkg = parent::install();
            $pm = new PaymentMethod();
            $pm->add('community_store_stripe','Stripe',$pkg);
        }
        
    }
    public function uninstall()
    {
        $pm = PaymentMethod::getByHandle('community_store_stripe');
        if ($pm) {
            $pm->delete();
        }
        $pkg = parent::uninstall();
    }

}
?>