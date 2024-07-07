var SV = window.SV || {};

;((window, document) =>
{
    "use strict";

    // ################################## TICKET MANAGE HANDLER ###########################################

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
            var thisTarget = this.target || this.$target.get(0);
            this.inOverlay = XF.findRelativeIf('< .overlay-container', thisTarget) !== null;

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
                if (typeof this.perPageDropdown.on !== "undefined") // XF 2.2 only
                {
                    this.perPageDropdown.on('change', XF.proxy(this, 'perPageChange'));
                }
                else
                {
                    XF.on(this.perPageDropdown, 'change', this.perPageChange.bind(this));
                }
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

            this.changeTimer = setTimeout(XF.proxy(this, 'perPageOnTimer'), 200);
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

            this.xhr = XF.ajax('post', currentUrl.toString(), {}, XF.proxy(this, 'onLoad'));
        },

        onLoad: function(result)
        {
            this.xhr = null;

            var oldPageNavWrapper = this.getPageNavWrapper();
            if (oldPageNavWrapper === null)
            {
                return;
            }

            var oldContentWrapper = this.getContentWrapper();
            if (oldContentWrapper === null)
            {
                return;
            }

            var tmpResult;
            if (typeof $ !== "undefined") // XF 2.2 and earlier
            {
                tmpResult = $($.parseHTML(result.html.content));
            }
            else
            {
                tmpResult = XF.createElementFromString(result.html.content.trim());
            }

            var newPageNavWrapper = XF.findRelativeIf(this.options.pageNavWrapper, tmpResult),
                newContentWrapper = XF.findRelativeIf(this.options.contentWrapper, tmpResult);
            if (newPageNavWrapper === null)
            {
                oldPageNavWrapper.innerHTML = '';
                return;
            }

            if (newContentWrapper === null)
            {
                oldContentWrapper.innerHTML = '';
                return;
            }

            oldPageNavWrapper.innerHTML = newPageNavWrapper.innerHTML;
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
         * @param {Boolean} logNotFound
         *
         * @returns {null|HTMLElement}
         */
        getPageNavWrapper: function(logNotFound)
        {
            logNotFound = typeof logNotFound === 'undefined' ? true : logNotFound;
            if (!this.options.pageNavWrapper)
            {
                if (logNotFound)
                {
                    console.error('No pagination wrapper query expression defined');
                }

                return null;
            }

            var thisTarget = this.target || this.$target.get(0),
                pageNavWrapper = thisTarget.querySelector((this.options.pageNavWrapper));
            if (pageNavWrapper === null)
            {
                if (logNotFound)
                {
                    console.error('No old pagination wrapper available');
                }

                return null;
            }

            return pageNavWrapper;
        },

        /**
         * @param {Boolean} logNotFound
         *
         * @returns {null|HTMLElement}
         */
        getContentWrapper: function(logNotFound)
        {
            logNotFound = typeof logNotFound === 'undefined' ? true : logNotFound;

            var thisTarget = this.target || this.$target.get(0),
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