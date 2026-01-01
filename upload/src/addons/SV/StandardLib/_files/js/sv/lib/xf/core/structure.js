// noinspection ES6ConvertVarToLetConst

var SV = window.SV || {};
SV.StandardLib = SV.StandardLib || {};
SV.StandardLib.XF = SV.StandardLib.XF || {};
SV.StandardLib.XF.Tabs = SV.StandardLib.XF.Tabs || {};
SV.extendObject = SV.extendObject || XF.extendObject || jQuery.extend;

;((window, document) =>
{
    "use strict";

    /**
     * @return {HTMLElement}
     */
    function getTarget(handler) {
        // noinspection JSUnresolvedReference
        return handler.target || handler.$target.get(0);
    }

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
            var thisTarget = getTarget(this),
                form = thisTarget.closest('form'),
                escapedInputName = XF.htmlspecialchars(this.options.svStoreSelectedTabInputName.toString()),
                finalInputSelector = '[name="' + escapedInputName + '"]',
                hiddenInput = form.querySelector(finalInputSelector);

            if (hiddenInput === null)
            {
                hiddenInput = document.createElement('input')
                hiddenInput.type = 'hidden';
                hiddenInput.name =  escapedInputName;
                hiddenInput.value = '';

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

            var tab = this.tabs ? this.tabs[offset] : this.$tabs.eq(offset).get(0),
                pane = this.panes ? this.panes[offset] : this.$panes.eq(offset).get(0);
            if (!tab || !pane)
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