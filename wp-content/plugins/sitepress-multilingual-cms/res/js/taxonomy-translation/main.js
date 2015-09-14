/*globals ajaxurl */

/* WCML compatibility */
WPML_Translate_taxonomy = {};
WPML_Translate_taxonomy.callbacks = jQuery.Callbacks();

(function () {


    jQuery(document).ready(function () {
        jQuery('.icl_tt_main_bottom').hide();
        jQuery.ajax({
            url: ajaxurl,
            type: "POST",
            data: {action: 'wpml_get_table_taxonomies'},
            success: function (response) {
                if (response.taxonomies !== undefined && response.activeLanguages !== undefined) {

                    TaxonomyTranslation.data.activeLanguages = response.activeLanguages;
                    TaxonomyTranslation.data.taxonomies = response.taxonomies;
                    TaxonomyTranslation.util.init();

                    var headerHTML = TaxonomyTranslation.getTemplate("main")({taxonomies: TaxonomyTranslation.data.taxonomies});
                    jQuery("#wpml_tt_taxonomy_translation_wrap").html(headerHTML);

                    // WCML compatibility
                    var taxonomySwitcher = jQuery("#icl_tt_tax_switch");
                    var potentialHiddenSelectInput = jQuery('#tax-selector-hidden');
                    var potentialHiddenTaxInput = jQuery('#tax-preselected');
                    if (potentialHiddenSelectInput.length !== 0 && potentialHiddenSelectInput.val() && potentialHiddenTaxInput.length !== 0 && potentialHiddenTaxInput.val()) {
                        var taxonomy = potentialHiddenTaxInput.val();
                        taxonomySwitcher.closest('label').hide();
                        jQuery('[id="term-table-header"]').hide();
                        jQuery('[id="term-table-summary"]').hide();
                        taxonomySwitcher.val(taxonomy);
                        loadModelAndView(taxonomy);
                    } else if ((taxonomy = taxonomyFromLocation()) !== false) {
                        taxonomySwitcher.val(taxonomy);
                        switchToTaxonomy(taxonomy)
                    } else {
                        taxonomySwitcher.one("change", function () {
                            switchToTaxonomy(jQuery(this).val());
                        });
                    }
                }
            }
        });

        function switchToTaxonomy(taxonomy){
            "use strict";
            var spinner = jQuery('.loading-taxonomy');

            spinner.show();
            spinner.closest('div').show();
            loadModelAndView(taxonomy);
            jQuery("#icl_tt_tax_switch").on("change", function () {
                spinner.show();
                jQuery('.icl_tt_main_bottom').hide();
                spinner.closest('div').show();
                jQuery('#taxonomy-translation').html('');
                TaxonomyTranslation.mainView.selectTaxonomy();
            });
        }

        function isSyncTab(){
            "use strict";

            return  window.location.search.substring(1).indexOf('&sync=1') > -1;
        }

        function loadModelAndView(taxonomy){
            "use strict";

            TaxonomyTranslation.classes.taxonomy = new TaxonomyTranslation.models.Taxonomy({taxonomy: taxonomy});
            TaxonomyTranslation.mainView = new TaxonomyTranslation.views.TaxonomyView({model: TaxonomyTranslation.classes.taxonomy}, {sync: isSyncTab()});
        }

        function taxonomyFromLocation() {
            "use strict";
            var queryString = window.location.search.substring(1);
            var taxonomy = false;
            Object.getOwnPropertyNames(TaxonomyTranslation.data.taxonomies).forEach(function (tax) {
                if (queryString.indexOf('taxonomy=' + tax) > -1) {
                    taxonomy = tax;
                }
            });

            return taxonomy;
        }
    })
})(TaxonomyTranslation);