$('.btn-danger').on('click', function () {
    return confirm("Voulez-vous vraiment supprimer ?");
});

$('.confirm-submit').on('click', function () {
    return confirm("Voulez-vous vraiment envoyer ?");
});

$('.datetimepicker').on('click', function () {
    $(this).datetimepicker({
        format: 'd/m/Y H:i',
        lang: '{{ locale }}'
    });
});

$('.collapse-link').on("click", function () {
    var chevron = $(this).find('span');

    if (chevron.hasClass('glyphicon-chevron-down')) {
        chevron.removeClass('glyphicon-chevron-down');
        chevron.addClass('glyphicon-chevron-up');
    } else {
        chevron.removeClass('glyphicon-chevron-up');
        chevron.addClass('glyphicon-chevron-down');
    }
});

/**
 * @param target
 * @param url
 * @param text
 * @param returnObj
 */
function searchAjax(target, url, text, returnObj) {
    $(target).select2({
        placeholder: text,
        allowClear: true,

        ajax: {
            url: url,
            delay: 500,
            dataType: 'json',
            type: "POST",

            data: function (params) {
                return {
                    data: params.term
                }
            },
            processResults: function (data) {
                return {
                    results: $.map(data, function (obj) {

                        if (returnObj instanceof Array) {
                            var result = {
                                id: obj.id
                            };

                            var resultText = '';
                            returnObj.forEach(function (data) {
                                resultText += obj[data] + ' ';
                            });

                            result["text"] = resultText;

                            return result;
                        }

                        if (returnObj == 'user') {
                            return {
                                id: obj.id,
                                text: obj.id + ' : ' + obj["firstname"] + ' ' + obj["lastname"] + ' (' + obj["username"] + ')'
                            };
                        }

                        if (returnObj == 'aggression') {
                            return {
                                id: obj.id,
                                text: obj.id + ' : ' + obj["aggressionTime"].date
                            };
                        }

                        return {
                            id: obj.id,
                            text: obj.id + ' : ' + obj[returnObj]
                        };
                    })
                }
            },
            cache: false
        },
        minimumInputLength: 1
    });
}
