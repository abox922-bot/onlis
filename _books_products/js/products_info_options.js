$(function(){

    let nomenclature_id = +$("#inpOptionsNomenclatureId").val();

    if (window.canDo && canDo('products.manage')) {
        $("#btnAddRootOption").off("click").on("click", function(){
            fncOpenOptionForm(nomenclature_id, 0);
        });

        $(".btn-add-option-child").off("click").on("click", function(e){
            e.stopPropagation();
            let parent_id = $(this).data("parent-id");
            fncOpenOptionForm(nomenclature_id, parent_id);
        });
    }

    $(".tree-toggle").off("click").on("click", function(e){
        e.stopPropagation();
        fncToggleOptionNode($(this));
    });

    $(".itemOptionName").off("click").on("click", function(){
        let product_id = $(this).data("id");
        infoLoadFunction(product_id, "general");
    });

    $(".optionGroupName").off("click").on("click", function(){
        let option_id = $(this).data("id");
        fncOpenOptionInfo(option_id, nomenclature_id);
    });

});

// ─────────────────────────────────────────────────────────────────────────────

function fncToggleOptionNode($toggle) {
    $toggle.toggleClass("bi-chevron-right bi-chevron-down");
    $toggle.closest(".tree-node").children(".tree-children").toggleClass("d-none");
}

// ─────────────────────────────────────────────────────────────────────────────

function fncOpenOptionForm(nomenclature_id, parent_id) {
    $("#modalOffcanvasLabel").html("Новая опция");
    $("#modalOffcanvasBody").html(spnr_loading);
    modalOffcanvas.show();
    let path = new URL("./_books_products/products_info_options_new.php", url);
    $("#modalOffcanvasBody").load(path.href, {nomenclature_id: nomenclature_id, parent_id: parent_id}, function(){
        $("#formOptionNew").submit(function(e){
            e.preventDefault();
            e.stopImmediatePropagation();
            let params_arr = [];
            params_arr.push({name: "nomenclature_id", value: nomenclature_id});
            params_arr.push({name: "parent_id", value: parent_id});
            let crt_arr = fncParamsCrt(".form-inp", params_arr);
            if (crt_arr["all_good"]) {
                $("#btnSave").prop("disabled", true);
                $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
                fncMyAjax("new_option", "noms", crt_arr["params"], 1)
                    .done(function(data){
                        if (data.sccss) {
                            modalOffcanvas.hide();
                            fncOptionsListReload(nomenclature_id);
                        } else {
                            fncBtnReset();
                            fncShowFormError(data.msg ?? "Проверьте введённые данные");
                        }
                    })
                    .fail(function(){ fncBtnReset(); });
            }
        });
    });
}

// ─────────────────────────────────────────────────────────────────────────────

function fncOpenOptionInfo(option_id, nomenclature_id) {
    $("#modalOffcanvasLabel").html("Опция");
    $("#modalOffcanvasBody").html(spnr_loading);
    modalOffcanvas.show();
    let path = new URL("./_books_products/products_info_options_info.php", url);
    $("#modalOffcanvasBody").load(path.href, {id: option_id}, function(){
        $("#formOptionInfo").submit(function(e){
            e.preventDefault();
            e.stopImmediatePropagation();
            let params_arr = [];
            params_arr.push({name: "id", value: option_id});
            let crt_arr = fncParamsCrt(".form-inp", params_arr);
            if (crt_arr["all_good"]) {
                $("#btnSave").prop("disabled", true);
                $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
                fncMyAjax("upd_option", "noms", crt_arr["params"], 1)
                    .done(function(data){
                        if (data.sccss) {
                            modalOffcanvas.hide();
                            fncOptionsListReload(nomenclature_id);
                        } else {
                            fncBtnReset();
                            fncShowFormError(data.msg ?? "Проверьте введённые данные");
                        }
                    })
                    .fail(function(){ fncBtnReset(); });
            }
        });

        $("#btnDeleteOption").off("click").on("click", async function(){
            let confirmed = await fncConfirm("Удалить опцию?");
            if (!confirmed) return;
            $(this).prop("disabled", true);
            fncMyAjax("del_option", "noms", [{name: "id", value: option_id}], 1)
                .done(function(data){
                    if (data.sccss) {
                        modalOffcanvas.hide();
                        fncOptionsListReload(nomenclature_id);
                    } else {
                        fncShowFormError(data.msg ?? "Не удалось удалить опцию");
                        $("#btnDeleteOption").prop("disabled", false);
                    }
                })
                .fail(function(){ $("#btnDeleteOption").prop("disabled", false); });
        });
    });
}

// ─────────────────────────────────────────────────────────────────────────────

function fncOptionsListReload(nomenclature_id) {
    fncProductTabLoad(nomenclature_id, "options");
}
