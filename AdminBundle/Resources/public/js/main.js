$(".scroll").click(function(event) {
    event.preventDefault();
    $('html,body').animate({scrollTop: $(this.hash).offset().top - 100}, 800);
});

$(document).on('click', '.btn-danger', function () {
    return confirm("Voulez-vous vraiment supprimer ?");
});

$(document).on('click', '.confirm-submit', function () {
    return confirm("Voulez-vous vraiment envoyer ?");
});

$('.datetimepicker').datetimepicker({
    format: 'd/m/Y H:i',
    lang: '{{ locale }}'
});

$('.datepicker').datetimepicker({
    format: 'd/m/Y',
    lang: '{{ locale }}'
});

$(document).on('click', '.btn-list-display', function () {
    $(this).parent().parent().find('.multiple').toggle('slide');

    if ($(this).hasClass('glyphicon-chevron-down')) {
        $(this).removeClass('glyphicon-chevron-down');
        $(this).addClass('glyphicon-chevron-up');
    } else {
        $(this).removeClass('glyphicon-chevron-up');
        $(this).addClass('glyphicon-chevron-down');
    }
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

$(document).on('click', '#changePassword', function () {
    var submit = $(document).find('#submit');
    var id = $('#user-id').text();
    var entityName = $('#entity-name').text();
    var globalTimeout = null;

    $.ajax({
        url: Routing.generate('geoks_admin_' + entityName + '_update', {'id': id, 'changePassword': "true"}),
        method: "GET",

        success: function (html) {
            $('#form-changePassword').replaceWith($(html).find('#form-changePassword'));

            var password = $('#plainPassword');
            var confirm_password = $('#passwordConfirm');

            function validatePassword () {
                globalTimeout = setTimeout(function () {
                    globalTimeout = null;
                    if (password.val() !== confirm_password.val() && confirm_password.val()) {
                        if (globalTimeout !== null) {
                            clearTimeout(globalTimeout);
                        }

                        submit.addClass('disabled');
                        submit.attr('disabled', 'disabled');

                        if (!$('#password-error').length) {
                            $(confirm_password).parent().parent().after(
                                "<br>" +
                                "<div id='password-error' class='form-error'>" +
                                "<ul>" +
                                "<li>Le mot de passe n'est pas identique à la confirmation</li>" +
                                "</ul>" +
                                "</div>"
                            );
                        }
                    } else {
                        $('#password-error').remove();
                        submit.removeClass('disabled');
                        submit.removeAttr('disabled');
                    }
                }, 800);
            }

            $(password).on('keyup', function () {
                validatePassword();
            });

            $(confirm_password).on('keyup', function () {
                validatePassword();
            });
        }
    })
});

/**
 * @param target
 * @param url
 * @param text
 * @param returnObj
 */
function searchAjax(target, url, text, returnObj) {
    var form = "";
    target = target.replace('#', '');

    var targetEntity = target.split("_");
    targetEntity = targetEntity[targetEntity.length - 1];

    if ($("#geoks_admin_create_" + targetEntity).length) {
        form = "geoks_admin_create";
    }

    if ($("#geoks_admin_update_" + targetEntity).length) {
        form = "geoks_admin_update";
    }

    target = "#" + form + "_" + targetEntity;

    var select2 = $(target).select2({
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

                        if (returnObj === 'user') {
                            return {
                                id: obj.id,
                                text: obj.id + ' : ' + obj["firstname"] + ' ' + obj["lastname"] + ' (' + obj["username"] + ')'
                            };
                        }

                        if (returnObj === 'aggression') {
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

    select2.select2({
        allowClear: true,
        theme: "bootstrap",
        placeholder: 'Rechercher...',
        width: '100%'
    });

    select2.on("select2:selecting", function (e) {
        var id = e.params['args']['data']['id'];
        var text = e.params['args']['data']['text'];

        if (!$(target + "_" + id).length) {
            $(target).parent().parent().find('.multiple').append(
                "<li>" +
                "<input type='checkbox' id=" + target.replace('#', '') + "_" + id + " name=" + form + "[" + targetEntity + "][] hidden='hidden' value='" + id +"' checked='checked'>" +
                "<button role='button' class='btn btn-default btn-list-remove'>" + text + "</button>" +
                "</li>"
            );
        }
    });

    $(document).on('click', ".btn-list-remove", function () {
        $(this).parent().remove();
    });
}

var loader = $("#loader");
var container = $("#box-data");

$(document).on('click', 'ul.pagination li a', function (e) {
    e.preventDefault();

    var url = $(this).attr("href");

    container.html(loader.css('display', 'block'));

    $.ajax({
        url: url,
        method: "GET",

        success: function (html) {
            $("#loader").hide();
            window.history.pushState(container.html(), "", url);

            container.html($(html).find("#box-data"));
            $("body").append(loader);
            $("#pagination").html($(html).find("#pagination"));

        }
    });
});

window.addEventListener('popstate', function() {
    loader.css('display', 'block');
    $("#loader-container").css('display', 'block').addClass('loader-all');

    window.location.reload();
});

function uploadCheck(input) {
    var url = input.value;
    var ext = url.substring(url.lastIndexOf('.') + 1).toLowerCase();
    var placeholder = document.getElementById(input.id + '-placeholder');

    if (input.files && input.files[0] && (ext == "gif" || ext == "png" || ext == "jpeg" || ext == "jpg")) {
        var reader = new FileReader();

        reader.onload = function (e) {
            placeholder.setAttribute('src',  e.target.result);
        };

        reader.readAsDataURL(input.files[0]);
    } else {
        placeholder.setAttribute('src', "");
    }
}

$(document).on('change', '#geoks_import_type', function () {
    var warning = $(".warning");

    if (this.value === "replace") {
        warning.show();
        warning.parent().find('.btn-blue').attr('disabled', 'disabled');
    } else {
        warning.hide();
        warning.parent().find('.btn-blue').removeAttr('disabled');
    }
});

$(document).on('change', '#check-import', function () {
    var warning = $(".warning");

    if (this.checked) {
        warning.parent().find('.btn-blue').removeAttr('disabled');
    } else {
        warning.parent().find('.btn-blue').attr('disabled', 'disabled');
    }
});

$(document).on('click', '#box-data .checkbox-animate', function () {
    var btnDelete = $('#btn-multiple-delete');
    var btnExport = $('#btn-multiple-export');

    if (btnDelete.hasClass('disabled')) {
        btnDelete.removeClass('disabled').removeAttr('disabled');
        btnExport.removeClass('disabled').removeAttr('disabled');
    }
});

$(document).on('click', "#selectAll", function () {
    var btnDelete = $('#btn-multiple-delete');
    var btnExport = $('#btn-multiple-export');

    if ($(this).data("check") === false) {
        $(this).data("check", true);
        $(this).text("Tout désélectionner");

        $('.checkbox-animate').prop("checked", true);

        btnDelete.removeClass('disabled').removeAttr('disabled');
        btnExport.removeClass('disabled').removeAttr('disabled');
    } else {
        $(this).data("check", false);
        $(this).text("Tout sélectionner");

        $('.checkbox-animate').prop("checked", false);

        btnDelete.addClass('disabled').attr('disabled');
        btnExport.addClass('disabled').attr('disabled');
    }
});

function multipleDelete(all) {
    var ids = [];
    var entityName = $('#entity-name').text();

    $("input[name='list-checkbox[]']").each(function() {
        if ($(this).prop('checked')) {
            ids.push($(this).val());
        }
    });

    var data = {ids : ids, all: all};

    if (all !== true) {
        data = {ids : ids};

        deleteAction(data, entityName);
    } else {
        $('#myModalWarning').modal();
    }
}

$(document).on('click', '#confirm-delete', function () {
    var entityName = $('#entity-name').text();
    var data = {ids : null, all: true};

    deleteAction(data, entityName);
});

function deleteAction(data, entityName) {
    $.ajax({
        type: "POST",
        url: Routing.generate('delete_' + entityName + '_entities'),
        data: data,

        success: function() {
            window.location.reload();
        }
    });
}

function multipleExport() {
    var datas = [];
    var entityName = $('#entity-name').text();

    $("input[name='list-checkbox[]']").each(function() {
        if ($(this).prop('checked')) {
            datas.push($(this).val());
        }
    });

    $.ajax({
        type: "POST",
        url: Routing.generate('export_' + entityName + '_entities'),
        data: { datas: datas },

        success: function (response) {
            window.location = window.location.origin + "/exports/" + response.success;
        }
    });
}
