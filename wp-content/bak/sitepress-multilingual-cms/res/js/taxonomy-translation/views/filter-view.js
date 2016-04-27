(function () {
    TaxonomyTranslation.views.FilterView = Backbone.View.extend({

        template: TaxonomyTranslation.getTemplate("filter"),
        model: TaxonomyTranslation.models.Taxonomy,
        tag: "div",
        untranslated: false,
        parent: 0,
        lang: 'all',
        search: '',

        events: {
            "change #child_of": "updateFilter",
            "change #status-select": "updateFilter",
            "change #in-lang": "updateLangFilter",
            "click #tax-apply": "clickedButton",
            "keyup #tax-search": "updateFilter"
        },

        initialize: function () {
            this.listenTo(this.model, 'newTaxonomySet', this.render);
            this.listenTo(this.model, 'modeChanged', this.render);
            this.listenTo(TaxonomyTranslation.classes.taxonomy, 'syncDataLoaded', this.updateLangFilter);
        },
        render: function () {
            var self = this;
            var currentTaxonomy = self.model.get("taxonomy");

            if (!currentTaxonomy) {
                return false;
            } else {
                currentTaxonomy = TaxonomyTranslation.data.taxonomies[currentTaxonomy];
            }

            self.$el.html(self.template({
                langs: TaxonomyTranslation.data.activeLanguages,
                taxonomy: currentTaxonomy,
                parents: self.model.get("parents"),
                mode: TaxonomyTranslation.mainView.mode
            }));

            return self;
        },
        clickedButton: function () {
            "use strict";
            var self = this;

            if (TaxonomyTranslation.mainView.mode === 'sync') {
                TaxonomyTranslation.mainView.model.doSync(self.selectedLang());
            } else {
                self.updateLangFilter();
            }

            return self;
        },
        updateLangFilter: function () {
            "use strict";
            var self = this;

            if (TaxonomyTranslation.mainView.mode === 'sync') {
                var newLang = self.selectedLang();
                if (self.lang !== newLang) {
                    TaxonomyTranslation.mainView.syncedLabel = labels.hieraSynced;
                    TaxonomyTranslation.classes.taxonomy.loadSyncData(newLang);
                    self.lang = newLang;
                }
                var button = self.$el.find('#tax-apply');
                button.prop('disabled', false);
                button.val(labels.synchronizeBtn);
            } else {
                self.updateFilter();
            }

            return self;
        },
        updateFilter: function () {
            var self = this;

            var parent = self.$el.find("#child_of").val();
            self.parent = parent != undefined && parent != -1 ? parent : 0;
            var untranslated = self.$el.find("#status-select").val();
            self.untranslated = !!(untranslated != undefined && untranslated == 1);
            self.setSelectVisibility();
            var search = self.$el.find("#tax-search").val();
            self.search = search != undefined && search.length > 1 ? search : 0;

            self.$el.find('#tax-apply').prop('disabled', true);
            self.trigger("updatedFilter");

            return self;
        },

        selectedLang: function(){
            "use strict";

            var self = this;
            var inLangSelect = self.$el.find("#in-lang");

            return inLangSelect.val();
        },
        setSelectVisibility: function(){
            "use strict";
            var self = this;
            var inLangLabel = jQuery('#in-lang-label');
            var inLangSelect = self.$el.find("#in-lang");
            if (self.untranslated || TaxonomyTranslation.mainView.mode === 'sync') {
                var lang = self.selectedLang();
                self.lang = lang != undefined && lang != 'all' ? lang : 'all';
                inLangSelect.show();
                inLangLabel.show();
            } else {
                self.lang = 'all';
                inLangSelect.hide();
                inLangLabel.hide();
            }

            return self;
        }
    })
})(TaxonomyTranslation);
