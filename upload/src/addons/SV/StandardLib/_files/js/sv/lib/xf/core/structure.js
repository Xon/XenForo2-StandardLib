// noinspection JSUnusedLocalSymbols
;((window, document) =>
{
    "use strict";

    XF.Tabs = XF.extend(XF.Tabs, {
        __backup: {
            'activateTab': 'svStandardLib_activateTab'
        },

        // With 2.2 support
        options: (typeof $ !== "undefined") ? $.extend({}, XF.Tabs.prototype.options, {
            svStoreSelectedTabInputName: null
        }) : XF.extendObject(true, XF.Tabs.prototype.options, {
            svStoreSelectedTabInputName: null
        }),

        activateTab: function(offset)
        {
            this.svStandardLib_activateTab(offset);

            this.handleStoringOfSelectedTabIfNeeded(offset);
        },

        /**
         * @returns {HTMLElement}
         *
         * @private
         */
        _getHiddenInput: function ()
        {
            var target = this.$target ? this.$target.get(0) : this.target,
                form = XF.findRelativeIf('form', target),
                escapedInputName = XF.htmlspecialchars(this.options.svStoreSelectedTabInputName.toString()),
                finalInputSelector = '[name="' + escapedInputName + '"]',
                hiddenInput = XF.findRelativeIf(finalInputSelector, form);

            if (hiddenInput !== null && (typeof hiddenInput.get === "function") && !hiddenInput.length) // if running XF 2.2 and no element found with the selector
            {
                hiddenInput = null
            }

            if (hiddenInput === null)
            {
                if (typeof XF.createElement === "function")
                {
                    hiddenInput = XF.createElement('input', {
                        type: 'hidden',
                        name: escapedInputName,
                        value: ''
                    })
                }
                else // jQuery - XF 2.2
                {
                    hiddenInput = $('<input />', {
                        type: 'hidden',
                        name: escapedInputName,
                        value: ''
                    }).get(0)
                }

                form.append(hiddenInput)
            }

            return XF.findRelativeIf(finalInputSelector, form);
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

            var tab = this.tabs[offset],
                pane = this.panes[offset];
            if ((tab === null) || (pane === null))
            {
                return;
            }

            var selectedTab = '';
            if (tab.hasAttribute('id'))
            {
                selectedTab = tab.getAttribute('id')
            }

            this._getHiddenInput().value = selectedTab
        }
    });
})(window, document)