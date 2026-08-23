$(function(){

    let nomenclature_id = +$("#inpProductAdditionsId").val();

    $("#btnMaxQuantityMinus").off("click").on("click", function(){
        let current = +$("#spnAdditionsMaxQuantity").data("value") || 0;
        let new_value = Math.max(0, current - 1);
        fncSaveMaxQuantity(new_value);
    });

    $("#btnMaxQuantityPlus").off("click").on("click", function(){
        let current = +$("#spnAdditionsMaxQuantity").data("value") || 0;
        fncSaveMaxQuantity(current + 1);
    });

    if (canDo('products.manage')) {
        $("#btnAddAddition").off("click").on("click", function(){
            $("#modalOffcanvasLabel").html("Новое дополнение");
            $("#modalOffcanvasBody").html(spnr_loading);
            modalOffcanvas.show();
            let path = new URL("./_books_products/products_info_additions_new.php", url);
            $("#modalOffcanvasBody").load(path.href, {nomenclature_id: nomenclature_id}, function(){
                $("#formAdditionNew").submit(function(e){
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    let params_arr = [];
                    params_arr.push({name: "nomenclature_id", value: nomenclature_id});
                    let crt_arr = fncParamsCrt(".form-inp", params_arr);
                    if (crt_arr["all_good"]) {
                        $("#btnSave").prop("disabled", true);
                        $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
                        fncMyAjax("new_addition", "noms", crt_arr["params"], 1)
                            .done(function(data){
                                if (data.sccss) {
                                    modalOffcanvas.hide();
                                    fncMarkItemEdited(nomenclature_id);
                                    fncProductTabLoad(nomenclature_id, "additions");
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

    $(".additionItemRow").off("click").on("click", function(){
        let id = $(this).data("id");
        $("#modalOffcanvasLabel").html("Дополнение");
        $("#modalOffcanvasBody").html(spnr_loading);
        modalOffcanvas.show();
        let path = new URL("./_books_products/products_info_additions_info.php", url);
        $("#modalOffcanvasBody").load(path.href, {id: id}, function(){
            $("#formAdditionInfo").submit(function(e){
                e.preventDefault();
                e.stopImmediatePropagation();
                let params_arr = [];
                params_arr.push({name: "id", value: id});
                let crt_arr = fncParamsCrt(".form-inp", params_arr);
                if (crt_arr["all_good"]) {
                    $("#btnSave").prop("disabled", true);
                    $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
                    fncMyAjax("upd_addition", "noms", crt_arr["params"], 1)
                        .done(function(data){
                            if (data.sccss) {
                                modalOffcanvas.hide();
                                fncMarkItemEdited(nomenclature_id);
                                fncProductTabLoad(nomenclature_id, "additions");
                            } else {
                                fncBtnReset();
                                fncShowFormError(data.msg ?? "Проверьте введённые данные");
                            }
                        })
                        .fail(function(){ fncBtnReset(); });
                }
            });

            $("#btnDeleteAddition").off("click").on("click", async function(){
                let confirmed = await fncConfirm("Удалить дополнение?");
                if (!confirmed) return;
                $(this).prop("disabled", true);
                fncMyAjax("del_addition", "noms", [{name: "id", value: id}], 1)
                    .done(function(data){
                        if (data.sccss) {
                            modalOffcanvas.hide();
                            fncMarkItemEdited(nomenclature_id);
                            fncProductTabLoad(nomenclature_id, "additions");
                        } else {
                            fncShowFormError(data.msg ?? "Не удалось удалить дополнение");
                            $("#btnDeleteAddition").prop("disabled", false);
                        }
                    })
                    .fail(function(){ $("#btnDeleteAddition").prop("disabled", false); });
            });
        });
    });

});

// ─────────────────────────────────────────────────────────────────────────────

function fncSaveMaxQuantity(new_value) {
    let nomenclature_id = +$("#inpProductAdditionsId").val();
    $("#spnMaxQuantityLoading").removeClass("d-none");
    fncMyAjax("upd_additions_max_quantity", "noms", [
        {name: "nomenclature_id", value: nomenclature_id},
        {name: "max_quantity", value: new_value}
    ], 1)
        .done(function(data){
            if (data.sccss) {
                fncMarkItemEdited(nomenclature_id);
                $("#spnAdditionsMaxQuantity").data("value", new_value).text(new_value > 0 ? new_value : "∞");
            }
        })
        .always(function(){
            $("#spnMaxQuantityLoading").addClass("d-none");
        });
}
