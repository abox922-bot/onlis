$(function(){

    if (!canDo('organizations.manage')) {
        $("#btnFastNew").hide();
    }

    window.countryPicker = new TomSelect("#slctCountry", {
        maxOptions: null,
        plugins: ["clear_button"],
        render: {
            item: function(data, escape) {
                return `<div class="item">${escape(data.text)}</div>`;
            }
        },
        onChange: function(value) {
            if (value) {
                $("#divEmptyHint").addClass("d-none");
                $("#btnFastNew").prop("disabled", false);
                listLoadFunction(value);
                window.countryPicker.blur();
            } else {
                $("#divChptContent").addClass("d-none").html("");
                $("#divEmptyHint").removeClass("d-none");
                $("#btnFastNew").prop("disabled", true);
            }
        }
    });

    if (canDo('organizations.manage')) {
        $("#btnFastNew").on("click", function(){
            let country_id = window.countryPicker.getValue();
            let country_name = window.countryPicker.getOption(country_id).textContent;
            $("#mainModalBody").html(spnr_loading);
            $("#mainModalLabel").html("Новая ОПФ");
            main_modal.show();
            let path = new URL("./_books_orgs/organization_types_new.php", url);
            $("#mainModalBody").load(path.href, {country_name}, function(){
                bankPersonToggle();
                $("#formNew").submit(function(e){
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    let params_arr = [];
                    params_arr.push({name: "country-id", value: country_id});
                    let crt_arr = fncParamsCrt(".form-inp", params_arr);
                    if (crt_arr["all_good"]) {
                        $("#btnSave").prop("disabled", true);
                        $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
                        fncMyAjax("new_organization_type", "orgs", crt_arr["params"], 1)
                            .done(function(data){
                                if (data.sccss) {
                                    main_modal.hide();
                                } else {
                                    fncBtnReset();
                                }
                            })
                            .fail(function(){
                                fncBtnReset();
                            })
                            .always(function(){
                                listLoadFunction(country_id);
                            });
                    }
                });
            });
        });
    }

});

// ─────────────────────────────────────────────────────────────────────────────

function listLoadFunction(country_id) {
    $("#divChptContent").removeClass("d-none").html(spnr_loading);
    let path = new URL("./_books_orgs/organization_types_list.php", url);
    $("#divChptContent").load(path.href, {country_id}, function(){
        searchFunction();
        $(".itemTr").off("click").on("click", function(){
            infoLoadFunction($(this).data("id"));
        });
    });
}

// ─────────────────────────────────────────────────────────────────────────────

function infoLoadFunction(item_id) {
    let item_name = $(`.itemName[data-id="${item_id}"]`).html();
    $("#mainModalBody").html(spnr_loading);
    $("#mainModalLabel").html(item_name);
    main_modal.show();
    let path = new URL("./_books_orgs/organization_types_info.php", url);
    $("#mainModalBody").load(path.href, {id: item_id}, function(){

        $(".inline-tab").off("click").on("click", function(){
            $(".inline-tab").removeClass("active");
            $(this).addClass("active");
            let target = $(this).data("target");
            $(".inline-tab-pane").addClass("d-none");
            $(target).removeClass("d-none");
        });

        bankPersonToggle();
        typeRequisitesListLoad(item_id);

        if (!canDo('organizations.manage')) {
            $("#btnSave").hide();
            $("#formInfo").off("submit");
        } else {
            $("#formInfo").submit(function(e){
                e.preventDefault();
                e.stopImmediatePropagation();
                let params_arr = [];
                params_arr.push({name: "item-id", value: item_id});
                let crt_arr = fncParamsCrt(".form-inp", params_arr);
                if (crt_arr["all_good"]) {
                    $("#btnSave").prop("disabled", true);
                    $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
                    fncMyAjax("upd_organization_type", "orgs", crt_arr["params"], 0)
                        .done(function(){
                            main_modal.hide();
                        })
                        .fail(function(){
                            fncBtnReset();
                        })
                        .always(function(){
                            let country_id = window.countryPicker.getValue();
                            listLoadFunction(country_id);
                        });
                }
            });
        }

        if (!canDo('organizations')) {
            $("#btnReqNew").hide();
        } else {
            $("#btnReqNew").on("click", function(){
                $("#modalOffcanvasBody").html(spnr_loading);
                $("#modalOffcanvasLabel").html("Добавление реквизита");
                modalOffcanvas.show();
                let path = new URL("./_books_orgs/organization_type_requisites_new.php", url);
                $("#modalOffcanvasBody").load(path.href, {id: item_id}, function(){

                    $("#slctRequisite").on("change", function(){
                        if (+$(this).find("option:selected").data("length-control") === 1) {
                            $("#rowExactLength").removeClass("d-none");
                            $("#inpExactLength").attr("data-required", 1);
                        } else {
                            $("#rowExactLength").addClass("d-none");
                            $("#inpExactLength").val("").attr("data-required", null);
                        }
                    });

                    $("#formReqNew").submit(function(e){
                        e.preventDefault();
                        e.stopImmediatePropagation();
                        let params_arr = [];
                        params_arr.push({name: "item-id", value: item_id});
                        let crt_arr = fncParamsCrt(".form-req-inp", params_arr);
                        if (crt_arr["all_good"]) {
                            $("#btnReqSave").prop("disabled", true);
                            $("#btnReqSaveText, #divReqSaveLoading").toggleClass("d-none");
                            fncMyAjax("new_organization_type_requisite", "orgs", crt_arr["params"], 0)
                                .always(function(){
                                    typeRequisitesListLoad(item_id);
                                    modalOffcanvas.hide();
                                });
                        }
                    });

                });
            });
        }
    });
}

// ─────────────────────────────────────────────────────────────────────────────

function typeRequisitesListLoad(organization_type_id) {
    $("#divReqsList").html(spnr_loading);
    let path = new URL("./_books_orgs/organization_type_requisites_list.php", url);
    $("#divReqsList").load(path.href, {organization_type_id}, function(){
        if (canDo('organizations')) {
            $(".itemReqTr").off("click").on("click", function(){
                if (confirm("Удалить реквизит из набора?")) {
                    fncMyAjax("del_organization_type_requisite", "orgs", [{name: "id", value: $(this).data("id")}], 0)
                        .always(function(){
                            typeRequisitesListLoad(organization_type_id);
                        });
                }
            });
        }
    });
}

// ─────────────────────────────────────────────────────────────────────────────

function bankPersonToggle() {
    $("#chckCanHaveBankAccount").off("change").on("change", function(){
        if ($(this).prop("checked")) {
            $("#chckIsIndividual").prop("checked", false);
        }
    });
    $("#chckIsIndividual").off("change").on("change", function(){
        if ($(this).prop("checked")) {
            $("#chckCanHaveBankAccount").prop("checked", false);
        }
    });
}
