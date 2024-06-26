var SV = window.SV || {};
SV.StandardLib = SV.StandardLib || {};

(function()
{
    "use strict";

    SV.StandardLib.Choices = XF.Element.newHandler({
        options: {
            choices: {
                maxItemCount: -1,
                removeItemButton: true,
                allowHTML: true,
                shouldSort: false,
                shouldSortItems: false,
                editItems: false,
                resetScrollPosition: false,
                renderSelectedChoices: 'always',//'auto'
            },
        },
        choices: null,

        init: function()
        {
            var field = this.target || this.$target.get(0);
            this.options.choices = this.phraseOptions(this.options.choices);
            this.choices = new Choices(field, this.options.choices);
        },

        phraseOptions: function(choices) {
            choices.loadingText = XF.phrase('choices_loadingText');
            choices.noResultsText = XF.phrase('choices_noResultsText');
            choices.noChoicesText = XF.phrase('choices_noChoicesText');
            choices.itemSelectText = XF.phrase('choices_itemSelectText');
            choices.uniqueItemText = XF.phrase('choices_uniqueItemText');
            choices.customAddItemText = XF.phrase('choices_customAddItemText');
            choices.addItemText = function (value) {
                return XF.phrase('choices_addItemText', {
                    '{value}': value
                });
            };
            this.options.choices.maxItemText = function (maxItemCount) {
                return XF.phrase('choices_maxItemText', {
                    '{maxItemCount}': maxItemCount
                });
            };

            return choices;
        }
    });

    XF.Element.register('sv-choices', 'SV.StandardLib.Choices');
}) ();