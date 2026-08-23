$(function(){

    window.currentStatus = "active";

    $(".dropdown-item[data-status]").off("click").on("click", function(e){
        e.preventDefault();
        window.currentStatus = $(this).data("status");
        $("#btnStatusFilter").html($(this).text());
        listLoadFunction();
    });

    if (!canDo('products.manage')) {
        $("#btnFastNew").hide();
    }

    listLoadFunction();

    if (canDo('products.manage')) {
        $("#btnFastNew").off("click").on("click", function(){
            $("#mainModalLabel").html("Новое пожелание");
            $("#mainModalBody").html(spnr_loading);
            $("#mainModal").removeClass("modal-xl");
            fncHideFormError();
            main_modal.show();
            let path = new URL("./_books_products/wishes_new.php", url);
            $("#mainModalBody").load(path.href, function(){
                $("#formNew").submit(function(e){
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    let crt_arr = fncParamsCrt(".form-inp", []);
                    if (crt_arr["all_good"]) {
                        $("#btnSave").prop("disabled", true);
                        $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
                        fncMyAjax("new_wish", "noms", crt_arr["params"], 1)
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
    let status = window.currentStatus || "active";

    $("#divChptContent").html(spnr_loading);
    let path = new URL("./_books_products/wishes_list.php", url);
    $("#divChptContent").load(path.href, {status: status}, function(){
        searchFunction();
        $(".itemTr").off("click").on("click", function(){
            infoLoadFunction(+$(this).data("id"));
        });
        fncCheckNewItem(infoLoadFunction);
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
    let path = new URL("./_books_products/wishes_info.php", url);
    $("#mainModalBody").load(path.href, {id: item_id}, function(){
        if (!canDo('products.manage')) {
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
                    fncMyAjax("upd_wish", "noms", crt_arr["params"], 1)
                        .done(function(data){
                            if (data.sccss) {
                                fncMarkItemEdited(item_id);
                                main_modal.hide();
                            } else {
                                fncShowFormError(data.msg ?? "Проверьте введённые данные");
                                fncBtnReset();
                            }
                        })
                        .fail(function(){ fncBtnReset(); });
                }
            });

            $("#btnArchive").off("click").on("click", async function(){
                let confirmed = await fncConfirm("Архивировать пожелание?");
                if (!confirmed) return;
                $(this).prop("disabled", true);
                fncMyAjax("archive_wish", "noms", [{name: "id", value: item_id}], 1)
                    .done(function(data){
                        if (data.sccss) {
                            fncMarkItemEdited(item_id);
                            main_modal.hide();
                        } else {
                            fncShowFormError(data.msg ?? "Не удалось архивировать пожелание");
                            $("#btnArchive").prop("disabled", false);
                        }
                    })
                    .fail(function(){ $("#btnArchive").prop("disabled", false); });
            });

            $("#btnRestore").off("click").on("click", async function(){
                let confirmed = await fncConfirm("Восстановить пожелание?");
                if (!confirmed) return;
                $(this).prop("disabled", true);
                fncMyAjax("restore_wish", "noms", [{name: "id", value: item_id}], 1)
                    .done(function(data){
                        if (data.sccss) {
                            fncMarkItemEdited(item_id);
                            main_modal.hide();
                        } else {
                            fncShowFormError(data.msg ?? "Не удалось восстановить пожелание");
                            $("#btnRestore").prop("disabled", false);
                        }
                    })
                    .fail(function(){ $("#btnRestore").prop("disabled", false); });
            });
        }
    });
}
