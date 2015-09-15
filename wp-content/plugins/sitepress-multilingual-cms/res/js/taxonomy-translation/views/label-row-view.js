(function () {

    TaxonomyTranslation.views.LabelRowView = Backbone.View.extend({

        tagName: "tbody",
        template: TaxonomyTranslation.getTemplate("labelRow"),
        model: TaxonomyTranslation.models.Taxonomy,
        events: {
            "click .icl_tt_label": "openPopUPLabel"
        },
        initialize: function () {
            var self = this;
            self.listenTo(self.model, 'labelTranslationSaved', self.render);
        },

        render: function () {
            var self = this;
            self.$el.html(self.template({
                taxLabels: TaxonomyTranslation.data.translatedTaxonomyLabels,
                langs: TaxonomyTranslation.util.langCodes,
                taxonomy: self.model.get("taxonomy")
            }));

            self.delegateEvents();
            return self;
        },
        openPopUPLabel: function (e) {

            e.preventDefault();

            var id = e.target.id;
            var lang = id.split('_').pop();

            if (TaxonomyTranslation.classes.labelPopUpView && typeof TaxonomyTranslation.classes.labelPopUpView !== 'undefined') {
                TaxonomyTranslation.classes.labelPopUpView.close();
            }

            TaxonomyTranslation.classes.labelPopUpView = new TaxonomyTranslation.views.LabelPopUpView({model: TaxonomyTranslation.classes.taxonomy}, {
                lang: lang,
                defLang: TaxonomyTranslation.classes.taxonomy.get("defaultLang")
            });
            var popUpHTML = TaxonomyTranslation.classes.labelPopUpView.render().el;
            var popUpDomEl = jQuery("#popup-" + lang);
            popUpDomEl.html(popUpHTML);
            var iclttForm = popUpDomEl.find('.icl_tt_form');
            jQuery('.icl_tt_form').hide();
            iclttForm.show();
        }
    })
}(TaxonomyTranslation));