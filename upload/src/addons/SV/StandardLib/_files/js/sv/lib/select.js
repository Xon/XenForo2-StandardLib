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
            this.choices = new Choices(this.target || this.$target.get(0), this.getConfig());
            this.initEvents();
        },

        getConfig: function ()
        {
            var field = this.target || this.$target.get(0),
                placeholderValue = this.options.placeholder || field.getAttribute('placeholder'),
                placeholder = !!placeholder;
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
                placeholder: placeholder,
                placeholderValue: placeholder ? placeholderValue : null,
            }, this.getPhrases(), this.getClassNames());
        },

        getPhrases: function()
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

        getClassNames: function ()
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

        initEvents: function ()
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
                $target.on('addItem', this.onAddItem.bind(this));
                $target.on('removeItem', this.onRemoveItem.bind(this));
                $target.on('choice', this.onChoice.bind(this));
                $target.on('showDropdown', this.onShowDropdown.bind(this));
                $target.on('hideDropdown', this.onHideDropdown.bind(this));
            }
            else
            {
                XF.on(passedElement, 'addItem', this.onAddItem.bind(this));
                XF.on(passedElement, 'removeItem', this.onRemoveItem.bind(this));
                XF.on(passedElement, 'choice', this.onChoice.bind(this));
                XF.on(passedElement, 'showDropdown', this.onShowDropdown.bind(this));
                XF.on(passedElement, 'hideDropdown', this.onHideDropdown.bind(this));
            }
        },

        onAddItem: function (event)
        {
            if (this.choices === null)
            {
                console.error('Choices not setup.')
                return;
            }

            let item = event.detail,
                customProperties = item.customProperties
            if (customProperties !== 'undefined')
            {
                if (customProperties.clears === true)
                {
                    this.choices.removeActiveItems(item.id)
                }
                else
                {
                    event.target.querySelectorAll('option').forEach((option) =>
                    {
                        if (typeof option.dataset.customProperties !== 'undefined')
                        {
                            let customProps;
                            try
                            {
                                customProps = SV.extendObject(JSON.parse(option.dataset.customProperties))
                            }
                            catch (e)
                            {
                                customProps = {}
                            }

                            if (customProps.clears)
                            {
                                this.choices.removeActiveItemsByValue(option.value)
                            }
                        }
                    })
                }
            }
        },

        onRemoveItem: function (event)
        {
        },

        onChoice: function (event)
        {
        },

        onShowDropdown: function (event)
        {
        },

        onHideDropdown: function (event)
        {
        },
    });

    XF.Element.register('sv-choices', 'SV.StandardLib.Choices');
}) ();