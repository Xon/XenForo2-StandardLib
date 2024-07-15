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
            var self = this;
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
                callbackOnInit: function () {
                    return self.choicesInitCallback.call(self, this);
                },
                callbackOnCreateTemplates: function (template) {
                    return self.choicesCreateTemplatesCallback.call(self, this, template);
                }
            }, this.getItemChoices(), this.getChoicesPhrases(), this.getChoicesClassNames());
        },

        choicesInitCallback: function (choices)
        {
            //I exist because I must.
        },

        choicesCreateTemplatesCallback: function (choices, template)
        {
            let config = choices.config;

            return {
                item: ({ classNames }, data) =>
                {
                    let allowHTML = config.allowHTML,
                        item = classNames.item,
                        button = classNames.button,
                        highlightedState = classNames.highlightedState,
                        itemSelectable = classNames.itemSelectable,
                        placeholder = classNames.placeholder

                    let id = data.id,
                        value = data.value,
                        label = data.label,
                        customProperties = data.customProperties,
                        active = data.active,
                        disabled = data.disabled,
                        highlighted = data.highlighted,
                        isPlaceholder = data.placeholder

                    let labelClass = typeof data.customProperties.labelClass !== "undefined" ? data.customProperties.labelClass : null,
                        wrapSpan = null
                    if (labelClass !== null)
                    {
                        wrapSpan = document.createElement('span')
                        wrapSpan.className = "".concat(data.customProperties.labelClass, "")
                    }

                    let div = document.createElement('div')
                    div.className = item
                    if (wrapSpan)
                    {
                        if (allowHTML)
                        {
                            wrapSpan.innerHTML = label
                        }
                        else
                        {
                            wrapSpan.innerText = label
                        }
                        div.innerHTML = wrapSpan.outerHTML
                    }
                    else
                    {
                        if (allowHTML)
                        {
                            div.innerHTML = label
                        }
                        else
                        {
                            div.innerText = label
                        }
                    }

                    Object.assign(div.dataset, {
                        item: '',
                        id: id,
                        value: value,
                        customProperties: customProperties
                    });

                    if (active)
                    {
                        div.setAttribute('aria-selected', 'true');
                    }

                    if (disabled)
                    {
                        div.setAttribute('aria-disabled', 'true');
                    }

                    if (isPlaceholder)
                    {
                        div.classList.add(placeholder);
                    }
                    div.classList.add(highlighted ? highlightedState : itemSelectable);

                    if (config.removeItemButton)
                    {
                        if (disabled)
                        {
                            div.classList.remove(itemSelectable);
                        }
                        div.dataset.deletable = '';
                        let REMOVE_ITEM_ICON = typeof config.removeItemIconText === 'function' ? config.removeItemIconText(value) : config.removeItemIconText;
                        let REMOVE_ITEM_LABEL = typeof config.removeItemLabelText === 'function' ? config.removeItemLabelText(value) : config.removeItemLabelText;

                        let removeButton = document.createElement('button')
                        removeButton.type = 'button'
                        removeButton.className = button
                        if (allowHTML)
                        {
                            removeButton.innerHTML = REMOVE_ITEM_ICON
                        }
                        else
                        {
                            removeButton.innerText = REMOVE_ITEM_ICON
                        }

                        removeButton.setAttribute('aria-label', REMOVE_ITEM_LABEL);
                        removeButton.dataset.button = '';
                        div.appendChild(removeButton);
                    }

                    return div;
                },

                choice: ({ classNames }, data) =>
                {
                    let allowHTML = config.allowHTML,
                        item = classNames.item,
                        itemChoice = classNames.itemChoice,
                        itemSelectable = classNames.itemSelectable,
                        selectedState = classNames.selectedState,
                        itemDisabled = classNames.itemDisabled,
                        placeholder = classNames.placeholder

                    let id = data.id,
                        value = data.value,
                        label = data.label,
                        groupId = data.groupId,
                        elementId = data.elementId,
                        isDisabled = data.disabled,
                        isSelected = data.selected,
                        isPlaceholder = data.placeholder

                    let labelClass = typeof data.customProperties.labelClass !== "undefined" ? data.customProperties.labelClass : null,
                        wrapSpan = null
                    if (labelClass !== null)
                    {
                        wrapSpan = document.createElement('span')
                        wrapSpan.className = "".concat(data.customProperties.labelClass, "")
                    }

                    let div = document.createElement('div')
                    div.id = elementId
                    div.className = "".concat(item, " ").concat(itemChoice)
                    if (wrapSpan)
                    {
                        if (allowHTML)
                        {
                            wrapSpan.innerHTML = label
                        }
                        else
                        {
                            wrapSpan.innerText = label
                        }
                        div.innerHTML = wrapSpan.outerHTML
                    }
                    else
                    {
                        if (allowHTML)
                        {
                            div.innerHTML = label
                        }
                        else
                        {
                            div.innerText = label
                        }
                    }

                    if (isSelected)
                    {
                        div.classList.add(selectedState)
                    }
                    if (isPlaceholder) {
                        div.classList.add(placeholder)
                    }
                    div.setAttribute('role', groupId && groupId > 0 ? 'treeitem' : 'option')
                    Object.assign(div.dataset, {
                        choice: '',
                        id: id,
                        value: value,
                        selectText: config.itemSelectText
                    })

                    if (isDisabled)
                    {
                        div.classList.add(itemDisabled)
                        div.dataset.choiceDisabled = ''
                        div.setAttribute('aria-disabled', 'true')
                    }
                    else
                    {
                        div.classList.add(itemSelectable)
                        div.dataset.choiceSelectable = ''
                    }
                    return div
                }
            };
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

        getChoicesClassNames: function (choices)
        {
            return {
                classNames: {
                    containerOuter: 'inputGroup svChoices--inputGroup',
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