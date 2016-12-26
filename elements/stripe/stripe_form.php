<?php defined('C5_EXECUTE') or die(_("Access Denied."));?>
<script src="<?= str_replace('/index.php/', '/', URL::to('packages/community_store_stripe/js/jquery.payment.min.js'));?>"></script>
<script type="text/javascript" src="https://js.stripe.com/v2/"></script>

<script>
    $(function() {

        var form = $('#store-checkout-form-group-payment'),
            submitButton = form.find(".store-btn-complete-order");
        var stripe_errorContainer = form.find('.stripe-payment-errors');

        $('#stripe-cc-number').payment('formatCardNumber');
        $('#stripe-cc-exp').payment('formatCardExpiry');
        $('#stripe-cc-cvc').payment('formatCardCVC');

        $('#stripe-cc-number').bind("keyup change", function(e) {
            var validcard = $.payment.validateCardNumber($(this).val());

            if (validcard) {
                $(this).closest('.form-group').removeClass('has-error');
            }
            stripe_errorContainer.hide();
        });

        $('#stripe-cc-exp').bind("keyup change", function(e) {
            var validcard = $.payment.validateCardNumber($(this).val());

            var expiry = $(this).payment('cardExpiryVal');
            var validexpiry = $.payment.validateCardExpiry(expiry.month, expiry.year);

            if (validexpiry) {
                $(this).closest('.form-group').removeClass('has-error');
            }
            stripe_errorContainer.hide();
        });

        $('#stripe-cc-cvc').bind("keyup change", function(e) {
            var validcv = $.payment.validateCardCVC($(this).val());

            if (validcv) {
                $('#stripe-cc-cvc').closest('.form-group').removeClass('has-error');
            }
            stripe_errorContainer.hide();
        });

        Stripe.setPublishableKey('<?= $publicAPIKey; ?>');

        form.submit(function(e) {
            var currentpmid = $('input[name="payment-method"]:checked:first').data('payment-method-id');

            if (currentpmid == <?= $pmID; ?>) {
                e.preventDefault();

                var allvalid = true;

                var validcard = $.payment.validateCardNumber($('#stripe-cc-number').val());

                if (!validcard) {
                    $('#stripe-cc-number').closest('.form-group').addClass('has-error');
                    allvalid = false;
                } else {
                    $('#stripe-cc-number').closest('.form-group').removeClass('has-error');
                }

                var expiry = $('#stripe-cc-exp').payment('cardExpiryVal');
                var validexpiry = $.payment.validateCardExpiry(expiry.month, expiry.year);

                if (!validexpiry) {
                    $('#stripe-cc-exp').closest('.form-group').addClass('has-error');
                    allvalid = false;
                } else {
                    $('#stripe-cc-exp').closest('.form-group').removeClass('has-error');
                }

                var validcv = $.payment.validateCardCVC($('#stripe-cc-cvc').val());

                if (!validcv) {
                    $('#stripe-cc-cvc').closest('.form-group').addClass('has-error');
                    allvalid = false;
                } else {
                    $('#stripe-cc-cvc').closest('.form-group').removeClass('has-error');
                }

                if (!allvalid) {
                    if (!validcard) {
                        $('#stripe-cc-number').focus()
                    } else {
                        if (!validexpiry) {
                            $('#stripe-cc-exp').focus()
                        } else {
                            if (!validcv) {
                                $('#stripe-cc-cvc').focus()
                            }
                        }
                    }

                    return false;
                }

                // Clear previous errors
                stripe_errorContainer.empty();
                stripe_errorContainer.hide();

                // Disable the submit button to prevent multiple clicks
                submitButton.attr({disabled: true});
                submitButton.val('<?= t('Processing...'); ?>');

                var ccData = {
                    number: $('#stripe-cc-number').val(),
                    cvc: $('#stripe-cc-cvc').val(),
                    exp_month: expiry.month,
                    exp_year: expiry.year
                };

                Stripe.card.createToken(ccData, function stripeResponseHandler(status, response) {
                    if (response.error) {
                        stripe_handleError(response);
                    } else {
                        stripe_handleSuccess(response);
                    }
                });

            } else {
                // allow form to submit normally
            }
        });

        function stripe_handleSuccess(response) {
            // Add the card token to the form
            var token = response.id;

            $('<input>')
                .attr({type: 'hidden', name: 'stripeToken'})
                .val(token)
                .appendTo(form);

            // Resubmit the form to the server
            //
            // Only the card_token will be submitted to your server. The
            // browser ignores the original form inputs because they don't
            // have their 'name' attribute set.
            form.get(0).submit();
        }

        function stripe_handleError(response) {
            $('<p class="alert alert-danger">').text(response.error.message).appendTo(stripe_errorContainer);
            stripe_errorContainer.show();

            // Re-enable the submit button
            submitButton.removeAttr('disabled');
            submitButton.val('<?= t('Complete Order'); ?>');
        };
    });


</script>


<div class="store-credit-card-boxpanel panel panel-default">
    <div class="panel-body">
        <div style="display:none;" class="store-payment-errors stripe-payment-errors">
        </div>
        <div class="row">
            <div class="col-xs-12">
                <div class="form-group">
                    <label for="cardNumber"><?= t('Card Number');?></label>
                    <div class="input-group">
                        <input
                            type="tel"
                            class="form-control"
                            id="stripe-cc-number"
                            placeholder="<?= t('Card Number');?>"
                            autocomplete="cc-number"
                            />
                        <span class="input-group-addon"><i class="fa fa-credit-card"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-xs-7 col-md-7">
                <div class="form-group">
                    <label for="cardExpiry"><?= t('Expiration Date');?></label>
                    <input
                        type="tel"
                        class="form-control"
                        id="stripe-cc-exp"
                        placeholder="MM / YY"
                        autocomplete="cc-exp"
                        />
                </div>
            </div>
            <div class="col-xs-5 col-md-5 pull-right">
                <div class="form-group">
                    <label for="cardCVC"><?= t('CV Code');?></label>
                    <input
                        type="tel"
                        class="form-control"
                        id="stripe-cc-cvc"
                        placeholder="<?= t('CVC');?>"
                        autocomplete="off"
                        />
                </div>
            </div>
        </div>
    </div>
</div>