// noinspection ES6ConvertVarToLetConst
var SV = window.SV || {};
// noinspection JSUnusedLocalSymbols
(function (window, document, _undefined)
{
    "use strict";
    const $ = SV.$,
        xf22 = typeof XF.on !== 'function',
        trigger = xf22 ? function (target, event, data) {
            return $(target).trigger(event, data)
        }: XF.trigger;

    /**
     * @return {HTMLElement}
     */
    function getTarget(handler) {
        // noinspection JSUnresolvedReference
        return handler.target || handler.$target.get(0);
    }

    SV.LinkPostClick = XF.Event.newHandler({
        eventType: 'click',
        eventNameSpace: 'XFLinkPostClick',
        options: {},

        init() {
        },

        /**
         * @param {PointerEvent} e
         */
        click(e) {
            /** @type {HTMLLinkElement} */
            const target = getTarget(this);
            const href = target.href
            if (!href) {
                console.error('No href found for %o', target)
                return
            }

            const csrf = XF.config.csrf
            if (!csrf) {
                console.error('No CSRF found for %o', target)
                return
            }

            e.preventDefault()

            const form = document.createElement('form');
            form.method = 'post';
            form.action = href;
            form.style.display = 'none';
            document.body.append(form)

            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_xfToken';
            csrfInput.value = csrf;
            form.append(csrfInput)

            if (trigger(form, 'submit')) {
                form.submit()
            }

            form.remove()
        }
    })

    if ('StyleVariation' in XF) {
        const oldUpdateVariation = XF.StyleVariation.updateVariation;
        XF.StyleVariation.updateVariation = function (variation) {
            const oldAjax = XF.ajax;
            XF.ajax = function (method, url, data = {}, successCallback, options = {}) {
                return oldAjax('POST', url, data, successCallback, options);
            }
            try {
                oldUpdateVariation(variation);
            } finally {
                XF.ajax = oldAjax;
            }
        };
    }

    XF.Event.register('click', 'link-post', 'SV.LinkPostClick')
} (window, document));
