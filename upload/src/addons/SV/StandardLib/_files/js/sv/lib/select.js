var SV = window.SV || {};
SV.StandardLib = SV.StandardLib || {};
SV.$ = SV.$ || window.jQuery || null;
SV.extendObject = SV.extendObject || XF.extendObject || jQuery.extend;

(function()
{
    "use strict";
    let $ = SV.$;

    /**
     * @param s string
     * @return {string}
     */
    function ucfirst(s) {
        return s && s.charAt(0).toUpperCase() + s.slice(1);
    }

    let dynamicElements = Choices.prototype._createElements,
        dynamicStructure = Choices.prototype._createStructure;
    Choices.prototype._createElements = function ()
    {
        dynamicElements.call(this)

        const el = this.passedElement.element
        this.isDynamicRendered = !el.dataset.rendered
        if (this.isDynamicRendered)
        {
            return
        }

        const wrapOrWarn = function (container, element, selector)
        {
            if (element === null) {
                console.warn('Expected pre-rendered element for '+selector+' does not exist', el);
                return;
            }
            container.element = element
        }

        // patch the created items to link to the pre-rendered elements
        const container = el.closest('.choices.svChoices--inputGroup')
        wrapOrWarn(this.containerOuter, container, '.choices.svChoices--inputGroup')
        if (container) {
            wrapOrWarn(this.containerInner, container.querySelector('.choices__inner'), '.choices__inner')
            wrapOrWarn(this.input, container.querySelector('input[type="search"]'), 'input[type="search"]')
            wrapOrWarn(this.itemList, container.querySelector('.choices__list'), '.choices__list')
        }
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

        this._highlightPosition = 0
        this._isSearching = false
    };

    SV.StandardLib.Choices = XF.Element.newHandler({
        options: {
            resetOnSubmit: false,
            placeholder: null,
            removeItemButton: true,
            removeItemButtonAlignLeft: true,
            shouldSort: false,
            shouldSortItems: false,
            editItems: false,
            resetScrollPosition: false,
            renderSelectedChoices: 'always',
            renderChoiceLimit: -1,
            searchResultLimit: -1,
            appendGroupInSearch: true,

            // to append a class to various styling elements, use `containerOuter` => `data-class-container-outer`
            // see https://github.com/Xon/Choices.js?tab=readme-ov-file#classnames for class keys
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

            // if the user typed text into the search input before choices initialized, ensure search works
            if (typeof this.choices.isDynamicRendered === 'boolean' && !this.choices.isDynamicRendered && this.choices._canSearch) {
                const searchValue = this.choices.input.value;
                if (searchValue || this.choices.input.element === document.activeElement) {
                    this.choices.showDropdown(false);
                    this.choices.input.isFocussed = true;
                    if (searchValue) {
                        this.choices._handleSearch(searchValue);
                    }
                }
            }
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
                    classNames: this.getClassNames(),
                    fuseOptions: this.getFuseOptions(),
                }, this.getPhrases());
            delete config.resetOnSubmit;

            if (!config.singleModeForMultiSelect && !field.getAttribute('multiple') && config.maxItemCount === -1)
            {
                config.singleModeForMultiSelect = true;
                config.maxItemCount = 1;
                this.options.maxItemCount = 1;
            }
            // support arbitrary choices.js config options as data-* attributes
            const dataset = (this.target || this.$target.get(0)).dataset,
                defaultOptions = Choices.defaults.allOptions;
            Object.keys(defaultOptions).forEach((key) =>
            {
                if (key in config || !(key in dataset)) {
                    return;
                }

                let v = dataset[key];
                let setValue = true;
                switch(typeof defaultOptions[key]) {
                    case 'string':
                        // is this JSON? try to parse to an object
                        try
                        {
                            v = JSON.parse(v)
                        }
                        catch (error)
                        {
                            // ignore
                        }
                        break;
                    case 'number':
                        v = Number(v)
                        if (isNaN(v))
                        {
                            setValue = false
                        }
                        break;
                    case 'boolean':
                        v = XF.toBoolean(v);
                        break;
                    default:
                        return;
                }

                if (setValue) {
                    config[key] = v;
                }
            });

            return config;
        },

        getFuseOptions: function () {
            return {
                // When `true`, the algorithm continues searching to the end of the input even if a perfect
                // match is found before the end of the same input.
                //isCaseSensitive: false,
                // Approximately where in the text is the pattern expected to be found?
                //location: 0,
                // At what point does the match algorithm give up. A threshold of '0.0' requires a perfect match
                // (of both letters and location), a threshold of '1.0' would match anything.
                //threshold: 0.6,
                // Determines how close the match must be to the fuzzy location (specified above).
                // An exact letter match which is 'distance' characters away from the fuzzy location
                // would score as a complete mismatch. A distance of '0' requires the match be at
                // the exact location specified, a threshold of '1000' would require a perfect match
                // to be within 800 characters of the fuzzy location to be found using a 0.8 threshold.
                //distance: 100
            };
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
            const classNames = {
                containerOuter: [
                    'choices',
                    'inputGroup',
                    'svChoices--inputGroup',
                ],
                containerInner: [
                    'choices__inner',
                    'input',
                ],
            };

            if (XF.phrases['svChoices_itemSelectText'])
            {
                classNames.containerOuter.push('svChoices--select-prompt');
            }

            const dataset = (this.target || this.$target.get(0)).dataset,
                defaultClassNames = Choices.defaults.allOptions.classNames;

            Object.keys(defaultClassNames).forEach((key) =>
            {
                const datasetKey = 'class' + ucfirst(key)
                const fromDataset = dataset[datasetKey]

                if (typeof fromDataset !== 'undefined')
                {
                    const parts = ('' + fromDataset).split(' ')
                    if (parts.length !== 0)
                    {
                        if (!(key in classNames))
                        {
                            classNames[key] = []
                        }

                        defaultClassNames[key].forEach(part => classNames[key].push(part));
                        parts.forEach(part => classNames[key].push(part));
                    }
                }
            });

            return classNames;
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
                    this.choices._store.items.forEach((option) =>
                    {
                        let customProperties = option.customProperties;
                        if (typeof customProperties === 'object' && customProperties.clears)
                        {
                            this.choices.removeActiveItemsByValue(option.value);
                        }
                    })
                }
            }

            this.triggerFormFillIfNeeded()
        },

        onRemoveItem: function (event)
        {
            this.triggerFormFillIfNeeded()
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
        },

        triggerFormFillIfNeeded ()
        {
            const formFillHandler = XF.Element.getHandler(this.target ? this.form : $(this.form), 'form-fill')
            if (formFillHandler !== null)
            {
                formFillHandler.change()
            }
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