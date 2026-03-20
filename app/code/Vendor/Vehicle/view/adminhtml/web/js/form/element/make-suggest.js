define([
    'Magento_Ui/js/form/element/abstract',
    'jquery',
    'jquery/ui'
], function (Abstract, $) {
    'use strict';

    return Abstract.extend({
        defaults: {
            suggestUrl: ''
        },

        initialize: function () {
            this._super();
            this.loadSuggestions();
            return this;
        },

        loadSuggestions: function () {
            var self = this;

            if (!this.suggestUrl) {
                return;
            }

            $.ajax({
                url: this.suggestUrl,
                type: 'GET',
                dataType: 'json',
                success: function (makes) {
                    self.initAutocomplete(makes);
                }
            });
        },

        initAutocomplete: function (makes) {
            var inputEl = $('[name="make"]');

            if (!inputEl.length) {
                // Retry after DOM is ready
                setTimeout(function () {
                    $('[name="make"]').autocomplete({
                        source: makes,
                        minLength: 0,
                        delay: 0
                    }).on('focus', function () {
                        $(this).autocomplete('search', '');
                    });
                }, 500);
                return;
            }

            inputEl.autocomplete({
                source: makes,
                minLength: 0,
                delay: 0
            }).on('focus', function () {
                $(this).autocomplete('search', '');
            });
        }
    });
});
