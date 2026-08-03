$(function(){

    if (!canDo('units.manage')) {
        $("#btnFastNew").hide();
    }

    listLoadFunction();

    if (canDo('units.manage')) {
        $("#btnFastNew").on("click", function(){
            $("#mainModalLabel").html("Новая единица измерения");
            $("#mainModalBody").html(spnr_loading);
            $("#mainModal").removeClass("modal-xl");
            fncHideFormError();
            main_modal.show();
            let path = new URL("./_books_units/units_new.php", url);
            $("#mainModalBody").load(path.href, function(){
                $("#formNew").submit(function(e){
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    let params_arr = [];
                    let crt_arr = fncParamsCrt(".form-inp", params_arr);
                    if (crt_arr["all_good"]) {
                        $("#btnSave").prop("disabled", true);
                        $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
                        fncMyAjax("new_unit", "unt", crt_arr["params"], 1)
                            .done(function(data){
                                if (data.sccss) {
                                    main_modal.hide();
                                } else {
                                    fncBtnReset();
                                    fncShowFormError(data.msg ?? "Проверьте введённые данные");
                                }
                            })
                            .fail(function(){ fncBtnReset(); })
                            .always(function(){ listLoadFunction(); });
                    }
                });
            });
        });
    }

});

// ─────────────────────────────────────────────────────────────────────────────

function listLoadFunction() {
    $("#divChptContent").html(spnr_loading);
    let path = new URL("./_books_units/units_list.php", url);
    $("#divChptContent").load(path.href, function(){
        searchFunction();
        $(".itemTr").off("click").on("click", function(){
            infoLoadFunction($(this).data("id"));
        });
    });
}

// ─────────────────────────────────────────────────────────────────────────────

function infoLoadFunction(item_id) {
    let item_name = $(`.itemName[data-id="${item_id}"]`).first().text().trim();
    $("#mainModalBody").html(spnr_loading);
    $("#mainModalLabel").html(item_name);
    $("#mainModal").removeClass("modal-xl");
    fncHideFormError();
    main_modal.show();
    let path = new URL("./_books_units/units_info.php", url);
    $("#mainModalBody").load(path.href, {id: item_id}, function(){
        if (!canDo('units.manage')) {
            $("#btnSave").hide();
            $("#formInfo").off("submit");
        } else {
            $("#formInfo").submit(function(e){
                e.preventDefault();
                e.stopImmediatePropagation();
                let params_arr = [];
                params_arr.push({name: "id", value: item_id});
                let crt_arr = fncParamsCrt(".form-inp", params_arr);
                if (crt_arr["all_good"]) {
                    $("#btnSave").prop("disabled", true);
                    $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
                    fncMyAjax("upd_unit", "unt", crt_arr["params"], 1)
                        .done(function(data){
                            if (!data.sccss) {
                                fncShowFormError(data.msg ?? "Проверьте введённые данные");
                                fncBtnReset();
                            } else {
                              listLoadFunction();
                              main_modal.hide();
                            }
                        })
                        .fail(function(){ fncBtnReset(); })
                }
            });
        }
    });
}
