var SV = window.SV || {};

!function($, window, document)
{
    // ################################## --- ###########################################
    SV.AjaxPagination = XF.Element.newHandler({
        options: {
            pageNavWrapper: '.block-outer--page-nav-wrapper',
            contentWrapper: null
        },

        inOverlay: false,
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

            var existingPage = null,
                $pageNavWrapper = this.getPageNavWrapper();
            if ($pageNavWrapper)
            {
                existingPage = this.getPageFromAhref($pageNavWrapper.find('.pageNav-page--current > a').first());
            }
            this.lastPageSelected = (typeof existingPage === 'number') ? existingPage : 1;

            this.shimDynamicPageNav();
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

        shimDynamicContent: function()
        {
            var $contentWrapper = this.getContentWrapper();
            if (!$contentWrapper)
            {
                return;
            }

            XF.activate($contentWrapper);
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
            var $e = $(e.target);
            var page = this.getPageFromAhref($e);
            if (page != this.lastPageSelected)
            {
                this.lastPageSelected = page;
                this.resetPage = false;
                try
                {
                    var url = $e.attr('href');

                    XF.ajax('GET', url, {}, XF.proxy(this, '_paginationAjaxResponse'));
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