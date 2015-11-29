/*jshint browser:true, devel:true */
/*global jQuery, ajaxurl, wpml_tm_words_count_data */

var WPMLWordsCount = function () {
	"use strict";

	var self = this;

	self.dialogPosition = {
		my: "center",
		at: "center",
		of: window
	};

	self.centerDialog = function () {
		if (self.hasOwnProperty('toolDialog') && self.toolDialog.hasClass('ui-dialog-content')) {
			self.toolDialog.dialog('option', 'position', self.dialogPosition);
		}
	};

	self.init = function () {
		self.box = jQuery('.wpml-accordion');
		self.openToolButton = self.box.find('.button-primary');
		self.toolDialog = self.box.find('.inside').find('.dialog');
		self.languageSelector = self.box.find('#source-language-selector');
		self.summary = self.box.find('.summary');
		self.cachedRows = {};

		/**
		 * @namespace wpml_tm_words_count_data.box_status
		 * @type bool
		 * */
		if ('open' === wpml_tm_words_count_data.box_status) {
			self.initialStatus = 0;
		} else {
			self.initialStatus = false;
		}

		if (self.box) {
			self.box.accordion({
				active:      self.initialStatus,
				collapsible: true,
				heightStyle: "content",
				activate:    function (event, ui) {
					var isOpen = (0 === (ui.oldHeader.length + ui.oldPanel.length));

					var elementName = 'wpml_words_count_panel';
					var ajaxAction = 'wpml_set_words_count_panel_default_status';

					jQuery.ajax({
						url:  ajaxurl,
						data: {
							'action':                       ajaxAction,
							'wpml_words_count_panel_nonce': jQuery('[name=' + elementName + '_nonce]').val(),
							'open':                         isOpen
						}
					});
				}
			});
			self.openToolButton.on('click', self.openTool);
			if ('#words-count' === window.location.hash) {
				self.openDialog();
			}
		}
	};

	self.openTool = function (event) {
		event.preventDefault();
		self.openDialog();
	};

	self.openDialog = function () {
		self.toolDialog.dialog({
			resizable:     false,
			modal:         true,
			width:         'auto',
			autoResize:    true,
			position:      self.dialogPosition,
			closeOnEscape: true,
			open:          function () {
				self.hideReport();
				self.selectSourceLanguage();
			}
		});
		self.languageSelector.on('change', self.selectSourceLanguage);

	};

	self.bindCheckBoxes = function () {
		var checkAll;
		var tbody = self.summary.find('.report tbody');

		tbody.find(':checkbox').on('click', self.updateCheckAllStatus);

		checkAll = self.summary.find('[name="check-all"]');

		checkAll.on('click', function () {
			self.updateCheckItemStatus(this);
		});
	};

	self.updateCheckAllStatus= function() {
		var tbody = self.summary.find('.report tbody');
		var checkAll = self.summary.find('[name="check-all"]');
		if (tbody.find(':checkbox').length === tbody.find(':checkbox:checked').length) {
			checkAll.attr('checked', 'checked');
		} else {
			checkAll.removeAttr('checked');
		}

		self.updateTotals();
	};

	self.updateCheckItemStatus= function(element) {
		var tbody = self.summary.find('.report tbody');
		var checkAll = self.summary.find('[name="check-all"]');

		if (jQuery(element).is(':checked')) {
			checkAll.attr('checked', 'checked');
			tbody.find(':checkbox').attr('checked', 'checked');
		} else {
			checkAll.removeAttr('checked');
			tbody.find(':checkbox').removeAttr('checked');
		}
		self.updateTotals();
	};

	self.updateTotals = function () {
		var table = self.summary.find('.report');
		var newValue;

		var foot = table.find('tfoot tr');

		foot.find('td.num').each(function (c, td) {
			var total = jQuery(td);
			total.data('value', 0);
			total.text(0);
		});

		table.find('tbody tr').each(function (r, tr) {
			var row = jQuery(tr);

			var checkBox = row.find(':checkbox');

			row.find('td').each(function (c, td) {
				var grandTotalCellElement;
				var grandTotalCell;
				var cellIndex;
				var totalCellValue;
				var currentTotal;
				var cell;
				var selectedValue;
				cell = jQuery(td);
				if (cell.hasClass('num')) {

					cellIndex = cell.index();

					selectedValue = cell.data('value');

					if (jQuery.isNumeric(selectedValue)) {
						if (!checkBox.is(':checked')) {
							selectedValue = 0;
						}
						selectedValue = parseInt(selectedValue);

						grandTotalCell = foot.find('td')[cellIndex - 1];
						grandTotalCellElement = jQuery(grandTotalCell);
						totalCellValue = grandTotalCellElement.data('value');
						if (isNaN(totalCellValue) || '' === totalCellValue) {
							totalCellValue = 0;
						}
						currentTotal = parseInt(totalCellValue);

						newValue = currentTotal + selectedValue;
						grandTotalCellElement.data('value', newValue);
					}
				}
			});
		});

		foot.find('td.num').each(function (c, td) {
			var total = jQuery(td);
			var totalCellValue = total.data('value');
			var localizedTotal = totalCellValue.toLocaleString();
			total.text(localizedTotal);
		});


	};

	self.showSummaryElement = function (selector) {
		if (self.summary.is(":visible")) {
			self.summary.find(selector).fadeIn(function () {
				self.centerDialog();
			});
		} else {
			self.summary.find(selector).show();
			self.summary.fadeIn(function () {
				self.centerDialog();
			});
		}
	};

	self.hideSummaryElement = function (selector) {
		if (self.summary.is(":visible")) {
			self.summary.find(selector).fadeOut(function () {
				self.centerDialog();
			});
		} else {
			self.summary.find(selector).hide();
			self.centerDialog();
		}
	};

	self.showNoResults = function () {
		self.hideReport();
		self.showSummaryElement('.no-results');
	};

	self.hideNoResults = function () {
		self.hideSummaryElement('.no-results');
	};

	self.showReport = function () {
		self.summary.find('.no-results').hide();
		self.showSummaryElement('.report');
		self.centerDialog();
	};

	self.hideReport = function () {
		self.hideSummaryElement('.report');
		self.centerDialog();
	};

	self.fillTable = function (newsummary) {
		var newTable;
		var summary = self.summary.empty();
		newTable = jQuery(newsummary);
		newTable.appendTo(summary);
	};

	self.getSpinner = function () {
		return self.toolDialog.find('.spinner');
	};

	self.showSpinner = function () {
		self.getSpinner().addClass('is-active');
	};

	self.hideSpinner = function () {
		self.getSpinner().removeClass('is-active');
	};

	self.selectSourceLanguage = function () {
		var sourceLanguage;
		var elementName;
		var ajaxAction;
		var summaryTable;

		elementName = 'wpml_words_count_source_language';
		ajaxAction = 'wpml_words_count_summary';
		self.showSpinner();
		sourceLanguage = self.languageSelector.val();

		self.hideNoResults();

		if (sourceLanguage in self.cachedRows) {
			summaryTable = self.cachedRows[sourceLanguage];
			self.fillTable(summaryTable);
			self.showReport();
			self.bindCheckBoxes();
			self.updateTotals();
			self.hideSpinner();
			self.updateCheckAllStatus();
		} else {
			jQuery.ajax({
				url:      ajaxurl,
				data:     {
					'action':          ajaxAction,
					'nonce':           jQuery('[name=' + elementName + '_nonce]').val(),
					'source_language': sourceLanguage
				},
				success:  function (response) {
					if (response.success) {
						/**
						 * @namespace response.data
						 * @type Array|Object
						 * */
						summaryTable = response.data;

						if ('undefined' !== summaryTable && summaryTable.length) {
							self.fillTable(summaryTable);
							self.cachedRows[sourceLanguage] = summaryTable;
							self.showReport();
							self.bindCheckBoxes();
							self.updateTotals();
						}
					} else {
						self.showNoResults();
					}
					self.updateCheckAllStatus();
				},
				error:    function (xhr, ajaxOptions, thrownError) {
					self.showNoResults();
					console.log(xhr);
					console.log(ajaxOptions);
					console.log(thrownError);
				},
				complete: function () {
					self.hideSpinner();
				}
			});
		}
	};

};

jQuery(document).ready(function () {
	"use strict";

	var wpmlWordsCount = new WPMLWordsCount();
	wpmlWordsCount.init();
});