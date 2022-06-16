var SV = window.SV || {};
SV.UserMentionImprovements = SV.UserMentionImprovements || {};

(function($, window, document, _undefined)
{
    "use strict";

    XF.Filter = XF.extend(XF.Filter, {
        __backup: {
            'init': 'svLib__init',
            '_filterAjax': 'svLib__filterAjax',
            'filter': 'svLib__filter',
            '_filterAjaxResponse': 'svLib__filterAjaxResponse',
            '_getStoredValue': 'svLib__getStoredValue',
            '_updateStoredValue': 'svLib__updateStoredValue'
        },

        options: $.extend({}, XF.Filter.prototype.options, {
            svLoadInOverlay: true,
            svExistingFilterText: '',
            svExistingFilterPrefix: false,
            svPageNavWrapper: '.block-outer--page-nav-wrapper',
        }),

        inOverlay: false,
        xhrFilterOriginal: null,
        svPageChanged: false,
        svLastPageSelected: null,

        init: function ()
        {
            this.inOverlay = this.$target.parents('.overlay-container').length  !== 0;

            this.svLib__init();

            this.svOverlayShim();

            var storedValue = this._getStoredValue();
            if (!storedValue)
            {
                return;
            }

            if (!this.inOverlay && storedValue.filter === '' && storedValue.page === 1)
            {
                var currentUrl = new Url(window.location.href);
                if ("_xfFilter[text]" in currentUrl.query)
                {
                    var text = currentUrl.query["_xfFilter[text]"],
                        prefix = false;
                    if ("_xfFilter[prefix]" in currentUrl.query)
                    {
                        prefix = currentUrl.query["_xfFilter[prefix]"];
                    }

                    var data = this._readFromStorage(),
                        storageKey = this.storageKey;
                    if (!storedValue)
                    {
                        storedValue = {
                            filter: '',
                            prefix: false,
                            page: 1
                        };
                    }

                    if (data[storageKey])
                    {
                        var record = data[storageKey];
                        if ('page' in record)
                        {
                            storedValue.page = parseInt(record.page) || 1;
                        }
                    }

                    if (this.svLastPageSelected !== null)
                    {
                        storedValue.page = parseInt(this.svLastPageSelected) || 1;
                    }

                    data[storageKey] = storedValue;
                    this._writeToStorage(data);

                    if (text.length)
                    {
                        var $rows = this.$search
                            .find(this.options.searchRow)
                            .filter(':not(.is-hidden)');
                        if (!$rows.length)
                        {
                            return;
                        }

                        this._applyFilter($rows, text, prefix);
                    }
                }
            }
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
            // this will be null if not used with who replied
            var currentPage = this.svGetCurrentPage();
            if (!currentPage)
            {
                this.svLib__filterAjax(text, prefix);
                return;
            }

            var data = {
                _xfFilter: {
                    text: text,
                    prefix: prefix ? 1 : 0,
                    page: currentPage
                }
            };

            this.xhrFilter = { text: text, prefix: prefix, page: currentPage };
            XF.ajax('GET', this.options.ajax, data, XF.proxy(this, '_filterAjaxResponse'));
        },

        _filterAjaxResponse: function(result)
        {
            this.svLib__filterAjaxResponse(result);

            var oldPageNavWrapper = this.svGetPageNavWrapper();
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
            this.svOverlayShim();

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
         * @returns {Object}
         *
         * @private
         */
        _getStoredValue: function()
        {
            var storedValue = this.svLib__getStoredValue(),
                storageKey = this.storageKey;

            if (!storageKey)
            {
                return storedValue;
            }

            var data = this._readFromStorage();
            if (!storedValue)
            {
                storedValue = {
                    filter: '',
                    prefix: false,
                    page: 1
                };
            }

            if (data[storageKey])
            {
                var record = data[storageKey];
                if ('page' in record)
                {
                    storedValue.page = parseInt(record.page) || 1;
                }
            }

            if (this.svLastPageSelected !== null)
            {
                storedValue.page = parseInt(this.svLastPageSelected) || 1;
            }

            if (storedValue.filter === '')
            {
                var existingFilterText = this.options.svExistingFilterText;
                if (typeof existingFilterText === 'string' && existingFilterText !== '')
                {
                    storedValue.filter = existingFilterText;

                    var existingFilterPrefix = this.options.svExistingFilterPrefix;
                    if (typeof existingFilterPrefix === 'boolean')
                    {
                        storedValue.prefix = existingFilterPrefix;
                    }
                }
            }

            data[storageKey] = storedValue;
            this._writeToStorage(data);

            return storedValue;
        },

        _updateStoredValue: function(text, prefix)
        {
            var storedValue = this._getStoredValue();
            if (storedValue && typeof storedValue === 'object')
            {
                var storageKey = this.storageKey;
                if (storageKey)
                {
                    var data = this._readFromStorage();
                    if (data[storageKey])
                    {
                        var record = data[storageKey];
                        record.page = 1;
                        data[storageKey] = record;
                        this._writeToStorage(data);
                    }
                }
            }

            this.svLib__updateStoredValue(text, prefix);
        },

        filter: function(text, prefix)
        {
            var page = this.svGetCurrentPage();
            if (page === null)
            {
                this.svLib__filter(text, prefix);

                return;
            }

            this._updateStoredValue(text, prefix);
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

        svOverlayShim: function()
        {
            var $pageNavWrapper = this.svGetPageNavWrapper();
            if (!$pageNavWrapper)
            {
                return;
            }

            $pageNavWrapper.find('.pageNav a[href]').on('click', XF.proxy(this, 'svUpdateCurrentPage'));
            XF.activate($pageNavWrapper);
        },

        /**
         * @param {Event} e
         */
        svUpdateCurrentPage: function(e)
        {
            var $target = $(e.target);
            if ($target.hasClass('pageNav-jump--prev'))
            {
                $target = $target.parent()
                    .find('ul.pageNav-main > .pageNav-page--current')
                    .prev();
            }
            else if ($target.hasClass('pageNav-jump--next'))
            {
                $target = $target.parent()
                    .find('ul.pageNav-main > .pageNav-page--current')
                    .next()
                    .find('a');
            }

            if ($target.length)
            {
                var storedValue = this._getStoredValue();
                if (!storedValue || !(typeof storedValue === 'object'))
                {
                    storedValue = {
                        filter: '',
                        prefix: false,
                        page: 1
                    };
                }

                e.preventDefault();

                storedValue.page = parseInt($target.text()) || 1;
                storedValue.saved = Math.floor(new Date().getTime() / 1000);

                this.svPageChanged = storedValue.page !== this.svGetCurrentPage();
                this.svLastPageSelected = storedValue.page;

                var data = this._readFromStorage();
                data[this.storageKey] = storedValue;
                this._writeToStorage(data);

                this.update();

                this.svPageChanged = false;
                this.svLastPageSelected = null;
            }
            else
            {
                console.error('No valid page link found.'); // for debugging purposes when preserve log is checked for edge/chrome settings
            }
        },

        /**
         *
         * @param {Boolean} logNotFound
         *
         * @returns {null|{length}|*|jQuery|HTMLElement}
         */
        svGetPageNavWrapper: function(logNotFound)
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
        svGetCurrentPage: function ()
        {
            if (!this.options.svPageNavWrapper)
            {
                return null;
            }

            var pageNavWrapper = this.svGetPageNavWrapper(false);
            if (!pageNavWrapper)
            {
                return null;
            }

            var storedValue = this._getStoredValue();
            if (!storedValue)
            {
                var lastPageSelected = parseInt(this.svLastPageSelected) || null;
                if (lastPageSelected)
                {
                    return lastPageSelected;
                }

                return null;
            }

            return parseInt(storedValue.page) || 1;
        }
    });
}
(jQuery, window, document));