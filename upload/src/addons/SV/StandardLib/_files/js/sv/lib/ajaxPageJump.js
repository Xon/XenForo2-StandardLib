// noinspection ES6ConvertVarToLetConst,JSUnusedLocalSymbols

var SV = window.SV || {};
SV.$ = SV.$ || window.jQuery || null;
SV.extendObject = SV.extendObject || XF.extendObject || jQuery.extend;

!function(window, document) {
    "use strict";
    const $ = SV.$,
        xf22 = typeof XF.on !== 'function';

    /**
     * @return {HTMLElement}
     */
    function getTarget(handler) {
        // noinspection JSUnresolvedReference
        return handler.target || handler.$target.get(0);
    }

    XF.Element.extend('page-jump', {
        options: SV.extendObject({}, XF.PageJump.prototype.options, {
            ajaxFilter: '.sv-ajax-pagination, .sv-dynamic-filter'
        }),
        __backup: {
            'init': 'svRedirectInit',
            'go': 'svRedirectGo',
        },
        /** @type {HTMLElement} */
        svAjaxHandler: null,
        init: function () {
            this.svAjaxHandler = getTarget(this).closest(this.options.ajaxFilter);
            // noinspection JSUnresolvedReference
            this.svRedirectInit();
        },
        go: function () {
            if (this.svAjaxGo()) {
                return;
            }
            // noinspection JSUnresolvedReference
            return this.svRedirectGo();
        },
        /**
         * @return {SV.StandardLib.DynamicFilter|SV.AjaxPagination|null}
         */
        svGetAjaxHandler() {
            if (!this.svAjaxHandler) {
                return null;
            }
            const el = xf22 ? $(this.svAjaxHandler) : this.svAjaxHandler;
            let ajaxHandler = XF.Element.getHandler(el, 'sv-ajax-pagination')
            if (ajaxHandler) {
                return ajaxHandler;
            }
            ajaxHandler = XF.Element.getHandler(el, 'sv-dynamic-filter')
            if (ajaxHandler) {
                return ajaxHandler;
            }

            return null;
        },
        svAjaxGo() {
            const ajaxHandler = this.svGetAjaxHandler();
            if (!ajaxHandler) {
                return false;
            }

            // noinspection JSUnresolvedReference
            /** @type HTMLInputElement */
            const input = xf22 ? this.$input.get(0) : this.input;

            let page = parseInt(input.value, 10)
            if (isNaN(page) || page < 1) {
                page = 1
            }

            const baseUrl = this.options.pageUrl
            const sentinel = this.options.sentinel
            let url = baseUrl.replace(sentinel, page)

            if (url === baseUrl) {
                url = baseUrl.replace(encodeURIComponent(sentinel), page)
            }

            // noinspection JSUnresolvedReference
            ajaxHandler.ajaxLoadPage(page, url);

            getTarget(this).closest('.menu--pageJump')?.remove();

            return true;
        }
    });
} (window, document);