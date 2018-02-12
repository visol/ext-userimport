$(function () {
    if (Userimport.usernameMustBeMapped || Userimport.emailMustBeMapped) {
        $('form[name=fieldMapping]').submit(function (e) {
            $('#userimport-client-side-validation-errors').hide().find('li').remove();
            var mappedFields = [];
            $(this).find('.userimport-fieldmapping').each(function () {
                var selectedValue = $(this).val();
                if (selectedValue) {
                    mappedFields.push(selectedValue)
                }
            });

            if (Userimport.usernameMustBeMapped && mappedFields.indexOf('username') === -1) {
                $('#userimport-client-side-validation-errors').show().find('ul').append('<li>' + Userimport.usernameMustBeMappedValidation + '</li>');
                e.preventDefault();
            }

            if (Userimport.emailMustBeMapped && mappedFields.indexOf('email') === -1) {
                $('#userimport-client-side-validation-errors').show().find('ul').append('<li>' + Userimport.emailMustBeMappedValidation + '</li>');
                e.preventDefault();
            }
        })
    }
});
