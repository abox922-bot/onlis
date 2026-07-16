$(function(){

    $("#btnToggleFilters").off("click").on("click", function(){
        $("#divFiltersGroup").toggleClass("show");
    });

    window.currentStatus = "actual";

    $(".dropdown-item[data-status]").off("click").on("click", function(e){
        e.preventDefault();
        window.currentStatus = $(this).data("status");
        $("#btnStatusFilter").html($(this).text());
        listLoadFunction();
    });

    if (window.orgFilterPicker) window.orgFilterPicker.destroy();
    window.orgFilterPicker = new TomSelect("#slctOrgFilter", {
        maxOptions: null,
        plugins: ["clear_button"],
        onChange: function(value){
            listLoadFunction();
            window.orgFilterPicker.blur();
        }
    });

    if (!canDo('users.manage')) {
        $("#btnFastNew").hide();
    }

    listLoadFunction();

    if (canDo('users.manage')) {
        $("#btnFastNew").off("click").on("click", function(){
            $("#mainModalLabel").html("Новый сотрудник");
            $("#mainModalBody").html(spnr_loading);
            fncHideFormError();
            main_modal.show();
            let path = new URL("./_books_users/users_new.php", url);
            $("#mainModalBody").load(path.href, function(){

                if (window.countryPicker) window.countryPicker.destroy();
                window.countryPicker = new TomSelect("#slctCountry", {
                    maxOptions: null
                });

                if (window.phoneCountryPicker) window.phoneCountryPicker.destroy();
                window.phoneCountryPicker = new TomSelect("#slctPhoneCountry", {
                    maxOptions: null,
                    onChange: function(value){
                        if (!value) {
                            $("#inpPhone").prop("disabled", true).val("").unmask();
                            return;
                        }
                        let opt  = $(`#slctPhoneCountry option[value="${value}"]`);
                        let mask = opt.data("mask");
                        $("#inpPhone").prop("disabled", false);
                        if (mask) $("#inpPhone").mask(mask);
                    }
                });

                if (!canDo('users.manage')) {
                    $("#btnSave").hide();
                    $("#formNew").off("submit");
                    return;
                }

                $("#formNew").submit(function(e){
                    e.preventDefault();
                    e.stopImmediatePropagation();

                    let params_arr = [];
                    params_arr.push({name: "user-country-id",  value: window.countryPicker.getValue()});
                    params_arr.push({name: "phone-country-id", value: window.phoneCountryPicker.getValue()});

                    let crt_arr = fncParamsCrt(".form-inp", params_arr);
                    if (crt_arr["all_good"]) {
                        $("#btnSave").prop("disabled", true);
                        $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
                        fncMyAjax("new", "users", crt_arr["params"], 1)
                            .done(function(data){
                                if (data.sccss) {
                                    main_modal.hide();
                                    listLoadFunction();
                                } else {
                                    fncBtnReset();
                                    fncShowFormError(data.msg ?? "Проверьте введённые данные");
                                }
                            })
                            .fail(function(){ fncBtnReset(); });
                    }
                });

            });
        });
    }

});

// ─────────────────────────────────────────────────────────────────────────────

function listLoadFunction() {
    let organization_id = window.orgFilterPicker ? window.orgFilterPicker.getValue() : "";
    let status = window.currentStatus || "actual";

    $("#divChptContent").html(spnr_loading);
    let path = new URL("./_books_users/users_list.php", url);
    $("#divChptContent").load(path.href, {organization_id: organization_id, status: status}, function(){
        searchFunction();
        $(".itemTr").off("click").on("click", function(){
            infoLoadFunction(+$(this).data("id"));
        });
    });
}

// ─────────────────────────────────────────────────────────────────────────────

function infoLoadFunction(user_id) {
    let user_name = $(`.itemName[data-id="${user_id}"]`).first().text().trim();

    $("#mainModalLabel").html(user_name);
    $("#mainModalBody").html(spnr_loading);
    fncHideFormError();
    main_modal.show();
    let path = new URL("./_books_users/users_info.php", url);
    $("#mainModalBody").load(path.href, {user_id: user_id});
}
