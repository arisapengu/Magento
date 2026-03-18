define(['jquery'], function ($) {
    'use strict';

    return {
        init: function (config) {
            var cookieName = config.cookieName || 'vendor_welcome_popup_shown';
            var cookieDays = config.cookieDays || 7;

            if (this._getCookie(cookieName)) {
                return;
            }

            var $overlay = $('#welcome-popup-overlay');

            setTimeout(function () {
                $overlay.fadeIn(300);
                $('body').addClass('popup-open');
            }, 500);

            $('#welcome-popup-close, #welcome-popup-accept').on('click', function () {
                $overlay.fadeOut(200, function () {
                    $('body').removeClass('popup-open');
                });
                if (cookieDays > 0) {
                    self._setCookie(cookieName, '1', cookieDays);
                }
            });

            $overlay.on('click', function (e) {
                if ($(e.target).is($overlay)) {
                    $overlay.fadeOut(200, function () {
                        $('body').removeClass('popup-open');
                    });
                    if (cookieDays > 0) {
                        self._setCookie(cookieName, '1', cookieDays);
                    }
                }
            });

            var self = this;
        },

        _getCookie: function (name) {
            var match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
            return match ? decodeURIComponent(match[1]) : null;
        },

        _setCookie: function (name, value, days) {
            var expires = '';
            if (days) {
                var date = new Date();
                date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
                expires = '; expires=' + date.toUTCString();
            }
            document.cookie = name + '=' + encodeURIComponent(value) + expires + '; path=/; SameSite=Lax';
        }
    };
});
