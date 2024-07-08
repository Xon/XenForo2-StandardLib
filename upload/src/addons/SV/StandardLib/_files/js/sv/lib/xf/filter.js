var SV = window.SV || {};
SV.StandardLib = SV.StandardLib || {};
SV.StandardLib.XF = SV.StandardLib.XF || {};
SV.StandardLib.XF.Filter = SV.StandardLib.XF.Filter || {};
SV.extendObject = SV.extendObject || XF.extendObject || jQuery.extend;

;((window, document) =>
{
    "use strict";

    SV.StandardLib.XF.Filter.BaseOpts = {
        svLoadInOverlay: true,
        pageNavWrapper: '.block-outer--page-nav-wrapper',
        searchTarget: '.userList',
        searchRow: '.userList-row',
        searchRowGroup: null,
        searchLimit: '.username',
        noResultsFormat: '<div class="blockMessage js-filterNoResults">%s</div>',
        globalFind: true,
        perPageDropdown: 'select[name="per_page"]',
        perPageCookiePrefix: null
    }

    SV.StandardLib.DynamicFilter = XF.extend(XF.Filter, {
        __backup: {
            'init': 'svLib__init',
            '_filterAjax': 'svLib__filterAjax',
            'filter': 'svLib__filter',
            'update': 'svLib__update',
            '_filterAjaxResponse': 'svLib__filterAjaxResponse'
        },
        options: SV.extendObject({}, XF.Filter.prototype.options, SV.StandardLib.XF.Filter.BaseOpts),

        resetPage: true,
        skipUpdate: false,
        svPerPageDropdown: null,
        finalUrl: null,
        inOverlay: false,
        svChangeTimer: null,
        svLastPageSelected: null,

        _getStoredValue: function() {
            return null;
        },
        _updateStoredValue: function(val, prefix) {
            return;
        },

        init: function ()
        {
            var thisTarget = this.target || this.$target.get(0);
            this.inOverlay = thisTarget.closest('.overlay-container') !== null;

            if (!this.options.ajax) {
                console.error('No filter AJAX URL input was provided.');
                return;
            }

            var existingPage = null,
                pageNavWrapper = this.getPageNavWrapper();
            if (pageNavWrapper)
            {
                var currentPageLink = pageNavWrapper.querySelector('.pageNav-page--current > a');
                if (currentPageLink)
                {
                    existingPage = this.getPageFromAhref(currentPageLink);
                }
            }
            this.svLastPageSelected = (typeof existingPage === 'number') ? existingPage : 1;

            this.svPerPageDropdown = thisTarget.querySelector(this.options.perPageDropdown);
            if (this.svPerPageDropdown !== null)
            {
                if (typeof this.svPerPageDropdown.on !== "undefined") // XF 2.2 only
                {
                    this.svPerPageDropdown.on('change', this.svPerPageChange.bind(this));
                }
                else
                {
                    XF.on(this.svPerPageDropdown, 'change', this.svPerPageChange.bind(this));
                }
            }

            this.skipUpdate = true;
            try
            {
                this.svLib__init();
            }
            finally {
                this.skipUpdate = false;
            }

            this.shimDynamicPageNav();
        },

        svPerPageChange: function()
        {
            if (this.svChangeTimer)
            {
                clearTimeout(this.svChangeTimer);
            }

            this.svChangeTimer = setTimeout(this.svPerPageOnTimer.bind(this), 200);
        },

        svPerPageOnTimer: function()
        {
            var value = this.svPerPageDropdown.value;

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

            this.update();
        },

        /**
         *
         *
         * @param {String} text
         * @param {Boolean} prefix
         *
         * @private
         */
        _filterAjax: function(text, prefix)
        {
            var currentPage = this.getCurrentPage() || 1;
            var data = {
                _xfFilter: {
                    text: text,
                    prefix: prefix ? 1 : 0
                }
            };

            if (currentPage != 1)
            {
                data['page'] = currentPage;
            }

            var finalUrl = this.options.ajax;
            if (this.svPerPageDropdown)
            {
                var currentUrl = new Url(this.options.ajax);
                currentUrl.query['per_page'] = this.svPerPageDropdown.value;

                finalUrl = currentUrl.toString();
            }

            this.xhrFilter = data['_xfFilter'];
            XF.ajax('GET', finalUrl, data, this._filterAjaxResponse.bind(this));
        },

        _filterAjaxResponse: function(result)
        {
            var filter = this.xhrFilter,
                existingRows = this._getSearchRows();
            if (!filter.text)
            {
                if (existingRows)
                {
                    for (const existingRow_ of existingRows)
                    {
                        existingRow_.remove();
                    }

                    existingRows = null;
                }
            }

            this.svLib__filterAjaxResponse(result);

            var oldPageNavWrapper = this.getPageNavWrapper();
            if (!oldPageNavWrapper)
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

            var newPageNavWrapper = XF.findRelativeIf(this.options.contentWrapper, tmpResult);
            if (newPageNavWrapper === null)
            {
                oldPageNavWrapper.empty();
                return;
            }

            oldPageNavWrapper.innerHTML = newPageNavWrapper.innerHTML;
            this.shimDynamicPageNav();

            if (this.inOverlay)
            {
                return;
            }

            var finalUrlInput = tmpResult.querySelector('input[type="hidden"][name="final_url"]');
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

        update: function() {
            if (this.skipUpdate) {
                return;
            }

            this.svLib__update();
        },

        filter: function(text, prefix)
        {
            if (this.resetPage)
            {
                this.svLastPageSelected = 1;
            }
            this._toggleFilterHide(text.length > 0);

            if (this.options.ajax)
            {
                this._filterAjax(text, prefix);
            }
            else
            {
                var matched = this._applyFilter(this._getSearchRows(), text, prefix);
                this._toggleNoResults(matched === 0);
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

        /**
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
            if (page != this.svLastPageSelected)
            {
                this.svLastPageSelected = page;
                this.resetPage = false;
                try
                {
                    this.update();
                }
                finally
                {
                    this.resetPage = true;
                }
            }
        },

        /**
         *
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
                oldPageNavWrapper = this.options.globalFind
                    ? document.querySelector((this.options.pageNavWrapper))
                    : thisTarget.querySelector((this.options.pageNavWrapper));
            if (oldPageNavWrapper === null)
            {
                if (logNotFound)
                {
                    console.error('No old pagination wrapper available', oldPageNavWrapper);
                }

                return null;
            }

            return oldPageNavWrapper;
        },

        /**
         *
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

            var lastPageSelected = parseInt(this.svLastPageSelected) || null;
            if (lastPageSelected)
            {
                return lastPageSelected;
            }

            return null;
        }
    });

    XF.Element.register('sv-dynamic-filter', 'SV.StandardLib.DynamicFilter');
})(window, document)