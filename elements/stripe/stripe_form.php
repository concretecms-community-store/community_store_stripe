<?php defined('C5_EXECUTE') or die(_("Access Denied."));?>
<script src="<?= URL::to('packages/community_store_stripe/js/jquery.payment.min.js');?>"></script>
<script type="text/javascript" src="https://js.stripe.com/v2/"></script>

<script>
    $(function() {

        var form = $('#store-checkout-form-group-payment'),
            submitButton = form.find(".store-btn-complete-order"),
            errorContainer = form.find('.store-payment-errors'),
            errorList = errorContainer.find('ul'),
            errorHeading = errorContainer.find('h3');

        $('#cc-number').payment('formatCardNumber');
        $('#cc-exp').payment('formatCardExpiry');
        $('#cc-cvc').payment('formatCardCVC');

        $('#cc-number').bind("keyup change", function(e) {
            var validcard = $.payment.validateCardNumber($(this).val());

            if (validcard) {
                $(this).closest('.form-group').removeClass('has-error');
            }
            errorContainer.hide();
        });

        $('#cc-exp').bind("keyup change", function(e) {
            var validcard = $.payment.validateCardNumber($(this).val());

            var expiry = $(this).payment('cardExpiryVal');
            var validexpiry = $.payment.validateCardExpiry(expiry.month, expiry.year);

            if (validexpiry) {
                $(this).closest('.form-group').removeClass('has-error');
            }
            errorContainer.hide();
        });

        $('#cc-cvc').bind("keyup change", function(e) {
            var validcv = $.payment.validateCardCVC($(this).val());

            if (validcv) {
                $('#cc-cvc').closest('.form-group').removeClass('has-error');
            }
            errorContainer.hide();
        });

        Stripe.setPublishableKey('<?= $publicAPIKey; ?>');

        form.submit(function(e) {
            var currentpmid = $('input[name="payment-method"]:checked:first').data('payment-method-id');

            if (currentpmid == <?= $pmID; ?>) {
                e.preventDefault();

                var allvalid = true;

                var validcard = $.payment.validateCardNumber($('#cc-number').val());

                if (!validcard) {
                    $('#cc-number').closest('.form-group').addClass('has-error');
                    allvalid = false;
                } else {
                    $('#cc-number').closest('.form-group').removeClass('has-error');
                }

                var expiry = $('#cc-exp').payment('cardExpiryVal');
                var validexpiry = $.payment.validateCardExpiry(expiry.month, expiry.year);

                if (!validexpiry) {
                    $('#cc-exp').closest('.form-group').addClass('has-error');
                    allvalid = false;
                } else {
                    $('#cc-exp').closest('.form-group').removeClass('has-error');
                }

                var validcv = $.payment.validateCardCVC($('#cc-cvc').val());

                if (!validcv) {
                    $('#cc-cvc').closest('.form-group').addClass('has-error');
                    allvalid = false;
                } else {
                    $('#cc-cvc').closest('.form-group').removeClass('has-error');
                }

                if (!allvalid) {
                    if (!validcard) {
                        $('#cc-number').focus()
                    } else {
                        if (!validexpiry) {
                            $('#cc-exp').focus()
                        } else {
                            if (!validcv) {
                                $('#cc-cvc').focus()
                            }
                        }
                    }

                    return false;
                }

                // Clear previous errors
                errorList.empty();
                errorHeading.empty();
                errorContainer.hide();

                // Disable the submit button to prevent multiple clicks
                submitButton.attr({disabled: true});
                submitButton.val('<?= t('Processing...'); ?>');

                var ccData = {
                    number: $('#cc-number').val(),
                    cvc: $('#cc-cvc').val(),
                    exp_month: expiry.month,
                    exp_year: expiry.year
                };

                Stripe.card.createToken(ccData, function stripeResponseHandler(status, response) {
                    if (response.error) {
                        handleError(response);
                    } else {
                        handleSuccess(response);
                    }
                });

            } else {
                // allow form to submit normally
            }
        });

        function handleSuccess(response) {
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

        function handleError(response) {
            errorHeading.text(response.error.message);
            errorContainer.show();

            // Re-enable the submit button
            submitButton.removeAttr('disabled');
            submitButton.val('<?= t('Complete Order'); ?>');
        };
    });


</script>


<div class="store-credit-card-boxpanel panel panel-default">
    <div class="panel-body">
        <div class="row">
            <div class="col-xs-12">
                <div class="form-group">
                    <label for="cardNumber"><?= t('Card Number');?></label>
                    <div class="input-group">
                        <input
                            type="tel"
                            class="form-control"
                            id="cc-number"
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
                        id="cc-exp"
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
                        id="cc-cvc"
                        placeholder="<?= t('CVC');?>"
                        autocomplete="off"
                        />
                </div>
            </div>
        </div>
        <div style="display:none;" class="store-payment-errors">
            <h3></h3>
            <ul></ul>
        </div>
    </div>
</div>