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
            var thisTarget = this.target || this.$target.get(0);
            this.inOverlay = XF.findRelativeIf('< .overlay-container', thisTarget) !== null;
            logNotFound = typeof logNotFound === 'undefined' ? true : logNotFound;
            if (!this.options.contentWrapper)
            {
                if (logNotFound)
                {
                    console.error('No content wrapper query expression defined');
                }

                return null;
            }

            var finalUrlInput = thisTarget.querySelector('input[type="hidden"][name="final_url"]')
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

            var existingPage = null,
                pageNavWrapper = this.getPageNavWrapper();
            if (pageNavWrapper)
            {
                var currentPageLink = pageNavWrapper.querySelector('.pageNav-page--current > a')
                if (currentPageLink)
                {
                    existingPage = this.getPageFromAhref(currentPageLink);
                }
            }
            this.lastPageSelected = (typeof existingPage === 'number') ? existingPage : 1;

            this.shimDynamicPageNav();

            this.perPageDropdown = thisTarget.querySelector(this.options.perPageDropdown)
            if (this.perPageDropdown !== null)
            {
                if (typeof this.perPageDropdown.on !== "undefined") // XF 2.2 only
                {
                    this.perPageDropdown.on('change', this.perPageChange.bind(this));
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

            XF.ajax('GET', currentUrl.toString(), {}, this._paginationAjaxResponse.bind(this));
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
                oldPageNavWrapper.empty();
                return;
            }

            if (newContentWrapper === null)
            {
                oldContentWrapper.empty();
                return;
            }

            oldPageNavWrapper.innerHTML = newPageNavWrapper.innerHTML;
            this.shimDynamicPageNav();

            oldContentWrapper.innerHTML = newContentWrapper.innerHTML;
            this.shimDynamicContent();

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

        shimDynamicPageNav: function()
        {
            var pageNavWrapper = this.getPageNavWrapper();
            if (!pageNavWrapper)
            {
                return;
            }

            if (typeof XF.on === "function")
            {
                for (const pageNavLink of pageNavWrapper.querySelectorAll('.pageNav a[href]'))
                {
                    XF.on(pageNavLink, 'click', this.ajaxLoadNewPage.bind(this))
                }
            }
            else // XF 2.2
            {
                $(pageNavWrapper).find('.pageNav a[href]').on('click', this.ajaxLoadNewPage.bind(this));
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
                    XF.ajax('GET', e.target.getAttribute('href'), {}, this._paginationAjaxResponse.bind(this));
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

            var thisTarget = this.target || this.$target.get(0),
                pageNavWrapper = thisTarget.querySelector(this.options.pageNavWrapper)
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
         * @returns {null|{length}|*|jQuery|HTMLElement}
         */
        getContentWrapper: function(logNotFound)
        {
            logNotFound = typeof logNotFound === 'undefined' ? true : logNotFound;
            var thisTarget = this.target || this.$target.get(0),
                contentWrapper = thisTarget.querySelector(this.options.contentWrapper)
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