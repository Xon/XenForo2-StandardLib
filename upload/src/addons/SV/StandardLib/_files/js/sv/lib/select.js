var SV = window.SV || {};
SV.StandardLib = SV.StandardLib || {};
SV.extendObject = SV.extendObject || XF.extendObject || jQuery.extend;

(function()
{
    "use strict";

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
        },

        getItemChoices: function ()
        {
            let itemChoices = [],
                theTarget = this.target || this.$target.get(0)

            theTarget.querySelectorAll(':scope > *').forEach((optionOrOptionGroup) =>
            {
                if (optionOrOptionGroup.tagName.toLowerCase().trim() === 'optgroup')
                {
                    let groupedChoices = {
                        label: optionOrOptionGroup.label,
                        disabled: optionOrOptionGroup.disabled,
                        choices: []
                    }

                    //@todo: does group has customProperties support?
                    let optGroupCustomProps = SV.extendObject(optionOrOptionGroup.dataset)
                    if (typeof optGroupCustomProps.id !== "undefined")
                    {
                        groupedChoices.id = optGroupCustomProps.id
                        delete optGroupCustomProps.id
                    }

                    optionOrOptionGroup.querySelectorAll('option').forEach((option) => {
                        groupedChoices.choices.push({
                            value: option.value,
                            label: option.text,
                            disabled: option.disabled,
                            selected: option.selected,
                            customProperties: SV.extendObject(option.dataset)
                        })
                    })

                    itemChoices.push(groupedChoices)
                }
                else
                {
                    itemChoices.push({
                        value: optionOrOptionGroup.value,
                        label: optionOrOptionGroup.text,
                        disabled: optionOrOptionGroup.disabled,
                        selected: optionOrOptionGroup.selected,
                        customProperties: SV.extendObject(optionOrOptionGroup.dataset)
                    })
                }

                optionOrOptionGroup.remove()
            })

            return {
                choices: itemChoices
            }
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
            }, this.getItemChoices(), this.getChoicesPhrases(), this.getChoicesClassNames());
        },

        getChoicesPhrases: function() {
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
                    containerOuter: 'inputGroup svChoices--inputGroup',
                    containerInner: 'input',
                }
            }
        }
    });

    XF.Element.register('sv-choices', 'SV.StandardLib.Choices');
}) ();