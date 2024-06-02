var SV = window.SV || {};

!function($, window, document)
{
    // ################################## --- ###########################################
    SV.AjaxPagination = XF.Element.newHandler({
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
        lastPageSelected: null,

        init: function()
        {
            this.inOverlay = this.$target.parents('.overlay-container').length  !== 0;
            logNotFound = typeof logNotFound === 'undefined' ? true : logNotFound;
            if (!this.options.contentWrapper)
            {
                if (logNotFound)
                {
                    console.error('No content wrapper query expression defined');
                }

                return null;
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

            this.finalUrl = finalUrl;

            var existingPage = null,
                $pageNavWrapper = this.getPageNavWrapper();
            if ($pageNavWrapper)
            {
                existingPage = this.getPageFromAhref($pageNavWrapper.find('.pageNav-page--current > a').first());
            }
            this.lastPageSelected = (typeof existingPage === 'number') ? existingPage : 1;

            this.shimDynamicPageNav();

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

            var currentUrl = new Url(this.finalUrl);
            currentUrl.query['per_page'] = this.perPageDropdown.val()

            XF.ajax('GET', currentUrl.toString(), {}, XF.proxy(this, '_paginationAjaxResponse'));
        },

        _paginationAjaxResponse: function(result)
        {
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
            this.shimDynamicPageNav();

            oldContentWrapper.html(newContentWrapper.html());
            this.shimDynamicContent();

            if (this.inOverlay)
            {
                return;
            }

            var finalUrlInput = result.querySelector('input[type="hidden"][name="final_url"]')
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

        shimDynamicPageNav: function()
        {
            var pageNavWrapper = this.getPageNavWrapper();
            if (!pageNavWrapper)
            {
                return;
            }

            if (typeof XF.on === "function")
            {
                XF.on(pageNavWrapper.querySelectorAll('.pageNav a[href]'), 'click', this.ajaxLoadNewPage.bind(this))
            }
            else // XF 2.2
            {
                $(pageNavWrapper).find('.pageNav a[href]').on('click', XF.proxy(this, 'ajaxLoadNewPage'));
            }

            XF.activate(pageNavWrapper);
        },

        shimDynamicContent: function()
        {
            var contentWrapper = this.getContentWrapper();
            if (!contentWrapper)
            {
                return;
            }

            XF.activate(contentWrapper);
        },

        /**
         *
         * @param {HTMLElement} e
         *
         * @returns {number|number|number}
         */
        getPageFromAhref: function (e)
        {
            var url = e.getAttribute('href');
            if (!url) {
                return 1;
            }

            var currentUrl = new Url(url);
            if ('page' in currentUrl.query)
            {
                return parseInt(currentUrl.query['page']) || 1;
            }

            return 1;
        },

        /**
         * @param {Event} e
         */
        ajaxLoadNewPage: function(e)
        {
            e.preventDefault();
            var page = this.getPageFromAhref(e.target);
            if (page !== this.lastPageSelected)
            {
                this.lastPageSelected = page;
                this.resetPage = false;
                try
                {
                    XF.ajax('GET', e.target.getAttribute('href'), {}, XF.proxy(this, '_paginationAjaxResponse'));
                }
                finally
                {
                    this.resetPage = true;
                }
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

            var thisTarget = this.target ? this.target : this.$target.get(0),
                pageNavWrapper = thisTarget.querySelector(this.options.pageNavWrapper)
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

        /**
         * @returns {null|number}
         */
        getCurrentPage: function ()
        {
            if (!this.options.pageNavWrapper)
            {
                return null;
            }

            var pageNavWrapper = this.getPageNavWrapper(false);
            if (!pageNavWrapper)
            {
                return null;
            }

            var lastPageSelected = parseInt(this.lastPageSelected) || null;
            if (lastPageSelected)
            {
                return lastPageSelected;
            }

            return null;
        }
    });

    // ################################## --- ###########################################

    XF.Element.register('sv-ajax-pagination', 'SV.AjaxPagination');
}
(window.jQuery, window, document);