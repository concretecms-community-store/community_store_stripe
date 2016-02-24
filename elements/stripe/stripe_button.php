<?php defined('C5_EXECUTE') or die(_("Access Denied."));?>
<script src="https://checkout.stripe.com/checkout.js"></script>
<input type="hidden" value="" name="stripeToken" id="stripeToken" />
<script>
    $(document).ready(function() {
        var handler = StripeCheckout.configure({
            key: '<?= $publicAPIKey; ?>',
            locale: 'auto',
            token: function (token) {
                // Use the token to create the charge with a server-side script.
                // You can access the token ID with `token.id`
                $('#stripeToken').val(token.id);
                $('#store-checkout-form-group-payment').submit();
            }
        });

        $('.store-btn-complete-order').on('click', function (e) {
            // Open Checkout with further options
            var currentpmid = $('input[name="payment-method"]:checked:first').data('payment-method-id');

            if (currentpmid == <?= $pmID; ?>) {
                handler.open({
                    currency: "<?= strtolower($currency);?>",
                    amount: $('.store-total-amount').data('total-cents') ?  $('.store-total-amount').data('total-cents') : '<?= ($amount); ?>',
                    email: $('#store-email').val() ? $('#store-email').val() : '<?= ($email); ?>'
                });
                e.preventDefault();
            }
        });

        // Close Checkout on page navigation
        $(window).on('popstate', function () {
            handler.close();
        });
    });
</script>