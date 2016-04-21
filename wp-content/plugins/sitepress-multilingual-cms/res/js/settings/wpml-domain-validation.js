var WpmlDomainValidation = function (domainInput, domainCheckBox) {

    return {
        run: function () {
            var textInput = domainInput.val().match(/^(?:.+\/\/)?([\w\.-]*)/)[1];
            if (!textInput) {
                domainCheckBox.prop('checked', false)
            }
            domainInput.val(textInput ? textInput : '');
        }
    }
};