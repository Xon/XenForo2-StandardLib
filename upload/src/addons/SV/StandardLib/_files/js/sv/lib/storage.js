var SV = window.SV || {};

(function ($, window, document, _undefined)
{
    "use strict";

    SV.ToggleStorage = XF.extend(XF.ToggleStorage, {
        options: {
            storageType: 'local',
            storageContainer: 'toggle',
            storageKey: null,

            target: null,
            container: null,
            hide: null,
            activeClass: 'is-active',
            activateParent: null,

            storageExpires: 31536000, // 1 year instead of the default 4 hours
            defaultValue:1,
        },
        __backup: {
            'init': 'svInit',
            'updateStorage': 'svExtraStorage'
        },

        init: function() {
            this.svInit();
            var options = this.options;
            options.defaultValue = !!options.defaultValue;

            // noinspection EqualityComparisonWithCoercionJS
            var activeClass = this.$target.hasClass(options.activeClass);
            if (activeClass === options.defaultValue) {
                this.storage.remove(
                    options.storageContainer,
                    options.storageKey
                );
            }
        },

        updateStorage: function()
        {
            var options = this.options;
            var activeClass = this.$target.hasClass(options.activeClass);
            if (activeClass === options.defaultValue) {
                this.storage.remove(
                    options.storageContainer,
                    options.storageKey
                );
            } else {
                this.storage.set(
                    options.storageContainer,
                    options.storageKey,
                    activeClass,
                    options.storageExpires,
                );
            }
        }
    });

    XF.Click.register('toggle-storage-ex', 'SV.ToggleStorage');
}) (jQuery, window, document);