var SV = window.SV || {};
SV.$ = SV.$ || window.jQuery || null;

!function(window, document)
{
    "use strict";
    var $ = SV.$;

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
            this.inOverlay = thisTarget.closest('.overlay-container') !== null;
            if (!this.options.contentWrapper)
            {
                console.error('No content wrapper query expression defined');

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
                pageNavWrappers = this.getPageNavWrappers();
            if (pageNavWrappers !== null)
            {
                var pageNavWrapper = pageNavWrappers[0];
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
                if (typeof XF.on !== "function") // XF 2.2
                {
                    $(this.perPageDropdown).on('change', this.perPageChange.bind(this));
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
            var oldPageNavWrappers = this.getPageNavWrappers();
            if (oldPageNavWrappers === null)
            {
                return;
            }

            var oldContentWrapper = this.getContentWrapper();
            if (oldContentWrapper === null)
            {
                return;
            }

            result.html.content = '<div>' + result.html.content.trim() + '</div>';
            XF.setupHtmlInsert(result.html, (html) => {
                if (typeof XF.createElementFromString === "undefined") { // XF 2.2
                    html = html.get(0);
                }

                var newPageNavWrapper = html.querySelector(this.options.pageNavWrapper),
                    newContentWrapper = html.querySelector(this.options.contentWrapper);
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
                this.shimDynamicPageNav();

                oldContentWrapper.innerHTML = newContentWrapper.innerHTML;
                this.shimDynamicContent();

                if (this.inOverlay)
                {
                    return;
                }

                var finalUrlInput = html.querySelector('input[type="hidden"][name="final_url"]')
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
            });
        },

        shimDynamicPageNav: function()
        {
            var pageNavWrappers = this.getPageNavWrappers();
            if (pageNavWrappers === null)
            {
                return;
            }

            if (typeof XF.on === "function") // XF 2.2
            {
                for (const pageNavWrapper of pageNavWrappers) {
                    for (const pageNavLink of pageNavWrapper.querySelectorAll('.pageNav a[href]')) {
                        XF.on(pageNavLink, 'click', this.ajaxLoadNewPage.bind(this))
                    }
                    XF.activate(pageNavWrapper);
                }
            }
            else // XF 2.2
            {
                var $wrappers = $(pageNavWrappers);
                $wrappers.find('.pageNav a[href]').on('click', this.ajaxLoadNewPage.bind(this));
                XF.activate($wrappers);
            }
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
         * @returns {null|HTMLElement[]}
         */
        getPageNavWrappers: function(logNotFound)
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
                pageNavWrappers = thisTarget.querySelectorAll(this.options.pageNavWrapper);
            if (pageNavWrappers === null || pageNavWrappers.length === 0)
            {
                if (logNotFound)
                {
                    console.error('No old pagination wrapper available');
                }

                return null;
            }

            return pageNavWrappers;
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

            var pageNavWrappers = this.getPageNavWrappers(false);
            if (pageNavWrappers === null)
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

    XF.Element.register('sv-ajax-pagination', 'SV.AjaxPagination');
}
(window, document);