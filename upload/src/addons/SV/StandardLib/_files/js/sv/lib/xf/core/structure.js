// noinspection JSUnusedLocalSymbols
var SV = window.SV || {};
SV.StandardLib = SV.StandardLib || {};
SV.StandardLib.XF = SV.StandardLib.XF || {};
SV.StandardLib.XF.Tabs = SV.StandardLib.XF.Tabs || {};
SV.$ = SV.$ || window.jQuery || null;
SV.extendObject = SV.extendObject || XF.extendObject || jQuery.extend;

;((window, document) =>
{
    "use strict";
    var $ = SV.$;

    SV.StandardLib.XF.Tabs.BaseOpts = {
        svStoreSelectedTabInputName: null
    }

    XF.Tabs = XF.extend(XF.Tabs, {
        __backup: {
            'activateTab': 'svStandardLib_activateTab'
        },
        options: SV.extendObject({}, XF.Tabs.prototype.options, SV.StandardLib.XF.Tabs.BaseOpts),

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
            var thisTarget = this.target || this.$target.get(0),
                form = thisTarget.closest('form'),
                escapedInputName = XF.htmlspecialchars(this.options.svStoreSelectedTabInputName.toString()),
                finalInputSelector = '[name="' + escapedInputName + '"]',
                hiddenInput = form.querySelector(finalInputSelector);

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

            return form.querySelector(finalInputSelector);
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