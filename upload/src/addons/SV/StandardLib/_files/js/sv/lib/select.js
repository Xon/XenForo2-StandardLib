var SV = window.SV || {};
SV.StandardLib = SV.StandardLib || {};
SV.$ = SV.$ || window.jQuery || null;
SV.extendObject = SV.extendObject || XF.extendObject || jQuery.extend;

(function()
{
    "use strict";
    var $ = SV.$;

    var dynamicElements = Choices.prototype._createElements,
        dynamicStructure = Choices.prototype._createStructure;
    Choices.prototype._createElements = function () {
        dynamicElements.call(this);

        var el = this.passedElement.element;
        this.isDynamicRendered = !el.dataset.rendered;
        if (this.isDynamicRendered) {
            return;
        }
        // patch the created items to link to the pre-rendered elements
        var container = el.closest('.choices.svChoices--inputGroup')
        this.containerOuter.element = container;
        this.containerInner.element = container.querySelector('.choices__inner');
        this.input.element = container.querySelector('input[name=search_terms]');
        this.itemList.element = container.querySelector('.choices__list');
    };

    Choices.prototype._createStructure = function () {
        if (this.isDynamicRendered) {
            dynamicStructure.call(this);
            return;
        }

        this.containerOuter.element.appendChild(this.dropdown.element);
        if (!this._isTextElement) {
            this.dropdown.element.appendChild(this.choiceList.element);
        }
        if (this._isSelectOneElement && this.config.searchEnabled) {
            this.dropdown.element.insertBefore(this.input.element, this.dropdown.element.firstChild);
        }
        if (!this._isSelectOneElement) {
            this.input.setWidth();
        }
        if (this._isSelectElement) {
            this._highlightPosition = 0;
            this._isSearching = false;
            this._startLoading();
            this._addPredefinedChoices(this._presetChoices);
            this._stopLoading();
        }
    };

    SV.StandardLib.Choices = XF.Element.newHandler({
        options: {
            resetOnSubmit: false,
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
            removeItemButtonAlignLeft: true,
        },
        initialValue: null,
        form: null,
        choices: null,

        init: function()
        {
            let theTarget = this.target || this.$target.get(0);
            this.form = theTarget.closest('form')
            this.initialValue = theTarget.value
            this.choices = new Choices(theTarget, this.getConfig());
            this.initEvents();
        },

        getConfig: function ()
        {
            var field = this.target || this.$target.get(0),
                placeholderValue = this.options.placeholder || field.getAttribute('placeholder'),
                placeholder = !!placeholderValue,
                config = SV.extendObject({}, this.options, {
                pseudoMultiSelectForSingle: this.options.maxItemCount === 1,
                placeholder: placeholder,
                placeholderValue: placeholder ? placeholderValue : null,
            }, this.getPhrases(), this.getClassNames());
            delete config.resetOnSubmit;

            if (!config.pseudoMultiSelectForSingle && !field.getAttribute('multiple') && config.maxItemCount === -1)
            {
                config.pseudoMultiSelectForSingle = true;
                config.maxItemCount = this.options.maxItemCount = 1;
            }

            return config;
        },

        getPhrases: function()
        {
            // These phrases should match public:svStandardLib_macros::choices_static_render
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
            // This classes should match public:svStandardLib_macros::choices_static_render
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

            let form = this.form
            let passedElement = this.choices.passedElement.element;

            if (typeof XF.on !== "function") // XF 2.2
            {
                if (this.options.resetOnSubmit)
                {
                    let $form = $(form)
                    if ($form.length)
                    {
                        //$form.on('ajax-submit:complete', this.onFormReset.bind(this))
                        $form.on('ajax-submit:response', this.afterFormSubmit.bind(this))
                    }
                }

                var $target = $(passedElement);
                $target.on('addItem', this.onAddItem.bind(this));
                $target.on('removeItem', this.onRemoveItem.bind(this));
                $target.on('choice', this.onChoice.bind(this));
                $target.on('showDropdown', this.onShowDropdown.bind(this));
                $target.on('hideDropdown', this.onHideDropdown.bind(this));
                $target.on('control:enabled', this.onControlEnabled.bind(this));
                $target.on('control:disabled', this.onControlDisabled.bind(this));
                $target.on('refreshChoices', this.onRefreshChoices.bind(this));
            }
            else
            {
                if (this.options.resetOnSubmit)
                {
                    if (form instanceof HTMLFormElement)
                    {
                        //XF.on(form, 'ajax-submit:complete', this.onFormReset.bind(this))
                        XF.on(form, 'ajax-submit:response', this.afterFormSubmit.bind(this))
                    }
                }

                XF.on(passedElement, 'addItem', this.onAddItem.bind(this));
                XF.on(passedElement, 'removeItem', this.onRemoveItem.bind(this));
                XF.on(passedElement, 'choice', this.onChoice.bind(this));
                XF.on(passedElement, 'showDropdown', this.onShowDropdown.bind(this));
                XF.on(passedElement, 'hideDropdown', this.onHideDropdown.bind(this));
                XF.on(passedElement, 'control:enabled', this.onControlEnabled.bind(this));
                XF.on(passedElement, 'control:disabled', this.onControlDisabled.bind(this));
                XF.on(passedElement, 'refreshChoices', this.onRefreshChoices.bind(this));
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

        onRefreshChoices: function (event, html, listRaw)
        {
            if (!this.choices)
            {
                console.error('Choices not setup.')
                return
            }

            const workingHtml = typeof event.html === 'undefined' ? html : event.html
            const workingListRaw = typeof event.listRaw === 'undefined' ? listRaw : event.listRaw

            /** @type HTMLSelectElement **/
            let tempSelect = null

            let choices = [],
                values = []

            if (workingHtml instanceof HTMLDivElement)
            {
                tempSelect = workingHtml.querySelector('select')
            }
            else if (workingHtml instanceof HTMLSelectElement)
            {
                tempSelect = workingHtml
            }
            else if (workingListRaw instanceof Object)
            {
                choices = workingListRaw
            }

            if (tempSelect !== null)
            {
                tempSelect.querySelectorAll('*').forEach((optionOrOptgroup) =>
                {
                    if (optionOrOptgroup instanceof HTMLOptionElement)
                    {
                        let isSelected = !!optionOrOptgroup.getAttribute('selected');
                        choices.push({
                            value: optionOrOptgroup.value,
                            label: optionOrOptgroup.text,
                            labelClass: typeof optionOrOptgroup.dataset.labelClass !== 'undefined' ? optionOrOptgroup.dataset.labelClass : null,
                            labelDescription: typeof optionOrOptgroup.dataset.labelDescription !== 'undefined' ? optionOrOptgroup.dataset.labelDescription : null,
                            customProperties: typeof optionOrOptgroup.dataset.customProperties !== 'undefined' ? JSON.parse(optionOrOptgroup.dataset.customProperties) : [],
                            disabled: !!optionOrOptgroup.disabled,
                            selected: isSelected
                        })

                        if (!optionOrOptgroup.disabled && isSelected)
                        {
                            values.push(optionOrOptgroup.value)
                        }
                    }
                    else if (optionOrOptgroup instanceof HTMLOptGroupElement)
                    {
                        let groupedChoices = {
                            label: optionOrOptgroup.label,
                            disabled: optionOrOptgroup.disabled,
                            choices: []
                        }

                        optionOrOptgroup.querySelectorAll('option').forEach((option) =>
                        {
                            let isSelected = !!option.getAttribute('selected');
                            groupedChoices.choices.push({
                                value: option.value,
                                label: option.text,
                                labelClass: typeof option.dataset.labelClass !== 'undefined' ? option.dataset.labelClass : null,
                                labelDescription: typeof option.dataset.labelDescription !== 'undefined' ? option.dataset.labelDescription : null,
                                customProperties: typeof option.dataset.customProperties !== 'undefined' ? JSON.parse(option.dataset.customProperties) : [],
                                disabled: !!option.disabled,
                                selected: isSelected
                            })

                            if (!option.disabled && isSelected)
                            {
                                values.push(option.value)
                            }
                        })
                    }
                })
            }

            this.choices.clearStore().setChoices(choices);
            if (values)
            {
                this.choices.setChoiceByValue(values);
            }
        },

        afterFormSubmit: function(e, data)
        {
            data = data || e.data // XF2.2 compat
            if (data.errors || data.status !== 'ok')
            {
                return;
            }

            if (!this.choices)
            {
                console.error('No choices setup.')
                return
            }

            this.choices._onFormReset()
        },

        onFormReset: function ()
        {
            if (!this.choices)
            {
                console.error('No choices setup.')
                return
            }

            this.choices._onFormReset()
        }
    });

    SV.StandardLib.ChoicesLoader = XF.Element.newHandler({
        options: {
            listenTo: '',
            initUpdate: true,
            href: ''
        },

        init ()
        {
            let theTarget = this.target || this.$target.get(0)
            if (!theTarget.matches('select'))
            {
                console.error('Must trigger on select')
                return
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

                XF.setupHtmlInsert(data.html, html =>
                {
                    if (typeof XF.trigger !== 'function') // XF 2.2
                    {
                        $(select).trigger('refreshChoices', [html.get(0)])
                    }
                    else
                    {
                        XF.trigger(select, XF.customEvent('refreshChoices', {html}))
                    }
                })
            }
        }
    })

    XF.Element.register('sv-choices', 'SV.StandardLib.Choices')
    XF.Element.register('sv-choices-loader', 'SV.StandardLib.ChoicesLoader')
}) ();