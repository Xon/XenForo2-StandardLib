var SV = window.SV || {};

!function($, window, document)
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

        inOverlay: false,
        changeTimer: null,
        xhr: null,

        init: function()
        {
            this.inOverlay = this.$target.parents('.overlay-container').length  !== 0;

            if (!this.options.contentWrapper)
            {
                console.error('No content wrapper query expression defined');
                return null;
            }

            this.perPageDropdown = this.$target.find(this.options.perPageDropdown);
            if (this.perPageDropdown.length)
            {
                this.perPageDropdown.on('change', XF.proxy(this, 'perPageChange'));
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
            var value = this.perPageDropdown.val();

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

            var $finalUrlInput = this.$target.find('input[type="hidden"][name="final_url"]');
            if (!$finalUrlInput.length)
            {
                console.error('No final URL input was provided.');
                return;
            }

            var finalUrl = $finalUrlInput.val();
            if (!finalUrl)
            {
                console.error('No final URL available.');
                return;
            }

            var currentUrl = new Url(finalUrl);
            currentUrl.query['per_page'] = this.perPageDropdown.val()

            this.xhr = XF.ajax('post', currentUrl.toString(), {}, XF.proxy(this, 'onLoad'));
        },

        onLoad: function(result)
        {
            this.xhr = null;

            var oldPageNavWrapper = this.getPageNavWrapper();
            if (!oldPageNavWrapper)
            {
                return;
            }

            var oldContentWrapper = this.getContentWrapper();
            if (!oldContentWrapper)
            {
                return;
            }

            var $result = $($.parseHTML(result.html.content)),
                newPageNavWrapper = $result.find(this.options.pageNavWrapper),
                newContentWrapper = $result.find(this.options.contentWrapper);
            if (!newPageNavWrapper.length)
            {
                oldPageNavWrapper.empty();
                return;
            }

            if (!newContentWrapper.length)
            {
                oldContentWrapper.empty();
                return;
            }

            oldPageNavWrapper.html(newPageNavWrapper.html());
            oldContentWrapper.html(newContentWrapper.html());

            if (this.inOverlay)
            {
                return;
            }

            var $finalUrlInput = $result.find('input[type="hidden"][name="final_url"]');
            if (!$finalUrlInput.length)
            {
                console.error('No final URL input was provided.');
                return;
            }

            var finalUrl = $finalUrlInput.val();
            if (!finalUrl)
            {
                console.error('No final URL available.');
                return;
            }

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
         * @returns {null|{length}|*|jQuery|HTMLElement}
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

            var pageNavWrapper = this.$target.find(this.options.pageNavWrapper);
            if (!pageNavWrapper.length)
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
         * @returns {null|{length}|*|jQuery|HTMLElement}
         */
        getContentWrapper: function(logNotFound)
        {
            logNotFound = typeof logNotFound === 'undefined' ? true : logNotFound;
            var contentWrapper = this.$target.find(this.options.contentWrapper);
            if (!contentWrapper.length)
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
}
(jQuery, window, document);
