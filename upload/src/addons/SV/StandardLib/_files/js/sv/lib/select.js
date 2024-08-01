window.SV = window.SV || {};
window.SV.StandardLib = window.SV.StandardLib || {};
window.SV.$ = window.SV.$ || window.jQuery || null;
window.SV.extendObject = window.SV.extendObject || XF.extendObject || jQuery.extend;

(function()
{
    "use strict";
    let $ = SV.$;

    let dynamicElements = Choices.prototype._createElements,
        dynamicStructure = Choices.prototype._createStructure;
    Choices.prototype._createElements = function ()
    {
        dynamicElements.call(this)

        let el = this.passedElement.element
        this.isDynamicRendered = !el.dataset.rendered
        if (this.isDynamicRendered)
        {
            return
        }

        // patch the created items to link to the pre-rendered elements
        let container = el.closest('.choices.svChoices--inputGroup')
        this.containerOuter.element = container
        this.containerInner.element = container.querySelector('.choices__inner')
        this.input.element = container.querySelector('input[name=search_terms]')
        this.itemList.element = container.querySelector('.choices__list')
    };

    Choices.prototype._createStructure = function ()
    {
        if (this.isDynamicRendered)
        {
            dynamicStructure.call(this)
            return;
        }

        this.containerOuter.element.appendChild(this.dropdown.element)
        if (!this._isTextElement)
        {
            this.dropdown.element.appendChild(this.choiceList.element)
        }

        if (this._isSelectOneElement && this.config.searchEnabled)
        {
            this.dropdown.element.insertBefore(this.input.element, this.dropdown.element.firstChild)
        }

        if (!this._isSelectOneElement)
        {
            this.input.setWidth()
        }

        if (this._isSelectElement)
        {
            this._highlightPosition = 0
            this._isSearching = false
            this._startLoading()
            this._addPredefinedChoices(this._presetChoices)
            this._stopLoading()
        }
    };

    SV.StandardLib.Choices = XF.Element.newHandler({
        options: {
            addChoices: false,
            resetOnSubmit: false,
            placeholder: null,
            maxItemCount: -1,
            removeItemButton: true,
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
            let field = this.target || this.$target.get(0),
                placeholderValue = this.options.placeholder || field.getAttribute('placeholder'),
                placeholder = !!placeholderValue,
                config = SV.extendObject({}, this.options, {
                    allowHTML: false,
                    singleModeForMultiSelect: this.options.maxItemCount === 1,
                    placeholder: placeholder,
                    placeholderValue: placeholder ? placeholderValue : null,
                }, this.getPhrases(), this.getClassNames());
            delete config.resetOnSubmit;

            if (!config.singleModeForMultiSelect && !field.getAttribute('multiple') && config.maxItemCount === -1)
            {
                config.singleModeForMultiSelect = true;
                config.maxItemCount = 1;
                this.options.maxItemCount = 1;
            }

            return config;
        },

        getPhrase: function(name, vars)
        {
            let phrase = XF.phrases[name];
            if (phrase && vars)
            {
                phrase = XF.stringTranslate(phrase, vars);
            }
            return phrase || '';
        },

        getPhrases: function()
        {
            // These phrases should match public:svStandardLib_macros::choices_static_render
            return {
                loadingText: this.getPhrase('svChoices_loadingText'),
                noResultsText: this.getPhrase('svChoices_noResultsText'),
                noChoicesText: this.getPhrase('svChoices_noChoicesText'),
                itemSelectText: this.getPhrase('svChoices_itemSelectText'),
                uniqueItemText: this.getPhrase('svChoices_uniqueItemText'),
                customAddItemText: this.getPhrase('svChoices_customAddItemText'),
                addItemText: function (value) {
                    return this.getPhrase('svChoices_addItemText', {
                        '{value}': value
                    }, '');
                }.bind(this),
                removeItemIconText: '',
                removeItemLabelText: function (value) {
                    return this.getPhrase('svChoices_removeItemLabel', {
                        '{value}': value
                    }, '');
                }.bind(this),
                maxItemText: function (maxItemCount) {
                    return this.getPhrase('svChoices_maxItemText', {
                        '{maxItemCount}': maxItemCount
                    }, '');
                }.bind(this)
            };
        },

        getClassNames: function ()
        {
            // This classes should match public:svStandardLib_macros::choices_static_render
            let classnames = {
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
            };

            if (XF.phrases['svChoices_itemSelectText'])
            {
                classnames.classNames.containerOuter.push('svChoices--select-prompt');
            }

            return classnames;
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

                let $target = $(passedElement);
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
                        let customProperties = option.customProperties;
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
                this.choices.clearStore().setChoices(workingListRaw);
                //this.choices.setChoiceByValue(values);
            }

            if (tempSelect !== null)
            {
                this.choices.passedElement.element.innerHTML = tempSelect.innerHTML;
                this.choices.refresh();
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