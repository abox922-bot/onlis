$(function(){

    let org_type = $("#scrOrgs").data("type");

    if (!canDo('organizations.manage')) {
        $("#btnFastNew").hide();
    }

    listLoadFunction(org_type);

    if (canDo('organizations.manage')) {
        $("#btnFastNew").on("click", function(){
            $("#mainModalBody").html(spnr_loading);
            $("#mainModalLabel").html("Новая организация");
            fncHideFormError();
            main_modal.show();
            let path = new URL("./_books_orgs/organization_new.php", url);
            $("#mainModalBody").load(path.href, {org_type}, function(){
                let types_arr = [];

                // Tom Select для выбора страны
                if (window.orgCountryPicker) window.orgCountryPicker.destroy();
                window.orgCountryPicker = new TomSelect("#slctCountry", {
                    maxOptions: null,
                    plugins: ["clear_button"],
                    onChange: function(value) {
                        $("#slctType option[value!='0']").remove();
                        if (value) {
                            types_arr.forEach(function(item){
                                if (item.country === +value) {
                                    $("#slctType").append(
                                        `<option value="${item.id}" data-abbr="${item.abbr}">${item.name}</option>`
                                    );
                                }
                            });
                            $("#slctType").prop("disabled", false);
                        } else {
                            $("#slctType").prop("disabled", true);
                            $("#divNewOrgName").addClass("d-none");
                            $("#spnOPF").html("");
                            $("#divOrgReqs").html("");
                            $("#divBtnSave").addClass("d-none");
                        }
                    }
                });

                // Собираем все ОПФ из select до очистки — фильтрация по стране на фронте
                $("#slctType option[value!='0']").each(function(){
                    types_arr.push({
                        id:      +$(this).val(),
                        country: +$(this).data("country"),
                        abbr:    $(this).data("abbr"),
                        name:    $(this).html()
                    });
                    $(this).remove();
                });
                $("#slctType").prop("disabled", true);

                // ОПФ → подгружаем поля реквизитов
                $("#slctType").on("change", function(){
                    let type_id = +$(this).val();
                    if (type_id > 0) {
                        $("#spnOPF").html($(this).find("option:selected").data("abbr"));
                        $("#divNewOrgName").removeClass("d-none");
                        $("#divOrgReqs").html(`<div class="col-12">${spnr_loading}</div>`);
                        let path = new URL("./_books_orgs/organization_new_requisites.php", url);
                        $("#divOrgReqs").load(path.href, {type_id, org_type}, function(){
                          $("#divBtnSave").removeClass("d-none");
                        });
                    } else {
                        $("#divNewOrgName").addClass("d-none");
                        $("#spnOPF").html("");
                        $("#divOrgReqs").html("");
                        $("#divBtnSave").addClass("d-none");
                    }
                });

                // Сабмит формы
                if (!canDo('organizations.manage')) {
                    $("#btnSave").hide();
                    $("#formNew").off("submit");
                } else {
                    $("#formNew").submit(function(e){
                        e.preventDefault();
                        e.stopImmediatePropagation();

                        // Валидация реквизитов
                        let all_good = true;
                        let reqs_arr = [];

                        $(".req-inp").each(function(){
                            let $el      = $(this);
                            let length   = $el.data("length") ? +$el.data("length") : 0;
                            let is_req   = $el.data("required") ? true : false;
                            let req_id   = $el.data("req-id");
                            let uniq     = $el.data("uniq");

                            fncNormalizeInp("#" + $el.attr("id"), $el.data("type"));
                            let val = $el.val().trim();

                            let valid = true;
                            if (length > 0 && val.length !== length) valid = false;
                            if (is_req && val.length === 0) valid = false;

                            if (!valid) {
                                $el.addClass("is-invalid");
                                all_good = false;
                            } else {
                                $el.removeClass("is-invalid");
                                reqs_arr.push({id: req_id, value: val, uniq});
                            }
                        });

                        let params_arr = [];
                        params_arr.push({name: "reqs-list", value: reqs_arr});
                        params_arr.push({name: "org-type",  value: org_type});
                        params_arr.push({name: "org-country-id", value: window.orgCountryPicker.getValue()});
                        let crt_arr = fncParamsCrt(".form-inp", params_arr);

                        if (all_good && crt_arr["all_good"]) {
                            $("#btnSave").prop("disabled", true);
                            $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
                            fncMyAjax("new_organization", "orgs", crt_arr["params"], 1)
                                .done(function(data){
                                    if (data.sccss) {
                                        main_modal.hide();
                                    } else {
                                        fncBtnReset();
                                        fncShowFormError(data.msg ?? "Проверьте введённые данные");
                                    }
                                })
                                .fail(function(){
                                    fncBtnReset();
                                })
                                .always(function(){
                                    listLoadFunction(org_type);
                                });
                        }
                    });
                }

            });
        });
    }

});

// ─────────────────────────────────────────────────────────────────────────────

function listLoadFunction(org_type) {
    $("#divChptContent").html(spnr_loading);
    let path = new URL("./_books_orgs/organizations_list.php", url);
    $("#divChptContent").load(path.href, {org_type}, function(){
        searchFunction();
        $(".itemTr").off("click").on("click", function(){
            infoLoadFunction($(this).data("id"), org_type);
        });
    });
}

// ─────────────────────────────────────────────────────────────────────────────

function infoLoadFunction(item_id, org_type) {
    let item_name = $(`.itemName[data-id="${item_id}"]`).html();
    $("#mainModalBody").html(spnr_loading);
    $("#mainModalLabel").html(item_name);
    fncHideFormError();
    main_modal.show();
    let path = new URL("./_books_orgs/organization_info.php", url);
    $("#mainModalBody").load(path.href, {id: item_id, org_type}, function(){

        $(".inline-tab").off("click").on("click", function(){
            $(".inline-tab").removeClass("active");
            $(this).addClass("active");
            fncOrgTabLoad(item_id, org_type, $(this).data("target"));
        });

        fncOrgTabLoad(item_id, org_type, "main");

    });
}

// ─────────────────────────────────────────────────────────────────────────────

function fncOrgTabLoad(id, org_type, target) {
    $(".inline-tab").prop("disabled", true);
    $("#divOrgInfoContent").html(spnr_loading);
    let path = new URL(`./_books_orgs/organization_info_${target}.php`, url);
    $("#divOrgInfoContent").load(path.href, {id, org_type}, function(){
      $(".inline-tab").prop("disabled", false);
    });
}
