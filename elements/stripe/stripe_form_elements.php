<?php defined('C5_EXECUTE') or die(_("Access Denied.")); ?>
<script src="<?= str_replace('/index.php/', '/', URL::to('packages/community_store_stripe/js/jquery.payment.min.js')); ?>"></script>
<script type="text/javascript" src="https://js.stripe.com/v3/"></script>

<script>
    $(window).on('load', function() {
        var form = $('#store-checkout-form-group-payment');
        var submitButton = form.find(".store-btn-complete-order");
		var errorMessage = $('#cardErrors');
		var stripe = Stripe('<?= $publicAPIKey; ?>');
		var elements = stripe.elements();
		var cardNumber = elements.create('cardNumber');
		var cardExpiry = elements.create('cardExpiry');
		var cardCvc = elements.create('cardCvc');
		
		cardNumber.mount('#cardNumber');
		cardExpiry.mount('#cardExpiry');
		cardCvc.mount('#cardCVC');
		
		function stripeTokenHandler(token) {
			var domForm = form.get(0); // Code modified from stripe docs. TODO: rewrite for jquery
			var hiddenInput = document.createElement('input');
			hiddenInput.setAttribute('type', 'hidden');
			hiddenInput.setAttribute('name', 'stripeToken');
			hiddenInput.setAttribute('value', token.id);
			domForm.appendChild(hiddenInput);
			domForm.submit();
		}

		function createToken() {
			stripe.createToken(cardNumber).then(function(result) {
				if (result.error) {
					form.addClass('has-error');
					errorMessage.show();
					errorMessage.text(result.error.message);
					submitButton.removeAttr('disabled');
					submitButton.val('<?= t('Complete Order'); ?>');
				} else {
					stripeTokenHandler(result.token);
				}
			});
		};

        form.submit(function(e) {
            var currentpmid = $('input[name="payment-method"]:checked:first').data('payment-method-id');
            if (currentpmid == <?= $pmID; ?>) {
                e.preventDefault();
				submitButton.prop('disabled', true);
                submitButton.val('<?= t('Processing...'); ?>');
				createToken();
            }
        });
    });


</script>


<div class="store-credit-card-boxpanel panel panel-default">
    <div class="panel-body">
		<div id="cardErrors" style="display:none;" class="alert alert-danger" role="alert"></div>
        <div class="row">
            <div class="col-xs-12">
                <div class="form-group">
                    <label for="cardNumber"><?= t('Card Number'); ?></label>
                    <div class="input-group">
                        <div id="cardNumber" class="form-control ccm-input-text"></div>
                        <span class="input-group-addon"><i class="fa fa-credit-card"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-xs-7 col-md-7">
                <div class="form-group">
                    <label for="cardExpiry"><?= t('Expiration Date'); ?></label>
                    <div id="cardExpiry" class="form-control ccm-input-text"></div>
                </div>
            </div>
            <div class="col-xs-5 col-md-5 pull-right">
                <div class="form-group">
                    <label for="cardCVC"><?= t('CV Code'); ?></label>
                    <div id="cardCVC" class="form-control ccm-input-text"></div>
                </div>
            </div>
        </div>
    </div>
</div>