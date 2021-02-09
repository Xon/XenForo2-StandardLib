// noinspection JSUnusedLocalSymbols
(function ($, window, document, _undefined)
{
    "use strict";

    XF.Tabs = XF.extend(XF.Tabs, {
        __backup: {
            'activateTab': 'svStandardLib_activateTab'
        },

        options: $.extend({}, XF.Tabs.prototype.options, {
            svStoreSelectedTabInputName: null
        }),

        activateTab: function(offset)
        {
            this.svStandardLib_activateTab(offset);

            this.handleStoringOfSelectedTabIfNeeded(offset);
        },

        _getHiddenInput: function ()
        {
            var $form = this.$target.closest('form'),
                finalInputSelector = '[name="' + XF.htmlspecialchars(this.options.svStoreSelectedTabInputName.toString()) + '"]',
                $hiddenInput = XF.findRelativeIf(finalInputSelector, $form);

            if (!$hiddenInput.length)
            {
                $('<input />', {
                    type: 'hidden',
                    name: XF.htmlspecialchars(this.options.svStoreSelectedTabInputName),
                    value: ''
                }).appendTo($form);
            }

            return XF.findRelativeIf(finalInputSelector, $form);
        },

        /**
         * @param {Number} offset
         */
        handleStoringOfSelectedTabIfNeeded: function (offset)
        {
            if (!this.options.svStoreSelectedTabInputName)
            {
                return;
            }

            var $tab = this.$tabs.eq(offset),
                $pane = this.$panes.eq(offset);
            if (!$tab.length || !$pane.length)
            {
                return;
            }

            var selectedTab = $tab.is('[id]') ? $tab.attr('id') : '';

            this._getHiddenInput().val(selectedTab);
        }
    });
} (jQuery, window, document));