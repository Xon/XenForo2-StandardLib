var SV = window.SV || {};
SV.StandardLib = SV.StandardLib || {};
SV.extendObject = SV.extendObject || XF.extendObject || jQuery.extend;

(function()
{
    "use strict";

    SV.StandardLib.Choices = XF.Element.newHandler({
        options: {
            choicesMaxItemCount: -1,
            choicesRemoveItemButton: true,
            choicesAllowHTML: true,
            choicesShouldSort: false,
            choicesShouldSortItems: false,
            choicesEditItems: false,
            choicesResetScrollPosition: false,
            choicesRenderSelectedChoices: 'always',//'auto'
            choicesRenderChoiceLimit: false
        },
        choices: null,

        init: function()
        {
            this.choices = new Choices(this.target || this.$target.get(0), this.getChoicesOptions());
        },

        getChoicesOptions: function ()
        {
            return SV.extendObject({}, {
                maxItemCount: this.options.choicesMaxItemCount,
                removeItemButton: this.options.choicesRemoveItemButton,
                allowHTML: this.options.choicesAllowHTML,
                shouldSort: this.options.choicesShouldSort,
                shouldSortItems: this.options.choicesShouldSortItems,
                editItems: this.options.choicesEditItems,
                resetScrollPosition: this.options.choicesResetScrollPosition,
                renderSelectedChoices: this.options.choicesRenderSelectedChoices,
                renderChoiceLimit: this.options.choicesRenderChoiceLimit
            }, this.getChoicesPhrases(), this.getChoicesClassNames())
        },

        getChoicesPhrases: function() {
            return {
                loadingText: XF.phrase('choices_loadingText'),
                noResultsText: XF.phrase('choices_noResultsText'),
                noChoicesText: XF.phrase('choices_noChoicesText'),
                itemSelectText: XF.phrase('choices_itemSelectText'),
                uniqueItemText: XF.phrase('choices_uniqueItemText'),
                customAddItemText: XF.phrase('choices_customAddItemText'),
                addItemText: function (value) {
                    return XF.phrase('choices_addItemText', {
                        '{value}': value
                    });
                },
                maxItemText: function (maxItemCount) {
                    return XF.phrase('choices_maxItemText', {
                        '{maxItemCount}': maxItemCount
                    });
                }
            };
        },

        getChoicesClassNames: function (choices)
        {
            return {
                classNames: {
                    containerOuter: 'inputGroup',
                    containerInner: 'input',
                    input: 'choices__input',
                    inputCloned: 'choices__input--cloned',
                    list: 'choices__list',
                    listItems: 'choices__list--multiple',
                    listSingle: 'choices__list--single',
                    listDropdown: 'choices__list--dropdown',
                    item: 'choices__item',
                    itemSelectable: 'choices__item--selectable',
                    itemDisabled: 'choices__item--disabled',
                    itemChoice: 'choices__item--choice',
                    placeholder: 'choices__placeholder',
                    group: 'choices__group',
                    groupHeading: 'choices__heading',
                    button: 'choices__button',
                    activeState: 'is-active',
                    focusState: 'is-focused',
                    openState: 'is-open',
                    disabledState: 'is-disabled',
                    highlightedState: 'is-highlighted',
                    selectedState: 'is-selected',
                    flippedState: 'is-flipped',
                    loadingState: 'is-loading',
                    noResults: 'has-no-results',
                    noChoices: 'has-no-choices'
                }
            }
        }
    });

    XF.Element.register('sv-choices', 'SV.StandardLib.Choices');
}) ();