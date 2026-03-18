define(['jquery'], function ($) {
    'use strict';

    return {
        init: function (config) {
            var cancelUrl = config.cancelUrl;
            var hasReasons = config.hasReasons;

            var $trigger  = $('#cancel-order-trigger');
            var $overlay  = $('#cancel-order-modal');
            var $orderNum = $('#cancel-modal-order-number');
            var $confirm  = $('#cancel-modal-confirm');
            var $dismiss  = $('#cancel-modal-dismiss');
            var $close    = $('#cancel-modal-close');
            var $reason   = $('#cancel-reason');
            var $reasonErr = $('#cancel-reason-error');
            var $message  = $('#cancel-modal-message');

            var orderId = $trigger.data('order-id');
            var orderNumber = $trigger.data('order-number');

            // Open modal
            $trigger.on('click', function () {
                $orderNum.text(orderNumber);
                $message.hide().removeClass('is-success is-error');
                if ($reason.length) { $reason.val(''); }
                $reasonErr.hide();
                this._open();
            }.bind(this));

            // Close triggers
            $close.add($dismiss).on('click', function () {
                this._close();
            }.bind(this));

            $overlay.on('click', function (e) {
                if ($(e.target).is($overlay)) this._close();
            }.bind(this));

            $(document).on('keydown', function (e) {
                if (e.key === 'Escape') this._close();
            }.bind(this));

            // Confirm cancel
            $confirm.on('click', function () {
                var reason = $reason.length ? $reason.val() : '';

                if (hasReasons && !reason) {
                    $reasonErr.show();
                    $reason.focus();
                    return;
                }
                $reasonErr.hide();

                this._setLoading(true);
                $message.hide();

                var formKey = this._getFormKey();

                $.ajax({
                    url: cancelUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        order_id: orderId,
                        reason: reason,
                        form_key: formKey
                    },
                    success: function (response) {
                        this._setLoading(false);
                        if (response.success) {
                            $message
                                .addClass('is-success')
                                .text(response.message)
                                .show();
                            $confirm.hide();
                            $trigger.prop('disabled', true).addClass('is-cancelled');
                            setTimeout(function () {
                                window.location.reload();
                            }, 2000);
                        } else {
                            $message
                                .addClass('is-error')
                                .text(response.message)
                                .show();
                        }
                    }.bind(this),
                    error: function () {
                        this._setLoading(false);
                        $message.addClass('is-error').text('An error occurred. Please try again.').show();
                    }.bind(this)
                });
            }.bind(this));

            this._$overlay = $overlay;
        },

        _open: function () {
            this._$overlay.removeAttr('aria-hidden').addClass('is-open');
            $('body').addClass('cancel-modal-open');
        },

        _close: function () {
            this._$overlay.attr('aria-hidden', 'true').removeClass('is-open');
            $('body').removeClass('cancel-modal-open');
        },

        _setLoading: function (loading) {
            var $btn = $('#cancel-modal-confirm');
            $btn.find('.cancel-modal__btn-text').toggle(!loading);
            $btn.find('.cancel-modal__spinner').toggle(loading);
            $btn.prop('disabled', loading);
        },

        _getFormKey: function () {
            var match = document.cookie.match(/(?:^|; )form_key=([^;]*)/);
            return match ? decodeURIComponent(match[1]) : '';
        }
    };
});
