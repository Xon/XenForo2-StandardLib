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
        }),

        resetPage: true,
        skipUpdate: false,
        inOverlay: false,
        svLastPageSelected: null,

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
                existingPage = this.getPageFromAhref($pageNavWrapper.find('.pageNav-page--current').first());
            }
            this.svLastPageSelected = (typeof existingPage === 'number') ? existingPage : 1;

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

            this.xhrFilter = data['_xfFilter'];
            XF.ajax('GET', this.options.ajax, data, XF.proxy(this, '_filterAjaxResponse'));
        },

        _filterAjaxResponse: function(result)
        {
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

            var oldPageNavWrapper = $(this.options.svPageNavWrapper);
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