var SV = window.SV || {};

;((window, document) =>
{
    "use strict";

    const defineFunc = function () {
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
            var thisTarget = this.$target ? this.$target.get(0) : this.target,
                activeClass = thisTarget.classList.contains(options.activeClass);
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
            var thisTarget = this.$target ? this.$target.get(0) : this.target,
                activeClass = thisTarget.classList.contains(options.activeClass);
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
    };

    if (typeof XF.ToggleStorage === "undefined")
    {
        setTimeout(function () {
            const jsUrl = XF.config.url.js
            if (!jsUrl) {
                console.error('No JS URL so cannot lazy-load JS')
                return
            }

            XF.Loader.loadJs([
                jsUrl.replace('__SENTINEL__', 'xf/structure.js') + '_mt=' + XF.config.jsMt['xf/structure.js'] || ''
            ], function() {
                defineFunc();
                XF.Event.register('click', 'toggle-storage-ex', 'SV.ToggleStorage');
            });
        }, 0);
    }
    else
    {
        defineFunc();
        XF.Event.register('click', 'toggle-storage-ex', 'SV.ToggleStorage');
    }

})(window, document)