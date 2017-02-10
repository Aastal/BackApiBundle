var flagContainer = $('.flag-container');
var countryList = $('.country-list');
var country = $('.country');
var selectedFlag = $('.selected-flag');

flagContainer.on('click', function () {
    countryList.toggleClass('hide');
});

country.on('click', function () {
    var dialCode = $(this).find('.dial-code').text();
    var flag = $(this).find('.iti-flag').attr('class').split(' ').slice(-1);

    selectedFlag.find('.iti-flag').removeClass().addClass('iti-flag ' + flag);
    selectedFlag.find('.selected-dial-code').text(dialCode);

    $('.intl-tel-input input').val(dialCode);
});