$(function(){

    let nomenclature_id = +$("#inpProductCompositionId").val();

    fncGenerateCompositionPrices();

    if (canDo('products.manage')) {
        $("#btnAddCompositionItem").off("click").on("click", function(){
            fncOpenCompositionForm(nomenclature_id);
        });
    }

    $(".compositionItemTr").off("click").on("click", function(){
        let item_id = $(this).data("id");
        fncOpenCompositionForm(nomenclature_id, item_id);
    });

});

// ─────────────────────────────────────────────────────────────────────────────

function fncGenerateCompositionPrices() {
    let total = 0;
    $(".composition-row").each(function(){
        let $row = $(this);
        let price = Math.floor(Math.random() * (999 - 100 + 1)) + 100;
        let quantity = parseFloat($row.data("quantity")) || 0;
        let sum = price * quantity;
        total += sum;
        $row.find(".composition-row__unit-price").text(price.toFixed(2) + " ₽");
        $row.find(".composition-row__sum").text(sum.toFixed(2) + " ₽");
    });
    $("#compositionTotal").text(total.toFixed(2) + " ₽");
}

// ─────────────────────────────────────────────────────────────────────────────

function fncOpenCompositionForm(nomenclature_id, item_id) {
    let is_new = !item_id;
    $("#modalOffcanvasLabel").html(is_new ? "Новый ингредиент" : "Ингредиент");
    $("#modalOffcanvasBody").html(spnr_loading);
    modalOffcanvas.show();

    let path = is_new
        ? new URL("./_books_products/products_info_composition_new.php", url)
        : new URL("./_books_products/products_info_composition_info.php", url);
    let post_data = is_new ? {nomenclature_id: nomenclature_id} : {id: item_id};

    $("#modalOffcanvasBody").load(path.href, post_data, function(){
        let form_id = is_new ? "#formCompositionNew" : "#formCompositionInfo";
        let action = is_new ? "new_product_composition_item" : "upd_product_composition_item";

        $(form_id).off("submit").on("submit", function(e){
            e.preventDefault();
            e.stopImmediatePropagation();

            let is_critical_value = $("input[name='criticalRadio']:checked").val();
            let is_visible_value = $("input[name='visibleRadio']:checked").val();
            if (is_critical_value === undefined || is_visible_value === undefined) {
                fncShowFormError("Укажите критичность и видимость ингредиента");
                return;
            }

            let params_arr = [];
            if (is_new) {
                params_arr.push({name: "nomenclature_id", value: nomenclature_id});
            } else {
                params_arr.push({name: "id", value: item_id});
            }
            params_arr.push({name: "is_critical", value: is_critical_value});
            params_arr.push({name: "is_visible", value: is_visible_value});

            let crt_arr = fncParamsCrt(".form-inp", params_arr);
            if (crt_arr["all_good"]) {
                $("#btnSave").prop("disabled", true);
                $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
                fncMyAjax(action, "noms", crt_arr["params"], 1)
                    .done(function(data){
                        if (data.sccss) {
                            modalOffcanvas.hide();
                            fncCompositionListReload(nomenclature_id);
                        } else {
                            fncBtnReset();
                            fncShowFormError(data.msg ?? "Проверьте введённые данные");
                        }
                    })
                    .fail(function(){ fncBtnReset(); });
            }
        });

        if (!is_new) {
            $("#btnDeleteCompositionItem").off("click").on("click", async function(){
                let confirmed = await fncConfirm("Удалить ингредиент из состава?");
                if (!confirmed) return;
                $(this).prop("disabled", true);
                fncMyAjax("del_product_composition_item", "noms", [{name: "id", value: item_id}], 1)
                    .done(function(data){
                        if (data.sccss) {
                            modalOffcanvas.hide();
                            fncCompositionListReload(nomenclature_id);
                        } else {
                            fncShowFormError(data.msg ?? "Не удалось удалить ингредиент");
                            $("#btnDeleteCompositionItem").prop("disabled", false);
                        }
                    })
                    .fail(function(){ $("#btnDeleteCompositionItem").prop("disabled", false); });
            });
        }
    });
}

// ─────────────────────────────────────────────────────────────────────────────

function fncCompositionListReload(nomenclature_id) {
    fncProductTabLoad(nomenclature_id, "composition");
}
