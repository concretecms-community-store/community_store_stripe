<?php defined('C5_EXECUTE') or die(_("Access Denied.")); ?>
<script src="<?= str_replace('/index.php/', '/', URL::to('packages/community_store_stripe/js/jquery.payment.min.js')); ?>"></script>
<script type="text/javascript" src="https://js.stripe.com/v2/"></script>

<script>
    $(window).on('load', function() {
        var form = $('#store-checkout-form-group-payment'),
            submitButton = form.find(".store-btn-complete-order");
        var stripe_errorContainer = form.find('.stripe-payment-errors');

        var cardField = $('#stripe-cc-number'),
            expField = $('#stripe-cc-exp'),
            cvcField = $('#stripe-cc-cvc');

        cardField.payment('formatCardNumber');
        expField.payment('formatCardExpiry');
        cvcField.payment('formatCardCVC');

        cardField.bind("keyup change", function(e) {
            var validcard = $.payment.validateCardNumber($(this).val());
            $(this).closest('.form-group').removeClass('has-success').removeClass('has-error');
            cvcField.closest('.form-group').removeClass('has-success').removeClass('has-error');
            if (validcard) {
                $(this).closest('.form-group').removeClass('has-error').addClass('has-success');
                if ($.payment.cardType($(this).val()) === "amex") {
                    cvcField.attr("placeholder", cvcField.data('amex-placeholder'));
                    cvcField.data("max-allowed", "4");
                } else {
                    cvcField.attr("placeholder", cvcField.data('other-placeholder'));
                    cvcField.data("max-allowed", "3");
                }
            }
            stripe_errorContainer.hide();
        });

        cardField.bind("blur", function(e) {
            if ($(this).val()) {
                var validcard = $.payment.validateCardNumber($(this).val());
                if (!validcard) {
                    $(this).closest('.form-group').addClass('has-error');
                    cvcField.data("max-allowed", "0");
                    cvcField.attr("placeholder", "•••(•)");
                }
            }
        });

        expField.bind("keyup change", function(e) {
            var validcard = $.payment.validateCardNumber($(this).val());
            $(this).closest('.form-group').removeClass('has-success').removeClass('has-error');
            var expiry = $(this).payment('cardExpiryVal');
            var validexpiry = $.payment.validateCardExpiry(expiry.month, expiry.year);

            if (validexpiry) {
                $(this).closest('.form-group').removeClass('has-error').addClass('has-success');
            }
            stripe_errorContainer.hide();
        });

        expField.bind("blur", function(e) {
            if ($(this).val()) {
                var expiry = $(this).payment('cardExpiryVal');
                var validexpiry = $.payment.validateCardExpiry(expiry.month, expiry.year);
                if (!validexpiry) {
                    $(this).closest('.form-group').addClass('has-error');
                }
            }
        });

        cvcField.bind("keyup change", function(e) {
            var validcv = $.payment.validateCardCVC($(this).val());
            var maxAllowed = $(this).data('max-allowed');
            var validLength = (maxAllowed == 0 && $(this).val().length <= 4) || (maxAllowed >= 0 && $(this).val().length == maxAllowed) ? true : false;
            $(this).closest('.form-group').removeClass('has-success').removeClass('has-error');
            if (validcv && validLength) {
                $(this).closest('.form-group').removeClass('has-error').addClass('has-success');
            }
            stripe_errorContainer.hide();
        });

        cvcField.bind("blur", function(e) {
            if ($(this).val()) {
                var validcv = $.payment.validateCardCVC($(this).val());
                var maxAllowed = $(this).data('max-allowed');
                var validLength = (maxAllowed == 0 && $(this).val().length <= 4) || (maxAllowed >= 0 && $(this).val().length == maxAllowed) ? true : false;
                if (!validcv || !validLength) {
                    $(this).closest('.form-group').addClass('has-error');
                }
            }
        });
        Stripe.setPublishableKey('<?= $publicAPIKey; ?>');

        form.submit(function(e) {
            var currentpmid = $('input[name="payment-method"]:checked:first').data('payment-method-id');

            if (currentpmid == <?= $pmID; ?>) {
                e.preventDefault();

                var allvalid = true;

                var validcard = $.payment.validateCardNumber(cardField.val());

                if (!validcard) {
                    cardField.closest('.form-group').addClass('has-error');
                    allvalid = false;
                } else {
                    cardField.closest('.form-group').removeClass('has-error');
                }

                var expiry = expField.payment('cardExpiryVal');
                var validexpiry = $.payment.validateCardExpiry(expiry.month, expiry.year);

                if (!validexpiry) {
                    expField.closest('.form-group').addClass('has-error');
                    allvalid = false;
                } else {
                    expField.closest('.form-group').removeClass('has-error');
                }

                var validcv = $.payment.validateCardCVC(cvcField.val());
                var maxAllowed = cvcField.data('max-allowed');
                var validLength = (maxAllowed == 0 && cvcField.val().length <= 4) || (maxAllowed >= 0 && cvcField.val().length == maxAllowed) ? true : false;
                if (!validcv || !validLength) {
                    cvcField.closest('.form-group').addClass('has-error');
                    allvalid = false;
                } else {
                    cvcField.closest('.form-group').removeClass('has-error');
                }

                if (!allvalid) {
                    if (!validcard) {
                        cardField.focus()
                    } else {
                        if (!validexpiry) {
                            expField.focus()
                        } else {
                            if (!validcv) {
                                cvcField.focus()
                            }
                        }
                    }

                    return false;
                }

                // Clear previous errors
                stripe_errorContainer.empty();
                stripe_errorContainer.hide();

                // Disable the submit button to prevent multiple clicks
                submitButton.prop('disabled', true);
                submitButton.val('<?= t('Processing...'); ?>');

                var ccData = {
                    number: cardField.val(),
                    cvc: cvcField.val(),
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
                    <label for="cardNumber"><?= t('Card Number'); ?></label>
                    <div class="input-group">
                        <input
                            type="tel"
                            class="form-control"
                            id="stripe-cc-number"
                            placeholder="•••• •••• •••• ••••"
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
                    <label for="cardExpiry"><?= t('Expiration Date'); ?></label>
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
                    <label for="cardCVC"><?= t('CV Code'); ?></label>
                    <input
                        type="tel"
                        class="form-control"
                        id="stripe-cc-cvc"
                        placeholder="•••(•)"
                        data-other-placeholder="••• <?= t('3 numbers on the back'); ?>"
                        data-amex-placeholder="•••• <?= t('4 numbers on the front'); ?>"
                        data-max-allowed="0"
                        autocomplete="off"
                        />
                </div>
            </div>
        </div>
    </div>
</div>