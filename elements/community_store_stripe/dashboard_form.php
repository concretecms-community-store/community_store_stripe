<?php defined('C5_EXECUTE') or die(_("Access Denied.")); 
extract($vars);
?>
<div class="form-group">
    <?=$form->label('currency',t('Currency'))?>
    <?=$form->select('currency',$currencies,$currency)?>
</div>

<div class="form-group">
    <?=$form->label('gateways',t('Integration Type'))?>
    <?=$form->select('gateway',$gateways,$gateway)?>
</div>

<div class="form-group">
    <?=$form->label('mode',t('Mode'))?>
    <?=$form->select('mode',array('test'=>t('Test'), 'live'=>t('Live')),$mode)?>
</div>



<div class="form-group">
    <label><?=t("Test Secret Key")?></label>
    <input type="text" name="testPrivateApiKey" value="<?=$testPrivateApiKey?>" class="form-control">
</div>

<div class="form-group">
    <label><?=t("Test Publishable Key")?></label>
    <input type="text" name="testPublicApiKey" value="<?=$testPublicApiKey?>" class="form-control">
</div>


<div class="form-group">
    <label><?=t("Live Secret Key")?></label>
    <input type="text" name="livePrivateApiKey" value="<?=$livePrivateApiKey?>" class="form-control">
</div>

<div class="form-group">
    <label><?=t("Live Publishable Key")?></label>
    <input type="text" name="livePublicApiKey" value="<?=$livePublicApiKey?>" class="form-control">
</div>