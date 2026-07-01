//==============================================================================
const url       = new URL("https://api.onlis.store/");
const rqst_path = new URL("main_request_modules.php", url);
const spnr_loading = `<div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div>`;
window.user_perms = [];
//==============================================================================
$(function() {
    const offsetMinutes = new Date().getTimezoneOffset();
    const offsetHours   = -offsetMinutes / 60;
    const offsetString  = (offsetHours >= 0 ? '+' : '') + offsetHours;
    document.cookie = `user_tz_offset=${offsetString};path=/;max-age=604800;secure;samesite=Lax`;
    //++++++++++++++++++++++++++++++++++
    if ($('meta[name="csrf-token"]').length === 0) {
        fncStartForm();
    } else {
        fncSetupToken();
        fncMyAjax("in_cntrl", "main")
            .done(function(data) {
                if (data.sccss) {
                    window.user_perms = data.rules || [];
                    $("#mainContainer").load(new URL(data.path, url).href);
                } else {
                    fncStartForm();
                }
            })
            .fail(function() {
                fncStartForm();
            });
    }
});
//==============================================================================
function fncStartForm() {
    let start_path = new URL("index_start.php", url);
    $("#mainContainer").load(start_path.href, function(){
        //++++++++++++++++++++++++++++++
        $(".btnInNmb").click(function(){
            if ($(this).val() != -1) {
                let lgnl = $("#inpUsrLogin").val().length;
                if (lgnl < 5) {
                    $("#inpUsrLogin").val($("#inpUsrLogin").val() + $(this).val());
                } else {
                    $("#inpUsrPsw").val($("#inpUsrPsw").val() + $(this).val());
                }
            } else {
                $("#inpUsrLogin, #inpUsrPsw").val("");
            }
        });
        //++++++++++++++++++++++++++++++
        $("#formLogin").submit(function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            let crt_arr = fncParamsCrt(".form-inp");
            if (crt_arr["all_good"]) {
                $("#btnText, #divLoadingLoginForm").toggleClass("d-none");
                fncMyAjax("in", "main", crt_arr["params"])
                    .done(function(data) {
                        if (data.sccss) {
                            window.location.reload();
                        } else {
                            fncStartForm();
                        }
                    })
                    .fail(function() {
                        fncStartForm();
                        $("#btnText, #divLoadingLoginForm").toggleClass("d-none");
                        $("#inpUsrLogin, #inpUsrPsw").val("");
                    });
            }
        });
        //++++++++++++++++++++++++++++++
    });
}
//==============================================================================
function fncNormalizeInp(elmnt, type) {
    const $el = $(elmnt);
    let val = $el.val().trim();

    const filters = {
        digits_only:     (v) => v.replace(/[^\d]/g, ''),
        digits_double:   (v) => v.replace(/,/g, ".").replace(/[^\d.]/g, ""),
        email:           (v) => v.replace(/[а-яА-Я]/g, '').replace(/['"]/g, ''),
        eng_text:        (v) => v.replace(/[^a-z0-9]/g, ''),
        without_letters: (v) => v.replace(/[a-zA-Zа-яА-ЯёЁ]/g, ''),
        text:            (v) => v.replace(/<[^>]*>/g, '')
    };

    if (filters[type]) {
        val = filters[type](val);
    }

    $el.val(val);
}
//==============================================================================
function fncItemCrt(elmnt, el_type, required) {
    const $el = $(elmnt);
    fncNormalizeInp($el, el_type);
    const val         = $el.val()?.trim() || "";
    const is_required = required !== undefined;
    const name        = $el.data("name");

    let is_valid    = true;
    let final_value = val;

    if (el_type === "check") {
        final_value = $el.prop("checked") ? 1 : 0;
    } else if (el_type === "phone") {
        const code_length    = String($el.data("phone-code") || "").length;
        const mask_digits    = String($el.data("phone-mask") || "").replace(/[^\d]/g, "").length;
        const expected_length = mask_digits - code_length;
        final_value = val.replace(/[^\d]/g, "").substring(code_length);
        if (val.length > 0 || is_required) {
            is_valid = final_value.length === expected_length;
        }
    } else if (el_type === "email") {
        if (val.length > 0 || is_required) {
            is_valid = val.length >= 5 && val.includes('@') && val.includes('.');
        }
    } else if (el_type === "select") {
        if (is_required) is_valid = val !== "0";
    } else {
        const expected_len = $el.data("length");
        if (expected_len !== undefined) {
            if (val.length > 0 || is_required) is_valid = val.length == expected_len;
        } else if (is_required) {
            is_valid = val.length > 0;
        }
    }

    $el.toggleClass("is-invalid", !is_valid);

    return {
        good: is_valid,
        item: { name: name, value: final_value }
    };
}
//==============================================================================
function fncParamsCrt(class_selector, extra_params = []) {
    let all_good = true;
    const params_arr = [...extra_params];

    $(class_selector).each(function() {
        const $el     = $(this);
        const el_type = $el.data("type");
        const required = $el.data("required");
        const result  = fncItemCrt($el, el_type, required);

        if (result.good) {
            params_arr.push(result.item);
        } else {
            all_good = false;
        }
    });

    return { params: params_arr, all_good };
}
//==============================================================================
function fncSetupToken(token = null) {
    let $meta = $('meta[name="csrf-token"]');

    if (token) {
        if ($meta.length === 0) {
            $meta = $('<meta name="csrf-token">').appendTo('head');
        }
        $meta.attr('content', token);
    } else {
        token = $meta.attr('content');
    }

    if (token) {
        $.ajaxSetup({
            headers: { 'X-CSRF-TOKEN': token }
        });

        if (window.AppSSE) {
            window.AppSSE.close();
        }
        window.AppSSE = new EventSource('sse.php');

        window.AppSSE.onerror = function() {
            if (this.readyState == EventSource.CONNECTING) {
                console.log('Переподключение...');
            } else if (this.readyState == EventSource.CLOSED) {
                console.log('Оффлайн');
            }
        };

        window.AppSSE.onopen = function() {
            console.log('Онлайн');
        };

        window.AppSSE.addEventListener('server_time', function(e) {
            const data = JSON.parse(e.data);
            $('#spnCurrTime').text(data.time);
        });

        window.AppSSE.addEventListener('auth_status', function(e) {
            const data = JSON.parse(e.data);
            if (data.action === 'logout') {
                window.location.reload();
            }
        });

        window.listenSSE = function(eventName, callback) {
            window.AppSSE.removeEventListener(eventName, callback);
            window.AppSSE.addEventListener(eventName, callback);
        };
    }
}
//==============================================================================
function fncMyAjax(action, module, params = {}, return_data = 1) {
    const ajaxOptions = {
        type: "POST",
        url:  rqst_path.href,
        data: {
            action,
            module,
            params: JSON.stringify(params),
            return_data
        }
    };

    if (return_data === 1) {
        ajaxOptions.dataType = 'json';
    }

    return $.ajax(ajaxOptions);
}
//==============================================================================
function fncBtnReset() {
    $("#btnSave").prop("disabled", false);
    $("#btnSaveText").removeClass("d-none");
    $("#divSaveLoading").addClass("d-none");
}
//==============================================================================
function fncCheckNewItem(callback) {
    const id = localStorage.getItem("new_item");
    if (id !== null) {
        localStorage.removeItem("new_item");
        callback(id);
    }
}
//==============================================================================
function canDo(slug) {
    if (window.user_perms.includes(slug)) return true;
    let parts = slug.split('.');
    for (let i = parts.length - 1; i > 0; i--) {
        let parent = parts.slice(0, i).join('.');
        if (window.user_perms.includes(parent)) return true;
    }
    return false;
}
//==============================================================================
function fncShowFormError(msg) {
    $("#spnFormError").html(msg);
    $("#divFormError").removeClass("d-none");
}
//==============================================================================
function fncHideFormError() {
    $("#divFormError").addClass("d-none");
    $("#spnFormError").html("");
}
//==============================================================================
