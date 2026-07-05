//==============================================================================
$(function(){
    if (!canDo('objects.manage')) {
        $("#btnFastNew").hide();
    }
    //~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    if (canDo('objects.manage')) {
        $("#btnFastNew").click(function(){
            $("#mainModalBody").html(spnr_loading);
            $("#mainModalLabel").html("Добавление типа объектов");
            fncHideFormError();
            main_modal.show();
            let path = new URL("./_books_objs/object_types_new.php", url);
            $("#mainModalBody").load(path.href, function(){
                if (!canDo('objects.manage')) {
                    $("#btnSave").hide();
                    $("#formNew").off("submit");
                } else {
                    $("#formNew").submit(function(e){
                        e.preventDefault();
                        e.stopImmediatePropagation();
                        let params_arr = [];
                        let crt_arr = fncParamsCrt(".form-inp", params_arr);
                        if (crt_arr["all_good"]) {
                            $("#btnSave").prop("disabled", true);
                            $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
                            fncMyAjax("new_object_type", "objs", crt_arr["params"], 1)
                                .done(function(data){
                                    if (data.sccss) {
                                        localStorage.setItem('new_item', data.id);
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
                }
            });
        });
    }
    //~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    listLoadFunction();
});
//==============================================================================
function listLoadFunction() {
    $("#divChptContent").html(spnr_loading);
    let path = new URL("./_books_objs/object_types_list.php", url);
    $("#divChptContent").load(path.href, function(){
        searchFunction();
        $(".itemTr").click(function(){
            infoLoadFunction($(this).data("id"));
        });
        fncCheckNewItem(infoLoadFunction);
    });
}
//==============================================================================
function infoLoadFunction(item_id) {
    let item_name = $(`.itemName[data-id="${item_id}"]`).html();
    $("#mainModalBody").html(spnr_loading);
    $("#mainModalLabel").html(item_name);
    fncHideFormError();
    main_modal.show();
    let path = new URL("./_books_objs/object_types_info.php", url);
    $("#mainModalBody").load(path.href, {id: item_id}, function(){
        if (!canDo('objects.manage')) {
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
                    fncMyAjax("upd_object_type", "objs", crt_arr["params"], 0)
                        .done(function(){ main_modal.hide(); })
                        .fail(function(){ fncBtnReset(); })
                        .always(function(){ listLoadFunction(); });
                }
            });
        }
    });
}
//==============================================================================
