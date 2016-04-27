(function () {

    TaxonomyTranslation.views.TaxonomyView = Backbone.View.extend({

        el: "#taxonomy-translation",
        model: TaxonomyTranslation.models.Taxonomy,
        tag: "div",
        termRowsView: {},
        mode: 'translate',
        initialMode: 'translate',
        perPage: 10,
        events: {
            "click #term-table-sync-header": "setToSync",
            "click #term-table-header": "setToTranslate"
        },
        syncedLabel: labels.hieraAlreadySynced,
        initialize: function (model, options) {
            var self = this;
            self.initialMode = options.sync === true ? 'sync' : 'translate';
            self.navView = new TaxonomyTranslation.views.NavView({model: self.model}, {perPage: self.perPage});
            self.filterView = new TaxonomyTranslation.views.FilterView({model: self.model});
            self.listenTo(self.filterView, 'updatedFilter', function () {
                self.navView.page = 1;
                self.renderRows();
            });
            self.termTableView = new TaxonomyTranslation.views.TableView({model: self.model}, {type: "terms"});
            self.labelTableView = new TaxonomyTranslation.views.TableView({model: self.model}, {type: "labels"});
            self.termRowsView = new TaxonomyTranslation.views.TermRowsView({collection: TaxonomyTranslation.data.termRowsCollection}, {
                start: 0,
                end: self.perPage
            });
            self.listenTo(self.model, 'newTaxonomySet', self.renderNewTaxonomy);
            self.listenTo(self.model, 'syncDataLoaded', self.renderNewTaxonomy);

            return self;
        },
        changeMode: function (mode) {
            "use strict";
            var self = this;
            self.mode = mode === 'translate' || ( mode === 'sync' && self.model.isHierarchical() ) ? mode : 'translate';
            self.navView.off();
            self.navView = new TaxonomyTranslation.views.NavView({model: self.model}, {perPage: self.perPage});
            self.listenTo(self.navView, 'newPage', self.render);

            if (self.mode === "sync") {
                self.model.loadSyncData(self.filterView.selectedLang());
            } else {
                self.renderRows();
                self.render();
            }
            self.model.trigger('modeChanged');

            return self;
        },
        setToTranslate: function () {
            "use strict";
            var self = this;
            if (self.mode !== 'translate') {
                self.changeMode('translate');
            }

            return self;
        },
        setToSync: function () {
            "use strict";
            var self = this;
            if (self.mode !== 'sync') {
                self.changeMode('sync');
            }

            return self;
        },
        setLabels: function () {
            var self = this;
            var tax = self.model.get("taxonomy");
            var taxonomyDefaultLabel = TaxonomyTranslation.data.taxonomies[tax].label;
            self.headerTerms = labels.translate + " " + taxonomyDefaultLabel;
            self.summaryTerms = labels.summaryTerms.replace('%taxonomy%', taxonomyDefaultLabel);
            self.labelSummary = labels.summaryLabels.replace('%taxonomy%', taxonomyDefaultLabel);

            return self;
        },
        renderRows: function () {
            var self = this;
            if (TaxonomyTranslation.data.termRowsCollection.length > 0) {
                self.termRowsView.start = (self.navView.page - 1 ) * self.perPage;
                self.termRowsView.end = self.termRowsView.start + self.perPage;
                var termRowsFragment = self.termRowsView.render().el;
                jQuery("#tax-table-terms").first('tbody').append(termRowsFragment);
            }
            self.navView.render();

            return self;
        },
        renderNewTaxonomy: function(){
            var self = this;
            self.navView.off();
            self.navView = undefined;
            self.navView = new TaxonomyTranslation.views.NavView({model: self.model}, {perPage: self.perPage});
            this.listenTo(this.navView, 'newPage', this.render);
            self.renderRows();
            self.render();
            if (self.initialMode === 'sync') {
                self.initialMode = false;
                self.setToSync();
            }
            return self;
        },
        getMainFragment: function () {
            "use strict";
            var self = this;

            var mainFragment = document.createElement("div");
            var mainTemplate = TaxonomyTranslation.getTemplate("taxonomyMainWrap");
            var tabsTemplate = TaxonomyTranslation.getTemplate("tabs");
            var taxonomy = TaxonomyTranslation.data.taxonomies[self.model.get("taxonomy")];

            var htmlTabs = tabsTemplate({
                taxonomy: taxonomy,
                headerTerms: self.headerTerms,
                syncLabel: labels.Synchronize,
                mode: self.mode
            });
            var htmlMain;
            if (self.mode === 'sync'
                && (!self.termRowsView || self.termRowsView.getDisplayCount() === 0 ) ) {
                htmlMain = ['<h2 style="clear: both; padding-top: 10px;">', self.syncedLabel, '</h2>'].join('');
                self.syncedLabel = labels.hieraAlreadySynced;
                mainFragment.innerHTML = ([htmlTabs, htmlMain].join(''));
            } else {
                htmlMain = mainTemplate({
                    taxonomy: taxonomy,
                    langs: TaxonomyTranslation.data.activeLanguages,
                    summaryTerms: self.summaryTerms,
                    labelSummary: self.labelSummary,
                    mode: self.mode
                });
                mainFragment.innerHTML = ([htmlTabs, htmlMain].join(''));
                mainFragment = self.addMainElements(mainFragment);
            }

            return mainFragment;
        },
        addMainElements: function (mainFragment) {
            "use strict";
            var self = this;
            self.filterFragment = self.filterFragment ? self.filterFragment : self.filterView.render().el;
            mainFragment.querySelector("#wpml-taxonomy-translation-filters").appendChild(self.filterFragment);
            var termTableFragment = self.termTableView.render().el;
            mainFragment.querySelector("#wpml-taxonomy-translation-terms-table").appendChild(termTableFragment);
            mainFragment = self.addTableRows(mainFragment);

            return mainFragment;
        },
        render: function () {
            "use strict";
            var self = this;

            self.setLabels();
            var renderedFragment = document.createDocumentFragment();
            var mainFragment = self.getMainFragment();
            renderedFragment.appendChild(mainFragment);

            if (TaxonomyTranslation.data.termRowsCollection.length > self.perPage
                && renderedFragment.querySelector("#wpml-taxonomy-translation-terms-nav")) {
                var navFragment = self.navView.render().el;
                renderedFragment.querySelector("#wpml-taxonomy-translation-terms-nav").appendChild(navFragment);
            }
            self.addLabelTranslation(mainFragment, renderedFragment);
            self.$el.html(renderedFragment);
            jQuery(".icl_tt_label").on("click", self.openPopUPLabel);
            jQuery('.loading-taxonomy').closest('div').hide();
            self.isRendered = true;
            self.filterView.delegateEvents();
            self.delegateEvents();
            self.maybeHideHeader();
            jQuery('.icl_tt_main_bottom').show();

            return self;
        },
        addLabelTranslation: function (mainFragment, renderedFragment) {
            "use strict";
            var self = this;

            if (TaxonomyTranslation.data.translatedTaxonomyLabels && self.mode !== 'sync') {
                var labelTableFragment = self.labelTableView.render().el;
                mainFragment.querySelector("#wpml-taxonomy-translation-labels-table").appendChild(labelTableFragment);
                if (renderedFragment.querySelector("#tax-table-labels")) {
                    var labelRowFragment = new TaxonomyTranslation.views.LabelRowView(({model: self.model})).render().el;
                    mainFragment.querySelector("#tax-table-labels").appendChild(labelRowFragment);
                }
            }

            return mainFragment;
        },
        addTableRows: function (mainFragment) {
            "use strict";
            var self = this;
            var termRowsFragment;

            if (TaxonomyTranslation.data.termRowsCollection.length > 0) {
                self.termRowsView.start = (self.navView.page - 1 ) * self.perPage;
                self.termRowsView.end = self.termRowsView.start + self.perPage;
                termRowsFragment = self.termRowsView.render().el;
                mainFragment.querySelector("#tax-table-terms").appendChild(termRowsFragment);
            }

            return mainFragment;
        },
        /**
         * Used by WCML to hide the controls for changing the taxonomy
         */
        maybeHideHeader: function () {
            "use strict";
            var taxonomySwitcher = jQuery("#icl_tt_tax_switch");
            var potentialHiddenSelectInput = jQuery('#tax-selector-hidden');
            var potentialHiddenTaxInput = jQuery('#tax-preselected');
            if (potentialHiddenSelectInput.length !== 0
                && potentialHiddenSelectInput.val()
                && potentialHiddenTaxInput.length !== 0
                && potentialHiddenTaxInput.val()) {
                taxonomySwitcher.closest('label').hide();
                jQuery('[id="term-table-summary"]').hide();
            }
        },
        selectTaxonomy: function () {
            var tax = jQuery("#icl_tt_tax_switch").val();
            if (tax != undefined && tax != this.model.get("taxonomy")) {
                this.model.setTaxonomy(tax);
            }
        }

    });
})(TaxonomyTranslation);
