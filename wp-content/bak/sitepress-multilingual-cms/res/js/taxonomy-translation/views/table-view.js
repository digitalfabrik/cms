/*globals labels */

(function () {
    TaxonomyTranslation.views.TableView = Backbone.View.extend({

        template: TaxonomyTranslation.getTemplate("table"),
        tag: 'div',
        termsView: {},

        model: TaxonomyTranslation.models.Taxonomy,

        initialize: function (data,options) {
            this.type = options.type;
        },

        render: function () {

            var self = this;
            if (!TaxonomyTranslation.classes.taxonomy.get("taxonomy")) {
                return false;
            }

            var tableType = self.type;
            var langs = TaxonomyTranslation.data.activeLanguages;
            var count = tableType === "terms" ? TaxonomyTranslation.data.termRowsCollection.length : 1;

            this.$el.html(self.template({
                langs: langs,
                tableType: tableType,
                count: count
            }));

            return self;
        },
        clear: function () {

        }

    })
})(TaxonomyTranslation);


