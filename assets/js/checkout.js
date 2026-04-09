/**
 * Payvessel WooCommerce Checkout JS
 *
 * Handles popup checkout and payment verification
 */
(function($) {
    'use strict';

    var PayvesselCheckout = {
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            // Handle popup checkout
            $(document.body).on('click', '.payvessel-pay-button', this.handlePopupPayment);
            
            // Listen for checkout form submission
            $('form.checkout').on('checkout_place_order_payvessel', this.onCheckoutSubmit);

            // Handle pay page
            if ($('.payvessel-popup-checkout').length) {
                this.initPopupOnPayPage();
            }
        },

        /**
         * Handle checkout form submission
         */
        onCheckoutSubmit: function(e) {
            if (payvessel_params.payment_method !== 'popup') {
                return true; // Let redirect happen
            }

            // For popup, we need to handle differently via AJAX
            return true;
        },

        /**
         * Initialize popup on pay page
         */
        initPopupOnPayPage: function() {
            var $container = $('.payvessel-popup-checkout');
            var accessCode = $container.data('access-code');
            var orderId = $container.data('order-id');
            var reference = $container.data('reference');

            if (!accessCode || typeof PayvesselCheckout === 'undefined') {
                // Fallback to redirect
                window.location.href = 'https://checkout.payvessel.com/' + accessCode;
                return;
            }

            this.openPopup(accessCode, orderId, reference);
        },

        /**
         * Handle popup payment button click
         */
        handlePopupPayment: function(e) {
            e.preventDefault();

            var $button = $(this);
            var accessCode = $button.data('access-code');
            var orderId = $button.data('order-id');
            var reference = $button.data('reference');

            if (!accessCode) {
                alert('Invalid payment configuration');
                return;
            }

            PayvesselCheckout.openPopup(accessCode, orderId, reference);
        },

        /**
         * Open Payvessel popup checkout
         */
        openPopup: function(accessCode, orderId, reference) {
            var self = this;

            // Check if SDK is loaded
            if (typeof window.PayvesselCheckout === 'undefined' || 
                typeof window.PayvesselCheckout.Checkout === 'undefined') {
                console.warn('Payvessel SDK not loaded, falling back to redirect');
                window.location.href = 'https://checkout.payvessel.com/' + accessCode;
                return;
            }

            try {
                var checkout = window.PayvesselCheckout.Checkout({
                    api_key: '', // Not needed for popup with access code
                });

                checkout.loadCheckout({
                    access_code: accessCode,
                    onSuccess: function(response) {
                        self.handlePaymentSuccess(response, orderId, reference);
                    },
                    onError: function(error) {
                        self.handlePaymentError(error);
                    },
                    onClose: function() {
                        self.handlePopupClose(orderId, reference);
                    }
                });
            } catch (error) {
                console.error('Error opening popup:', error);
                // Fallback to redirect
                window.location.href = 'https://checkout.payvessel.com/' + accessCode;
            }
        },

        /**
         * Handle successful payment
         */
        handlePaymentSuccess: function(response, orderId, reference) {
            this.verifyPayment(orderId, reference || response.reference);
        },

        /**
         * Handle payment error
         */
        handlePaymentError: function(error) {
            console.error('Payment error:', error);
            alert(error.message || 'Payment failed. Please try again.');
        },

        /**
         * Handle popup close
         */
        handlePopupClose: function(orderId, reference) {
            // Verify payment in case it was completed
            if (reference) {
                this.verifyPayment(orderId, reference);
            }
        },

        /**
         * Verify payment with server
         */
        verifyPayment: function(orderId, reference) {
            var self = this;

            $.ajax({
                url: payvessel_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'payvessel_verify_payment',
                    order_id: orderId,
                    reference: reference,
                    nonce: payvessel_params.verify_nonce
                },
                beforeSend: function() {
                    self.showLoader();
                },
                success: function(response) {
                    self.hideLoader();
                    if (response.success && response.data.redirect_url) {
                        window.location.href = response.data.redirect_url;
                    } else {
                        alert(response.data.message || 'Payment verification failed');
                    }
                },
                error: function() {
                    self.hideLoader();
                    // Redirect to order received page anyway
                    window.location.reload();
                }
            });
        },

        /**
         * Show loading overlay
         */
        showLoader: function() {
            if ($('#payvessel-loader').length === 0) {
                $('body').append(
                    '<div id="payvessel-loader" style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(255,255,255,0.8);z-index:99999;display:flex;align-items:center;justify-content:center;">' +
                    '<div style="text-align:center;">' +
                    '<div style="width:40px;height:40px;border:3px solid #f3f3f3;border-top:3px solid #ff6b00;border-radius:50%;animation:payvessel-spin 1s linear infinite;margin:0 auto 10px;"></div>' +
                    '<p>Verifying payment...</p>' +
                    '</div>' +
                    '</div>' +
                    '<style>@keyframes payvessel-spin{0%{transform:rotate(0deg)}100%{transform:rotate(360deg)}}</style>'
                );
            }
            $('#payvessel-loader').show();
        },

        /**
         * Hide loading overlay
         */
        hideLoader: function() {
            $('#payvessel-loader').hide();
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        PayvesselCheckout.init();
    });

})(jQuery);
