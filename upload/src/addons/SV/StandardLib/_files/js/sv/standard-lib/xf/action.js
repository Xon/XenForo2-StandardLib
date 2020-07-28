var SV = window.SV || {};
SV.StandardLib = SV.StandardLib || {};

(function ($, window, document, _undefined)
{
    'use strict';

    SV.StandardLib.PreviewClick = XF.Event.newHandler({
        eventType: 'click',
        eventNameSpace: 'SVStandardLibPreviewClick',

        options: {
            previewUrl: null,
            formTarget: 'form',
            previewContainerTarget: '.js-previewContainer'
        },

        $form: null,

        initFailed: true,
        isLoadingFromRequest: null,

        init: function ()
        {
            if (!this.options.previewUrl)
            {
                console.error('No preview url provided.');
                return;
            }

            if (!this.options.formTarget)
            {
                console.error('No form target provided.');
                return;
            }

            this.$form = this.$target.closest(this.options.formTarget);
            if (!this.$form.length)
            {
                console.error('No form available.')
                return;
            }

            this.initFailed = false;
            this.isLoadingFromRequest = false;
        },

        /**
         * @param {Event} e
         */
        click: function (e)
        {
            if (this.initFailed)
            {
                return;
            }

            if (this.isLoadingFromRequest)
            {
                return;
            }

            this.isLoadingFromRequest = true;

            var self = this,
                formData = XF.getDefaultFormData(this.$form);

            XF.ajax('POST', this.options.previewUrl, formData, XF.proxy(this, '_handleResponseFromRequest')).always(function ()
            {
                self.isLoadingFromRequest = false;
            })
        },

        /**
         * @returns {null|{length}|*}
         */
        getPreviewContainer: function ()
        {
            if (!this.options.previewContainerTarget)
            {
                console.error('No preview container target provided.');
                return null;
            }

            var $previewContainer = XF.findRelativeIf(this.options.previewContainerTarget, this.$target);
            if (!$previewContainer.length)
            {
                console.error('No preview container available.');
                return;
            }

            return $previewContainer;
        },

        /**
         * @param {Object} data
         *
         * @private
         */
        _handleResponseFromRequest: function (data)
        {
            if (!('html' in data))
            {
                return;
            }

            if (data.html.content)
            {
                this._insertPreview(data.html);
            }
            else
            {
                this._resetPreview();
            }
        },

        /**
         * @param {Object} dataHtml
         *
         * @private
         */
        _insertPreview: function (dataHtml)
        {
            var self = this;

            XF.setupHtmlInsert(dataHtml, function ($html, container, onComplete)
            {
                var $previewContainer = self.getPreviewContainer();

                $previewContainer.replaceWith($html);
                onComplete();

                return false;
            });
        },

        /**
         * @private
         */
        _resetPreview: function ()
        {
            var self = this;

            this.$previewContainer.xfFadeUp(XF.config.speed.fast, function ()
            {
                var $previewContainer = self.getPreviewContainer();
                if ($previewContainer)
                {
                    $previewContainer.empty();
                }

                XF.layoutChange();
            });
        }
    });

    XF.Event.register('click', 'sv-standard-lib--preview-click', 'SV.StandardLib.PreviewClick');
})(jQuery, window, document);