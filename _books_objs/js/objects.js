$(function(){

    if (!canDo('objects.manage')) {
        $("#btnFastNew").hide();
    }

    listLoadFunction();

    if (canDo('objects.manage')) {
        $("#btnFastNew").on("click", function(){
            $("#mainModalBody").html(spnr_loading);
            $("#mainModalLabel").html("Добавление объекта");
            $("#mainModal").removeClass("modal-xl");
            fncHideFormError();
            main_modal.show();
            let path = new URL("./_books_objs/object_new.php", url);
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
                            fncMyAjax("new_object", "objs", crt_arr["params"], 1)
                                .done(function(data){
                                    if (data.sccss) {
                                        localStorage.setItem('new_item', data.id);
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
                                    listLoadFunction();
                                });
                        }
                    });
                }

            });
        });
    }

});

// ─────────────────────────────────────────────────────────────────────────────

function listLoadFunction() {
    $("#divChptContent").html(spnr_loading);
    let path = new URL("./_books_objs/object_list.php", url);
    $("#divChptContent").load(path.href, function(){
        searchFunction();
        $(".itemTr").off("click").on("click", function(){
            infoLoadFunction($(this).data("id"));
        });
        fncCheckNewItem(infoLoadFunction);
    });
}

// ─────────────────────────────────────────────────────────────────────────────

function infoLoadFunction(item_id) {
    let item_name = $(`.itemName[data-id="${item_id}"]`).html();
    $("#mainModalBody").html(spnr_loading);
    $("#mainModalLabel").html(item_name);
    fncHideFormError();
    main_modal.show();
    let path = new URL("./_books_objs/object_info.php", url);
    $("#mainModalBody").load(path.href, {id: item_id}, function(){

        $(".inline-tab").off("click").on("click", function(){
            $(".inline-tab").removeClass("active");
            $(this).addClass("active");
            fncObjectTabLoad(item_id, $(this).data("target"));
        });

        fncObjectTabLoad(item_id, "main");

    });
}

// ─────────────────────────────────────────────────────────────────────────────

function fncObjectTabLoad(id, target) {
    $("#divObjectInfoContent").html(spnr_loading);
    let path = new URL(`./_books_objs/object_info_${target}.php`, url);
    $("#divObjectInfoContent").load(path.href, {id});
}
