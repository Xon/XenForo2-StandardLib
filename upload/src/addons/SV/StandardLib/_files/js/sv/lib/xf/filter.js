var SV = window.SV || {};
SV.StandardLib = SV.StandardLib || {};

(function($, window, document, _undefined)
{
    "use strict";

    SV.StandardLib.DynamicFilter = XF.extend(XF.Filter, {
        __backup: {
            'init': 'svLib__init',
            '_filterAjax': 'svLib__filterAjax',
            'filter': 'svLib__filter',
            'update': 'svLib__update',
            '_filterAjaxResponse': 'svLib__filterAjaxResponse'
        },

        options: $.extend({}, XF.Filter.prototype.options, {
            svLoadInOverlay: true,
            svPageNavWrapper: '.block-outer--page-nav-wrapper',
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
        inOverlay: false,
        svLastPageSelected: null,
        svPerPageDropdown: null,
        svChangeTimer: null,

        _getStoredValue: function() {
            return null;
        },
        _updateStoredValue: function(val, prefix) {
            return;
        },

        init: function ()
        {
            this.inOverlay = this.$target.parents('.overlay-container').length  !== 0;

            var existingPage = null,
                $pageNavWrapper = this.getPageNavWrapper();
            if ($pageNavWrapper)
            {
                existingPage = this.getPageFromAhref($pageNavWrapper.find('.pageNav-page--current > a').first());
            }
            this.svLastPageSelected = (typeof existingPage === 'number') ? existingPage : 1;

            this.svPerPageDropdown = this.$target.find(this.options.perPageDropdown);
            if (this.svPerPageDropdown.length)
            {
                this.svPerPageDropdown.on('change', XF.proxy(this, 'svPerPageChange'));
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

            this.svChangeTimer = setTimeout(XF.proxy(this, 'svPerPageOnTimer'), 200);
        },

        svPerPageOnTimer: function()
        {
            var value = this.svPerPageDropdown.val();

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
            if (currentPage != 1) {
                data['page'] = currentPage;
            }

            var finalUrl = this.options.ajax;
            if (this.svPerPageDropdown)
            {
                var currentUrl = new Url(this.options.ajax);
                currentUrl.query['per_page'] = this.svPerPageDropdown.val()

                finalUrl = currentUrl.toString();
            }

            this.xhrFilter = data['_xfFilter'];
            XF.ajax('GET', finalUrl, data, XF.proxy(this, '_filterAjaxResponse'));
        },

        _filterAjaxResponse: function(result)
        {
            var filter = this.xhrFilter,
                $existingRows = this._getSearchRows()
            ;
            if (!filter.text)
            {
                if ($existingRows)
                {
                    $existingRows.remove();
                    $existingRows = null;
                }
            }

            this.svLib__filterAjaxResponse(result);

            var oldPageNavWrapper = this.getPageNavWrapper();
            if (!oldPageNavWrapper)
            {
                return;
            }

            var $result = $($.parseHTML(result.html.content)),
                newPageNavWrapper = $result.find(this.options.svPageNavWrapper);
            if (!newPageNavWrapper.length)
            {
                oldPageNavWrapper.empty();
                return;
            }

            oldPageNavWrapper.html(newPageNavWrapper.html());
            this.shimDynamicPageNav();

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
            var $pageNavWrapper = this.getPageNavWrapper();
            if (!$pageNavWrapper)
            {
                return;
            }

            $pageNavWrapper.find('.pageNav a[href]').on('click', XF.proxy(this, 'ajaxLoadNewPage'));
            XF.activate($pageNavWrapper);
        },

        /**
         * @param {jQuery} e
         * @return number
         */
        getPageFromAhref: function ($e)
        {
            var url = $e.attr('href');
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
            var page = this.getPageFromAhref($(e.target));
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
            if (!this.options.svPageNavWrapper)
            {
                if (logNotFound)
                {
                    console.error('No pagination wrapper query expression defined');
                }

                return null;
            }

            var oldPageNavWrapper = this.options.globalFind
                ? $(this.options.svPageNavWrapper)
                : this.$target.find(this.options.svPageNavWrapper)
            ;
            if (!oldPageNavWrapper.length)
            {
                if (logNotFound)
                {
                    console.error('No old pagination wrapper available');
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
            if (!this.options.svPageNavWrapper)
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

            return parseInt(storedValue.page) || 1;
        }
    });

    XF.Element.register('sv-dynamic-filter', 'SV.StandardLib.DynamicFilter');
}
(jQuery, window, document));