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
            maxItemCount: -1,
            removeItemButton: true,
            allowHTML: true,
            shouldSort: false,
            shouldSortItems: false,
            editItems: false,
            resetScrollPosition: false,
            renderSelectedChoices: 'always',//'auto'
            renderChoiceLimit: false,
            appendGroupInSearch: false,
        },
        choices: null,

        init: function()
        {
            this.choices = new Choices(this.target || this.$target.get(0), this.getConfig());
            this.initEvents();
        },

        reset: function()
        {
            this.choices._onFormReset();
        },

        getConfig: function ()
        {
            var field = this.target || this.$target.get(0),
                placeholderValue = this.options.placeholder || field.getAttribute('placeholder'),
                placeholder = !!placeholder;
            return SV.extendObject({}, this.options, {
                pseudoMultiSelectForSingle: this.options.maxItemCount === 1,
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
                        'choices',
                        'inputGroup',
                        'svChoices--inputGroup',
                    ],
                    containerInner: [
                        'choices__inner',
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
                $target.on('control:enabled', this.onControlEnabled.bind(this));
                $target.on('control:disabled', this.onControlDisabled.bind(this));
            }
            else
            {
                XF.on(passedElement, 'addItem', this.onAddItem.bind(this));
                XF.on(passedElement, 'removeItem', this.onRemoveItem.bind(this));
                XF.on(passedElement, 'choice', this.onChoice.bind(this));
                XF.on(passedElement, 'showDropdown', this.onShowDropdown.bind(this));
                XF.on(passedElement, 'hideDropdown', this.onHideDropdown.bind(this));
                XF.on(passedElement, 'control:enabled', this.onControlEnabled.bind(this));
                XF.on(passedElement, 'control:disabled', this.onControlDisabled.bind(this));
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
            if (typeof customProperties !== 'undefined')
            {
                if (customProperties.clears)
                {
                    this.choices.removeActiveItems(item.id)
                }
                else
                {
                    this.choices._currentState.items.forEach((option) =>
                    {
                        var customProperties = option.customProperties;
                        if (typeof customProperties === 'object' && customProperties.clears)
                        {
                            this.choices.removeActiveItemsByValue(option.value);
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

        onControlEnabled: function (event)
        {
            if (!this.choices)
            {
                console.error('Choices not setup.')
                return
            }

            this.choices.enable()
        },

        onControlDisabled: function (event)
        {
            if (!this.choices)
            {
                console.error('Choices not setup.')
                return
            }

            this.choices.disable()
        },
    });

    SV.StandardLib.ChoicesLoader = XF.Element.newHandler({
        options: {
            listenTo: '',
            initUpdate: true,
            href: true
        },

        init ()
        {
            let theTarget = this.target || this.$target.get(0)
            if (!theTarget.matches('select'))
            {
                console.error('Must trigger on select')
            }

            if (this.options.href)
            {
                const listenTo = this.options.listenTo ? XF.findRelativeIf(this.options.listenTo, this.target || this.$target) : null
                if (!listenTo)
                {
                    console.error('Cannot load choices dynamically as no element set to listen to for changes')
                }
                else
                {
                    if (typeof XF.on !== "function") // XF 2.2
                    {
                        listenTo.on('change', XF.proxy(this, 'loadChoices'));
                    }
                    else
                    {
                        XF.on(listenTo, 'change', this.loadChoices.bind(this));
                    }

                    if (this.options.initUpdate)
                    {
                        if (typeof XF.trigger === "function")
                        {
                            XF.trigger(listenTo, 'change');
                        }
                        else
                        {
                            listenTo.trigger('change');
                        }
                    }
                }
            }
        },

        loadChoices (e)
        {
            XF.ajax('POST', this.options.href, {
                val: e.target.value
            }, this.loadSuccess.bind(this))
        },

        loadSuccess (data)
        {
            if (data.html)
            {
                const select = this.target || this.$target.get(0)

                // @todo: XF 2.3 compatibility
                XF.setupHtmlInsert(data.html, html =>
                {
                    const val = select.value

                    if (html instanceof HTMLSelectElement)
                    {
                        select.innerHTML = ''
                        select.append(...Array.from(html.children))

                        let hasValue = false
                        const options = select.querySelectorAll('option')

                        options.forEach((option) =>
                        {
                            if (option.value === val)
                            {
                                select.value = val
                                hasValue = true
                            }
                        })
                        if (!hasValue)
                        {
                            select.value = options[0].value
                        }

                        return true
                    }
                })
            }
        }
    })

    XF.Element.register('sv-choices', 'SV.StandardLib.Choices')
    XF.Element.register('sv-choices-loader', 'SV.StandardLib.ChoicesLoader')
}) ();