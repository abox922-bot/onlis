//==============================================================================
const main_modal      = new bootstrap.Modal('#mainModal');
const confirm_modal   = new bootstrap.Modal('#confirmModal');
const myOffcanvas     = new bootstrap.Offcanvas('#myOffcanvas');
const modalOffcanvas  = new bootstrap.Offcanvas('#modalOffcanvas');
//==============================================================================
$(function(){
    $('body, html').animate({scrollTop: 0}, 100, "linear");
    const $startItem = $(".link-item[data-onload=1]");
    if ($startItem.length) {
        fncChptLoad($startItem.data("module"), $startItem.data("ttl"));
    }
    //~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    $("#modalOffcanvas").on("shown.bs.offcanvas", function(){
        main_modal._config.keyboard = false;
        main_modal._config.backdrop = false;
        modalOffcanvas._config.keyboard = true;
        modalOffcanvas._config.backdrop = true;
        $("#modalOffcanvas").trigger("focus");
        $("#mainModal").addClass("modal-static-silent");
    });

    $("#modalOffcanvas").on("hidden.bs.offcanvas", function(){
        main_modal._config.keyboard = true;
        main_modal._config.backdrop = true;
        $("#mainModal").trigger("focus");
        $("#mainModal").removeClass("modal-static-silent");
    });
    //~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    $("#mainModal").on("shown.bs.modal", function(){
        $("html").css("overflow-y", "hidden");
    });
    //~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    $("#mainModal").on("hidden.bs.modal", function(){
        main_modal._config.backdrop = true;
        main_modal._config.keyboard = true;
        $("html").css("overflow-y", "auto");
        $("#mainModal").addClass("modal-xl");
        $(".modal-dialog").addClass("modal-dialog-centered");
        $("#mainModalBody").html("");
    });
    //~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    $(".my-menu-div-btn").click(function(){
        $(".my-nav-item_second_level").addClass("d-none");
        myOffcanvas.show();
    });
    //~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    $(".head-item").on("click", function(){
        const target = $(this).data("target");
        $(`.my-nav-item_second_level[data-target!="${target}"]`).addClass("d-none");
        $(`.my-nav-item_second_level[data-target="${target}"]`).toggleClass("d-none");
    });
    //~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    $(".link-item").on("click", function(){
        myOffcanvas.hide();
        const $item = $(this);
        fncChptLoad($item.data("module"), $item.data("ttl"));
    });
    //~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    $("#spnQuit").on("click", async function(){
        myOffcanvas.hide();
        let confirmed = await fncConfirm("Выйти из системы?");
        if (confirmed) {
            fncMyAjax("close_ses", "main")
                .always(function() {
                    window.location.reload();
                });
        }
    });
    //~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    $(document).on('show.bs.modal', '.modal', function () {
        let z = 1050 + (10 * $('.modal.show').length);
        $(this).css('z-index', z);
        setTimeout(function () {
            $('.modal-backdrop').not('.modal-stacked').last().css('z-index', z - 1).addClass('modal-stacked');
        });
    });
    //~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    $("#menuButtonProfile").click(function(){
        myOffcanvas.hide();
        $("#mainModalBody").html(spnr_loading);
        $("#mainModalLabel").html("Профиль");
        main_modal.show();
        let path = new URL("main_profile.php", url);
        $("#mainModalBody").load(path.href, function() {
            //+++++++++++++++++++++++++++++++++++
            $("#inpPhone").mask($("#inpPhone").data("phone-mask"));
            //+++++++++++++++++++++++++++++++++++
            $("#btnNewLogin").off("click").on("click", function(){
                $("#inpLogin").val(getRandomCode(10000, 99999));
            });
            //+++++++++++++++++++++++++++++++++++
            $("#btnNewPin").off("click").on("click", function(){
                $("#inpPin").val(getRandomCode(1000, 9999));
            });
            //+++++++++++++++++++++++++++++++++++
            $("#formInfo").off("submit").on("submit", function(e){
                e.preventDefault();
                e.stopImmediatePropagation();
                let params_arr = [];
                params_arr.push({name: "item-id", value: $("#formInfo").data("id")});
                params_arr.push({name: "actual",  value: $(".btnItemActual:checked").data("target")});
                let crt_arr = fncParamsCrt(".form-inp", params_arr);
                if (crt_arr["all_good"]) {
                    $("#btnSave").prop("disabled", true);
                    $("#btnText, #divSaveLoading").toggleClass("d-none");
                    fncMyAjax("upd", "users", crt_arr["params"])
                        .done(function(data) {
                            if (data.sccss) {
                                main_modal.hide();
                            } else {
                                $("#btnText, #divSaveLoading").toggleClass("d-none");
                                $("#btnSave").prop("disabled", false);
                            }
                        })
                        .fail(function() {
                            $("#btnText, #divSaveLoading").toggleClass("d-none");
                            $("#btnSave").prop("disabled", false);
                        });
                }
            });
            //+++++++++++++++++++++++++++++++++++
        });
    });
});
//==============================================================================
function fncStartFnc() {
    $("#btnActSlct").html($(".liActListName:first").text());
    $("#btnActSlct").val($(".liActListName:first").data("val"));
    //++++++++++++++++++++++++++++++++++++
    $(".liAct").click(function(){
        let actl = $(this).data("val");
        $("#btnActSlct").html($(`.liActListName[data-val="${actl}"]`).text());
        $(".itemTr").addClass("d-none");
        $(`.itemTr[data-actual="${actl}"]`).removeClass("d-none");
        $("#btnActSlct").val(actl);
    });
    //++++++++++++++++++++++++++++++++++++
}
//==============================================================================
function fncBookNav() {
    const $shell = $("#divModuleShell");
    const folder = $shell.data("folder");
    const $tabs  = $(".module-tab");

    if ($tabs.length > 1) {
        const target = $(".module-tab.active").data("target");
        $("#rowContent").html(`<div class="col-12 p-3">${spnr_loading}</div>`);
        let path = new URL(`./${folder}/${target}.php`, url);
        $("#rowContent").load(path.href);
        //++++++++++++++++++++++++++++++++++++
        $tabs.click(function() {
            $tabs.removeClass("active");
            $(this).addClass("active");
            this.scrollIntoView({ behavior: "smooth", block: "nearest", inline: "center" });
            $('body, html').animate({scrollTop: 0}, 100, "linear");
            let chpt = $(this).data("target");
            $("#rowContent").html(`<div class="col-12 p-3">${spnr_loading}</div>`);
            let path = new URL(`./${folder}/${chpt}.php`, url);
            $("#rowContent").load(path.href);
        });
        //++++++++++++++++++++++++++++++++++++
    } else {
        const default_file = $shell.data("default");
        $("#rowContent").html(`<div class="col-12 p-3">${spnr_loading}</div>`);
        let path = new URL(`./${folder}/${default_file}.php`, url);
        $("#rowContent").load(path.href);
    }
}
//==============================================================================
function fncChptLoad(module_key, chpt_header) {
    $("#divMainContent").html(`<div class="col-12">${spnr_loading}</div>`);
    $("#sectionHeader").html(chpt_header);
    let path = new URL(`main_module.php?module=${module_key}`, url);
    $("#divMainContent").load(path.href, function(){
        $('body, html').animate({scrollTop: 0}, 100, "linear");
        fncBookNav();
    });
}
//==============================================================================
function searchFunction() {
    $("#inpSearchVal").keyup(function() {
        let val = $(this).val();
        if (val.length > 0) {
            $(".itemTr").addClass("d-none");
            val = val.toLowerCase();
            $(".itemTr").each(function() {
                let item_id = +$(this).data("id");
                if ($(`.itemName[data-id=${item_id}]`).html().toLowerCase().includes(val)) {
                    $(this).removeClass("d-none");
                }
            });
        } else {
            $(".itemTr").removeClass("d-none");
        }
    });
}
//==============================================================================
function getRandomCode(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
}
//==============================================================================
function fncConfirm(message) {
    return new Promise(function(resolve){
        let confirmed = false;
        $("#confirmModalText").html(message);

        if ($("#modalOffcanvas").hasClass("show")) {
            modalOffcanvas._config.keyboard = false;
            modalOffcanvas._config.backdrop = false;
        } else {
            main_modal._config.keyboard = false;
            main_modal._config.backdrop = false;
        }

        $("#btnConfirmOk").off("click").on("click", function(){
            confirmed = true;
            confirm_modal.hide();
        });

        $("#confirmModal").off("shown.bs.modal").one("shown.bs.modal", function(){
            $("#confirmModal").trigger("focus");
        });

        $("#confirmModal").off("hidden.bs.modal").one("hidden.bs.modal", function(){
            if ($("#modalOffcanvas").hasClass("show")) {
                modalOffcanvas._config.keyboard = true;
                modalOffcanvas._config.backdrop = true;
                $("#modalOffcanvas").trigger("focus");
            } else {
                main_modal._config.keyboard = true;
                main_modal._config.backdrop = true;
                $("#mainModal").trigger("focus");
            }
            resolve(confirmed);
        });

        confirm_modal.show();
    });
}
//==============================================================================
function fncInitStaffMainForm(st_id, reloadCallback) {
    let phone_mask = $("#inpWPhone").data("phone-mask");
    if (phone_mask) $("#inpWPhone").mask(phone_mask);

    if (!canDo('organizations.manage')) {
        $("#btnSave, #btnDismiss").hide();
        return;
    }

    $("#formStaffMain").submit(function(e){
        e.preventDefault();
        e.stopImmediatePropagation();
        let params_arr = [];
        params_arr.push({name: "st-id", value: st_id});
        let crt_arr = fncParamsCrt(".form-inp", params_arr);
        if (crt_arr["all_good"]) {
            $("#btnSave").prop("disabled", true);
            $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
            fncMyAjax("upd_organization_staff_main", "orgs", crt_arr["params"], 0)
                .done(function(){
                    reloadCallback();
                    fncBtnReset();
                })
                .fail(function(){ fncBtnReset(); });
        }
    });

    $("#btnDismiss").on("click", async function(){
        let confirmed = await fncConfirm("Уволить сотрудника?");
        if (confirmed) {
            fncMyAjax("dismiss_organization_staff", "orgs", [
                {name: "st-id", value: st_id}
            ], 0)
            .always(function(){
                reloadCallback();
                modalOffcanvas.hide();
            });
        }
    });
}
//==============================================================================
