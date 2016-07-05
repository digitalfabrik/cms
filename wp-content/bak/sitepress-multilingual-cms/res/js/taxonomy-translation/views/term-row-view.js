(function () {

    TaxonomyTranslation.views.TermRowView = Backbone.View.extend({

        tagName: "tr",
        model: TaxonomyTranslation.models.TermRow,
        termViews: {},

        initialize: function () {
            var self = this;

            self.listenTo(TaxonomyTranslation.classes.taxonomy, 'syncDataLoaded', self.maybeHide);
        },
        maybeHide: function () {
            "use strict";
            var self = this;
            var visible = false;
            var terms = self.model.get("terms");
            _.each(TaxonomyTranslation.data.syncData, function (correction) {
                _.each(terms, function (term) {
                    if (correction.translated_id == term.get("term_taxonomy_id")) {
                        visible = true;
                    }
                })
            });
            if (visible) {
                self.$el.show();
            } else {
                self.$el.hide();
            }
        },

        render: function () {

            var termsFragments = {};
            var self  = this;
            var langs = TaxonomyTranslation.util.langCodes;
            var terms = self.model.get("terms");
            _.each(langs, function (lang) {
                var term = terms[lang];
                if (term === undefined) {
                    term = new TaxonomyTranslation.models.Term({language_code: lang, trid: self.model.get("trid")});
                    terms[lang] = term;
                    self.model.set("terms", terms, {silent: true});
                }
                var newView = new TaxonomyTranslation.views.TermView({model: term});
                self.termViews[lang] = newView;
                if (TaxonomyTranslation.mainView.mode === 'sync') {
                    termsFragments[lang] = newView.loadSyncData().el;
                } else {
                    termsFragments[lang] = newView.render().el;
                }
            });

            var newRowFragment = document.createDocumentFragment();

            _.each(langs, function(lang){
                newRowFragment.appendChild(termsFragments[lang]);
            });

            self.$el.html(newRowFragment);

            return self;

        }
    })
}(TaxonomyTranslation));

