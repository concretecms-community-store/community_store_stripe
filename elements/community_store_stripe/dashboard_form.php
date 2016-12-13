<?php defined('C5_EXECUTE') or die(_("Access Denied.")); 
extract($vars);
?>
<div class="form-group">
    <?=$form->label('stripeCurrency',t('Currency'))?>
    <?=$form->select('stripeCurrency',$stripeCurrencies,$stripeCurrency)?>
</div>

<div class="form-group">
    <?=$form->label('stripeGateway',t('Integration Type'))?>
    <?=$form->select('stripeGateway',$stripeGateways,$stripeGateway)?>
</div>

<div class="form-group">
    <?=$form->label('stripeMode',t('Mode'))?>
    <?=$form->select('stripeMode',array('test'=>t('Test'), 'live'=>t('Live')),$stripeMode)?>
</div>

<div class="form-group">
    <?=$form->label('stripeTestPrivateApiKey',t('Test Secret Key'))?>
    <input type="text" name="stripeTestPrivateApiKey" value="<?=$stripeTestPrivateApiKey?>" class="form-control">
</div>

<div class="form-group">
    <?=$form->label('stripeTestPublicApiKey',t('Test Publishable Key'))?>
    <input type="text" name="stripeTestPublicApiKey" value="<?=$stripeTestPublicApiKey?>" class="form-control">
</div>

<div class="form-group">
    <?=$form->label('stripeLivePrivateApiKey',t('Live Secret Key'))?>
    <input type="text" name="stripeLivePrivateApiKey" value="<?=$stripeLivePrivateApiKey?>" class="form-control">
</div>

<div class="form-group">
    <?=$form->label('stripeLivePublicApiKey',t('Live Publishable Key'))?>
    <input type="text" name="stripeLivePublicApiKey" value="<?=$stripeLivePublicApiKey?>" class="form-control">
</div>