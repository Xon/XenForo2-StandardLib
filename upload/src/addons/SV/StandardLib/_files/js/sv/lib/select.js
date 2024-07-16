var SV = window.SV || {};
SV.StandardLib = SV.StandardLib || {};
SV.$ = SV.$ || window.jQuery || null;
SV.extendObject = SV.extendObject || XF.extendObject || jQuery.extend;

(function()
{
    "use strict";
    var $ = SV.$;

    SV.StandardLib.Choices = XF.Element.newHandler({
        options: {
            placeholder: null,
            choicesMaxItemCount: -1,
            choicesRemoveItemButton: true,
            choicesAllowHTML: true,
            choicesShouldSort: false,
            choicesShouldSortItems: false,
            choicesEditItems: false,
            choicesResetScrollPosition: false,
            choicesRenderSelectedChoices: 'always',//'auto'
            choicesRenderChoiceLimit: false,
            choicesCustomProperties: null
        },
        choices: null,

        init: function()
        {
            this.choices = new Choices(this.target || this.$target.get(0), this.getChoicesConfig());
            this.initChoicesEvents()
        },

        getChoicesConfig: function ()
        {
            var field = this.target || this.$target.get(0),
                placeholder = this.options.placeholder || field.getAttribute('placeholder');
            return SV.extendObject({}, {
                maxItemCount: this.options.choicesMaxItemCount,
                removeItemButton: this.options.choicesRemoveItemButton,
                allowHTML: this.options.choicesAllowHTML,
                shouldSort: this.options.choicesShouldSort,
                shouldSortItems: this.options.choicesShouldSortItems,
                editItems: this.options.choicesEditItems,
                resetScrollPosition: this.options.choicesResetScrollPosition,
                renderSelectedChoices: this.options.choicesRenderSelectedChoices,
                renderChoiceLimit: this.options.choicesRenderChoiceLimit,
                pseudoMultiSelectForSingle: this.options.choicesMaxItemCount === 1,
                placeholderValue: placeholder !== '' ? placeholder : null,
            }, this.getChoicesPhrases(), this.getChoicesClassNames());
        },

        getChoicesPhrases: function()
        {
            return {
                loadingText: XF.phrase('svChoices_loadingText'),
                noResultsText: XF.phrase('svChoices_noResultsText'),
                noChoicesText: XF.phrase('svChoices_noChoicesText'),
                itemSelectText: XF.phrase('svChoices_itemSelectText'),
                uniqueItemText: XF.phrase('svChoices_uniqueItemText'),
                customAddItemText: XF.phrase('svChoices_customAddItemText'),
                addItemText: function (value) {
                    return XF.phrase('svChoices_addItemText', {
                        '{value}': value
                    });
                },
                removeItemIconText: '',
                removeItemLabelText: function (value) {
                    return XF.phrase('svChoices_removeItemLabel', {
                        '{value}': value
                    });
                },
                maxItemText: function (maxItemCount) {
                    return XF.phrase('svChoices_maxItemText', {
                        '{maxItemCount}': maxItemCount
                    });
                }
            };
        },

        getChoicesClassNames: function ()
        {
            return {
                classNames: {
                    containerOuter: [
                        'inputGroup',
                        'svChoices--inputGroup',
                    ],
                    containerInner: [
                        'input',
                    ],
                }
            }
        },

        initChoicesEvents: function ()
        {
            if (!this.choices)
            {
                console.error('Choices not setup.')
                return;
            }

            let passedElement = this.choices.passedElement.element;

            if (typeof XF.on !== "function") // XF 2.2
            {
                var $target = $(passedElement);
                $target.on('addItem', this.choicesOnAddItem.bind(this));
                $target.on('removeItem', this.choicesOnRemoveItem.bind(this));
                $target.on('choice', this.choicesOnChoice.bind(this));
                $target.on('showDropdown', this.choicesOnShowDropdown.bind(this));
                $target.on('hideDropdown', this.choicesOnHideDropdown.bind(this));
            }
            else
            {
                XF.on(passedElement, 'addItem', this.choicesOnAddItem.bind(this));
                XF.on(passedElement, 'removeItem', this.choicesOnRemoveItem.bind(this));
                XF.on(passedElement, 'choice', this.choicesOnChoice.bind(this));
                XF.on(passedElement, 'showDropdown', this.choicesOnShowDropdown.bind(this));
                XF.on(passedElement, 'hideDropdown', this.choicesOnHideDropdown.bind(this));
            }
        },

        choicesOnAddItem: function (event)
        {
        },

        choicesOnRemoveItem: function (event)
        {
        },

        choicesOnChoice: function ()
        {
        },

        choicesOnShowDropdown: function ()
        {
        },

        choicesOnHideDropdown: function (event)
        {
        },
    });

    XF.Element.register('sv-choices', 'SV.StandardLib.Choices');
}) ();