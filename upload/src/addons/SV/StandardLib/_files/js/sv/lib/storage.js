// noinspection ES6ConvertVarToLetConst

var SV = window.SV || {};

;((window, document) =>
{
    "use strict";

    /**
     * @return {HTMLElement}
     */
    function getTarget(handler) {
        // noinspection JSUnresolvedReference
        return handler.target || handler.$target.get(0);
    }

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
            var thisTarget = getTarget(this),
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
            var thisTarget = getTarget(this),
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

    XF.Element.register('toggle-storage-ex', 'SV.ToggleStorage');
})(window, document)