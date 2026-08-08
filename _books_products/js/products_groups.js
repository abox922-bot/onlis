window.currentGroupsStatus = 'active';

$(function(){

    if (!canDo('products.manage')) {
        $("#btnFastNew").hide();
    }

    listLoadFunction();

    $(".dropdown-item[data-status]").off("click").on("click", function(e){
        e.preventDefault();
        window.currentGroupsStatus = $(this).data("status");
        $("#btnStatusFilter").html($(this).text());
        listLoadFunction();
    });

    if (canDo('products.manage')) {
        $("#btnFastNew").on("click", function(){
            $("#mainModalLabel").html("Новая группа");
            $("#mainModalBody").html(spnr_loading);
            $("#mainModal").removeClass("modal-xl");
            fncHideFormError();
            main_modal.show();
            let path = new URL("./_books_products/products_groups_new.php", url);
            $("#mainModalBody").load(path.href, function(){
                $("#formNew").submit(function(e){
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    let params_arr = [];
                    params_arr.push({name: "type", value: "product"});
                    let crt_arr = fncParamsCrt(".form-inp", params_arr);
                    if (crt_arr["all_good"]) {
                        $("#btnSave").prop("disabled", true);
                        $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
                        fncMyAjax("new_group", "noms", crt_arr["params"], 1)
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
    let path = new URL("./_books_products/products_groups_list.php", url);
    $("#divChptContent").load(path.href, {status: window.currentGroupsStatus}, function(){
        $(".tree-toggle").off("click").on("click", function(e){
            e.stopPropagation();
            $(this).toggleClass("bi-chevron-right bi-chevron-down");
            $(this).closest(".tree-node").children(".tree-children").toggleClass("d-none");
        });
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
    let path = new URL("./_books_products/products_groups_info.php", url);
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
                    fncMyAjax("upd_group", "noms", crt_arr["params"], 1)
                        .done(function(data){
                            if (!data.sccss) {
                                fncShowFormError(data.msg ?? "Проверьте введённые данные");
                                fncBtnReset();
                            } else {
                                listLoadFunction();
                                main_modal.hide();
                            }
                        })
                        .fail(function(){ fncBtnReset(); });
                }
            });

            $("#btnArchive").off("click").on("click", async function(){
                let confirmed = await fncConfirm("Архивировать группу и все её подгруппы?");
                if (!confirmed) return;
                $(this).prop("disabled", true);
                fncMyAjax("archive_group", "noms", [{name: "id", value: item_id}], 1)
                    .done(function(data){
                        if (data.sccss) {
                            main_modal.hide();
                            listLoadFunction();
                        } else {
                            fncShowFormError(data.msg ?? "Не удалось архивировать группу");
                            $("#btnArchive").prop("disabled", false);
                        }
                    })
                    .fail(function(){ $("#btnArchive").prop("disabled", false); });
            });

            $("#btnRestore").off("click").on("click", async function(){
                let confirmed = await fncConfirm("Восстановить группу вместе с вышестоящими архивными родителями?");
                if (!confirmed) return;
                $(this).prop("disabled", true);
                fncMyAjax("restore_group", "noms", [{name: "id", value: item_id}], 1)
                    .done(function(data){
                        if (data.sccss) {
                            main_modal.hide();
                            listLoadFunction();
                        } else {
                            fncShowFormError(data.msg ?? "Не удалось восстановить группу");
                            $("#btnRestore").prop("disabled", false);
                        }
                    })
                    .fail(function(){ $("#btnRestore").prop("disabled", false); });
            });
        }
    });
}
