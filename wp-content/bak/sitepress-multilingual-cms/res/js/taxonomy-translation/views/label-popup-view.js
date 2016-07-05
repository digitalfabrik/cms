(function () {
    TaxonomyTranslation.views.LabelPopUpView = Backbone.View.extend({

        tagName: "div",
        template: TaxonomyTranslation.getTemplate("labelPopUp"),
        model: TaxonomyTranslation.models.Taxonomy,

        events: {
            "click .cancel": "close",
            "click .label-save": "saveLabel",
            "keydown" : "handleEnter"
        },
        initialize: function (data, options) {
            var self = this;
            self.lang = options.lang;
            self.defLang = options.defLang;
            self.listenTo(self.model, 'labelTranslationSaved', self.close);
            self.listenTo(self.model, 'saveFailed', self.render);
            return self;
        },

        open: function () {
            this.$el.show();
        },
        close: function () {
            this.$el.hide();
            this.undelegateEvents();
            this.remove();
        },
        render: function () {
            var self = this;
            var taxonomy = self.model.get("taxonomy");
            var labels = TaxonomyTranslation.data.translatedTaxonomyLabels[self.lang];
            var originalLabels = TaxonomyTranslation.data.translatedTaxonomyLabels[self.model.get('stDefaultLang')];

            if (!labels) {
                labels = {
                    singular: undefined,
                    plural: undefined
                }
            }

            this.$el.html(
                self.template({
                    langs: TaxonomyTranslation.data.activeLanguages,
                    lang: self.lang,
                    originalS: originalLabels.singular,
                    originalP: originalLabels.general,
                    transS: labels.singular,
                    transP: labels.general,
                    taxonomy: taxonomy

                })
            );

            self.delegateEvents();
            return self;
        },

        handleEnter: function(e){
            var self = this;
            if(self.$el.find('input:focus').length !== 0 && e.keyCode == 13){
                self.saveLabel(e);
            }
            return self;
        },

        saveLabel: function (e) {
            var singularValueField, pluralValueField, singularValue, pluralValue, self, inputPrefix;
            self = this;

            e.preventDefault();

            inputPrefix = '#' + self.model.get("taxonomy") + '-';
            singularValueField = self.$el.find(inputPrefix + 'singular');
            pluralValueField = self.$el.find(inputPrefix + 'plural');

            if (singularValueField.length > 0 && pluralValueField.length > 0) {
                singularValue = singularValueField.val();
                pluralValue = pluralValueField.val();
            }

            if (singularValue && pluralValue) {
                self.undelegateEvents();

                self.$el.find(".spinner").show();
                self.$el.find(".label-save").hide();
                self.$el.find(".cancel").hide();

                self.model.saveLabel(singularValue, pluralValue, self.lang);

            }

            return self;

        }
    });
})(TaxonomyTranslation);