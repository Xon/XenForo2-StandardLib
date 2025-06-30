var SV = window.SV || {};
SV.StandardLib = SV.StandardLib || {};
SV.$ = SV.$ || window.jQuery || null;
SV.extendObject = SV.extendObject || XF.extendObject || jQuery.extend;

;((window, document) =>
{
    "use strict";
    var $ = SV.$, xf22 = typeof XF.on !== 'function';

    SV.StandardLib.DynamicFilter = XF.extend(XF.Filter, {
        __backup: {
            'init': 'svLib__init',
            '_filterAjax': 'svLib__filterAjax',
            'filter': 'svLib__filter',
            'update': 'svLib__update',
            '_filterAjaxResponse': 'svLib__filterAjaxResponse',
            '_appendRows': 'svLib__appendRows'
        },
        options: SV.extendObject({}, XF.Filter.prototype.options, {
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
        }),

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
                pageNavWrappers = this.getPageNavWrappers();
            if (pageNavWrappers.length !== 0)
            {
                var pageNavWrapper = pageNavWrappers[0];
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
                if (xf22) // XF 2.2
                {
                    $(this.svPerPageDropdown).on('change', this.svPerPageChange.bind(this));
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
            const data = {
                page: this.getCurrentPage() || 1,
            };

            const currentUrl = new Url(this.options.ajax);
            currentUrl.query['_xfFilter[text]'] = text;
            currentUrl.query['_xfFilter[prefix]'] = prefix ? 1 : 0;

            this.xhrFilter = {
                text,
                prefix,
            }

            if (this.svPerPageDropdown)
            {
                currentUrl.query['per_page'] = this.svPerPageDropdown.value;
            }
            const finalUrl = currentUrl.toString();

            XF.ajax('GET', finalUrl, data, this._filterAjaxResponse.bind(this));
        },

        _appendRows: function(rows)
        {
            if (xf22) {
                this.svLib__appendRows(rows);
                return;
            }
            // XF2.3.0 bug workaround
            // https://xenforo.com/community/threads/uncaught-in-promise-typeerror-lastrow-is-undefined.222125/
            const existingRows = this.search.querySelectorAll(this.options.searchRow)
            const lastRow = existingRows[existingRows.length - 1]
            let lastRowContainer = null
            const searchRowGroup = this.options.searchRowGroup

            if (lastRow) {
                if (searchRowGroup) {
                    lastRowContainer = lastRow.closest(searchRowGroup)
                }

                if (!lastRowContainer && lastRow.matches('tr')) {
                    lastRowContainer = lastRow.closest('tbody')
                }

                if (!lastRowContainer) {
                    lastRowContainer = lastRow
                }
            }

            if (lastRowContainer)
            {
                Array.from(rows)
                    .reverse()
                    .forEach((row) =>
                    {
                        lastRowContainer.insertAdjacentElement('afterend', row)
                    })
            }
            else
            {
                const search = this.search;
                rows.forEach(function (row) {
                    search.append(row);
                });
            }
        },

        _filterAjaxResponse: function(result)
        {
            var filter = this.xhrFilter,
                existingRows = this._getSearchRows();
            if (xf22) {
                existingRows = existingRows.toArray();
            }
            if (!filter || !filter.text)
            {
                existingRows.forEach((el) => el.remove());
                existingRows = [];
            }

            result.html.content = '<div>' + result.html.content.trim() + '</div>'
            XF.setupHtmlInsert(result.html, (html) => {
                if (xf22) {
                    this.xhr = null;
                    html = html.get(0);
                } else {
                    this.abortController = null;
                }
                this._clearAjaxRows();

                var rows = html.querySelectorAll(this.options.searchRow);
                existingRows.forEach((el) => el.classList.add('is-hidden'));

                this._applyRowGroupLimit();
                this._toggleNoResults(rows.length === 0);

                if (rows.length)
                {
                    if (xf22) {
                        const $rows = $(rows);

                        this._appendRows($rows);
                        XF.activate($rows);
                        this.$ajaxRows = $rows;
                        this._applyFilter($rows, filter.text, filter.prefix);
                    } else {
                        this._appendRows(rows);
                        XF.activateAll(rows);
                        this.ajaxRows = rows;
                        this._applyFilter(rows, filter.text, filter.prefix)
                    }
                }
                else
                {
                    XF.layoutChange();
                }

                this.xhrFilter = null;

                var oldPageNavWrappers = this.getPageNavWrappers();
                var newPageNavWrapper = html.querySelector(this.options.pageNavWrapper);
                if (newPageNavWrapper === null)
                {
                    oldPageNavWrappers.forEach(function (oldPageNavWrapper) {
                        oldPageNavWrapper.innerHTML = '';
                    });
                    return;
                }

                oldPageNavWrappers.forEach(function (oldPageNavWrapper) {
                    oldPageNavWrapper.innerHTML = newPageNavWrapper.innerHTML;
                });
                this.shimDynamicPageNav();

                if (this.inOverlay)
                {
                    return;
                }

                var finalUrlInput = html.querySelector('input[type="hidden"][name="final_url"]');
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
            });
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
            var pageNavWrappers = this.getPageNavWrappers();
            if (pageNavWrappers.length === 0)
            {
                return;
            }

            if (xf22) {
                var $wrappers = $(pageNavWrappers);
                $wrappers.find('.pageNav a[href]').on('click', this.ajaxLoadNewPage.bind(this));
                XF.activate($wrappers);
            } else {
                for (const pageNavWrapper of pageNavWrappers) {
                    for (const pageNavLink of pageNavWrapper.querySelectorAll('.pageNav a[href]')) {
                        XF.on(pageNavLink, 'click', this.ajaxLoadNewPage.bind(this))
                    }
                    XF.activate(pageNavWrapper);
                }
            }
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
         * @returns {HTMLElement[]}
         */
        getPageNavWrappers: function()
        {
            var thisTarget = this.target || this.$target.get(0);

            return this.options.pageNavWrapper ? thisTarget.querySelectorAll(this.options.pageNavWrapper) :  [];
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

            var pageNavWrappers = this.getPageNavWrappers();
            if (pageNavWrappers.length === 0)
            {
                return 1;
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