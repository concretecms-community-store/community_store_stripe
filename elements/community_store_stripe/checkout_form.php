<?php defined('C5_EXECUTE') or die(_("Access Denied."));
extract($vars);
?>
<?php if ($publicAPIKey) { ?>
<?php if ($gateway == 'stripe_button') {
    Loader::packageElement('stripe/stripe_button', 'community_store_stripe', $vars);
} ?>
<?php if ($gateway == 'stripe_form') {
    Loader::packageElement('stripe/stripe_form', 'community_store_stripe', $vars);
} ?>
<?php if ($gateway == 'stripe_form_elements') {
    Loader::packageElement('stripe/stripe_form_elements', 'community_store_stripe', $vars);
} ?>
<?php } else { ?>
<p class="alert alert-warning"><?php t('API Keys not entered');?></p>
<?php } ?>




