// noinspection ES6ConvertVarToLetConst,JSUnusedLocalSymbols

var SV = window.SV || {};
// XF22 compat shim
/** @type jQuery */
SV.$ = SV.$ || window.jQuery || null;

;((window, document) =>
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

    SV.DropdownSubmit = XF.Element.newHandler({
        options: {
            pageNavWrapper: '.block-outer--page-nav-wrapper',
            contentWrapper: null,
            perPageDropdown: 'select[name="per_page"]',
            perPageCookiePrefix: null
        },

        perPageDropdown: null,
        finalUrl: null,
        inOverlay: false,
        changeTimer: null,
        xhr: null,

        init: function()
        {
            var thisTarget = getTarget(this);
            this.inOverlay = thisTarget.closest('.overlay-container') !== null;

            if (!this.options.contentWrapper)
            {
                console.error('No content wrapper query expression defined');
                return null;
            }

            var finalUrlInput = thisTarget.querySelector('input[type="hidden"][name="final_url"]');
            if (finalUrlInput === null)
            {
                console.error('No final URL input was provided.');
                return;
            }

            var finalUrl = finalUrlInput.value;
            if (!finalUrl)
            {
                console.error('No final URL available.');
                return;
            }

            this.finalUrl = finalUrl;

            this.perPageDropdown = thisTarget.querySelector(this.options.perPageDropdown);
            if (this.perPageDropdown !== null)
            {
                on(this.perPageDropdown, 'change', this.perPageChange.bind(this));
            }
        },

        perPageChange: function()
        {
            if (this.changeTimer)
            {
                clearTimeout(this.changeTimer);
            }

            if (this.xhr)
            {
                this.xhr.abort();
                this.xhr = null;
            }

            this.changeTimer = setTimeout(this.perPageOnTimer.bind(this), 200);
        },

        perPageOnTimer: function()
        {
            var value = this.perPageDropdown.value;
            if (!value)
            {
                return;
            }

            if (this.options.perPageCookiePrefix !== null)
            {
                XF.Cookie.set(
                    this.options.perPageCookiePrefix + 'per_page',
                    value,
                    new Date(new Date().setFullYear(new Date().getFullYear() + 1))
                );
            }

            var currentUrl = new Url(this.finalUrl);
            currentUrl.query['per_page'] = this.perPageDropdown.value;

            this.xhr = XF.ajax('post', currentUrl.toString(), {}, this.onLoad.bind(this));
        },

        onLoad: function(result)
        {
            this.xhr = null;

            var oldContentWrapper = this.getContentWrapper();
            if (oldContentWrapper === null)
            {
                return;
            }

            var oldPageNavWrappers = this.getPageNavWrappers();
            var tmpResult;
            if (xf22)
            {
                tmpResult = $.parseHTML('<div>' + result.html.content + '</div>');
                tmpResult = tmpResult[0];
            }
            else
            {
                tmpResult = XF.createElementFromString(result.html.content.trim());
            }

            var newPageNavWrapper = tmpResult.querySelector(this.options.pageNavWrapper),
                newContentWrapper = tmpResult.querySelector(this.options.contentWrapper);
            if (newPageNavWrapper === null)
            {
                oldPageNavWrappers.forEach(function (oldPageNavWrapper) {
                    oldPageNavWrapper.innerHTML = '';
                });
                return;
            }
            if (newContentWrapper === null)
            {
                oldContentWrapper.innerHTML = '';
                return;
            }

            oldPageNavWrappers.forEach(function (oldPageNavWrapper) {
                oldPageNavWrapper.innerHTML = newPageNavWrapper.innerHTML;
            });
            oldContentWrapper.innerHTML = newContentWrapper.innerHTML;

            if (this.inOverlay)
            {
                return;
            }

            var finalUrlInput = tmpResult.querySelector('input[type="hidden"][name="final_url"]')
            if (finalUrlInput === null)
            {
                console.error('No final URL input was provided.');
                return;
            }

            var finalUrl = finalUrlInput.value;
            if (!finalUrl)
            {
                console.error('No final URL available.');
                return;
            }

            this.finalUrl = finalUrl;

            if ('pushState' in window.history)
            {
                window.history.pushState({
                    state: 1,
                    rand: Math.random()
                }, '', finalUrl);
            }
            else
            {
                window.location = finalUrl; // force
            }
        },

        /**
         * @returns {HTMLElement[]}
         */
        getPageNavWrappers: function()
        {
            var thisTarget = getTarget(this);

            return this.options.pageNavWrapper ? thisTarget.querySelectorAll(this.options.pageNavWrapper) :  [];
        },

        /**
         * @param {Boolean=} logNotFound
         *
         * @returns {null|HTMLElement}
         */
        getContentWrapper: function(logNotFound)
        {
            logNotFound = typeof logNotFound === 'undefined' ? true : logNotFound;

            var thisTarget = getTarget(this),
                contentWrapper = thisTarget.querySelector((this.options.contentWrapper));
            if (contentWrapper === null)
            {
                if (logNotFound)
                {
                    console.error('No old content wrapper available');
                }

                return null;
            }

            return contentWrapper;
        },
    });

    XF.Element.register('sv-dropdown-submit', 'SV.DropdownSubmit');
})(window, document)