// noinspection ES6ConvertVarToLetConst
var SV = window.SV || {};
// XF22 compat shim
/** @type jQuery */
SV.$ = SV.$ || window.jQuery || null;

// noinspection JSUnusedLocalSymbols
(function (window, document, _undefined)
{
    "use strict";
    const $ = SV.$,
        xf22 = typeof XF.on !== 'function',
        on = xf22 ? function (element, namespacedEvent, handler) {
            $(element).on(namespacedEvent, handler);
        } : XF.on;
    /**
     * @return {HTMLElement}
     */
    function getTarget(handler) {
        // noinspection JSUnresolvedReference
        return handler.target || handler.$target.get(0);
    }

    SV.TabNav = XF.Element.newHandler({
        init: function() {
            const target = getTarget(this);
            on(target, 'focus', this.onFocus.bind(this));
            on(target, 'keyup', this.onKeyUp.bind(this));
        },
        /**
         * @param {FocusEvent} e
         */
        onFocus: function(e) {
            const target = getTarget(this);
            if (target.classList.contains('is-active')) {
                return;
            }

            target.click();
            target.focus();
        },
        /**
         * @param {KeyboardEvent} e
         */
        onKeyUp: function(e) {
            const target = getTarget(this);
            if (e.key === 'Enter' || (e.key === 'Escape' && target.classList.contains('is-active'))) {
                target.click();
                target.focus();
            }
        },
    });

    XF.Element.register('sv-tab-nav', 'SV.TabNav');
} (window, document));
