$(function(){

    let nomenclature_id = +$("#inpProductPricesId").val();

    $("#btnSaveBasePrice").off("click").on("click", function(){
        let price = $("#inpBasePrice").val();
        $(this).prop("disabled", true);
        $("#btnSaveBasePriceIcon, #spnBasePriceLoading").toggleClass("d-none");
        fncMyAjax("upd_base_price", "noms", [
            {name: "nomenclature_id", value: nomenclature_id},
            {name: "price", value: price}
        ], 1)
            .done(function(data){
                if (data.sccss) {
                    fncMarkItemEdited(nomenclature_id);
                } else {
                    fncShowFormError(data.msg ?? "Не удалось сохранить цену");
                }
            })
            .always(function(){
                $("#btnSaveBasePrice").prop("disabled", false);
                $("#btnSaveBasePriceIcon, #spnBasePriceLoading").toggleClass("d-none");
            });
    });
    
    if (canDo('products.manage')) {
        $("#btnAddPriceVariation").off("click").on("click", function(){
            $("#modalOffcanvasLabel").html("Новая цена");
            $("#modalOffcanvasBody").html(spnr_loading);
            modalOffcanvas.show();
            let path = new URL("./_books_products/products_info_prices_new.php", url);
            $("#modalOffcanvasBody").load(path.href, {nomenclature_id: nomenclature_id}, function(){

                $("input[name='variantTypeRadio']").off("change").on("change", function(){
                    let type_value = $(this).val();
                    $("#divVariantObjectType").toggleClass("d-none", type_value !== "object_type");
                    $("#divVariantObject").toggleClass("d-none", type_value !== "object");

                    $("#slctObjectType").toggleClass("form-inp", type_value === "object_type");
                    $("#slctObject").toggleClass("form-inp", type_value === "object");
                });

                $("#formPriceNew").submit(function(e){
                    e.preventDefault();
                    e.stopImmediatePropagation();

                    let variant_type = $("input[name='variantTypeRadio']:checked").val();
                    if (variant_type === undefined) {
                        fncShowFormError("Укажите, для чего задаётся цена");
                        return;
                    }

                    let params_arr = [];
                    params_arr.push({name: "nomenclature_id", value: nomenclature_id});
                    params_arr.push({name: "variant_type", value: variant_type});
                    let crt_arr = fncParamsCrt(".form-inp", params_arr);
                    if (crt_arr["all_good"]) {
                        $("#btnSave").prop("disabled", true);
                        $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
                        fncMyAjax("new_price_variation", "noms", crt_arr["params"], 1)
                            .done(function(data){
                                if (data.sccss) {
                                    modalOffcanvas.hide();
                                    fncMarkItemEdited(nomenclature_id);
                                    fncProductTabLoad(nomenclature_id, "prices");
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

    $(".priceVariationRow").off("click").on("click", function(){
        let id = $(this).data("id");
        $("#modalOffcanvasLabel").html("Цена");
        $("#modalOffcanvasBody").html(spnr_loading);
        modalOffcanvas.show();
        let path = new URL("./_books_products/products_info_prices_info.php", url);
        $("#modalOffcanvasBody").load(path.href, {id: id}, function(){
            $("#formPriceInfo").submit(function(e){
                e.preventDefault();
                e.stopImmediatePropagation();
                let params_arr = [];
                params_arr.push({name: "id", value: id});
                let crt_arr = fncParamsCrt(".form-inp", params_arr);
                if (crt_arr["all_good"]) {
                    $("#btnSave").prop("disabled", true);
                    $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
                    fncMyAjax("upd_price_variation", "noms", crt_arr["params"], 1)
                        .done(function(data){
                            if (data.sccss) {
                                modalOffcanvas.hide();
                                fncMarkItemEdited(nomenclature_id);
                                fncProductTabLoad(nomenclature_id, "prices");
                            } else {
                                fncBtnReset();
                                fncShowFormError(data.msg ?? "Проверьте введённые данные");
                            }
                        })
                        .fail(function(){ fncBtnReset(); });
                }
            });

            $("#btnDeletePriceVariation").off("click").on("click", async function(){
                let confirmed = await fncConfirm("Удалить цену?");
                if (!confirmed) return;
                $(this).prop("disabled", true);
                fncMyAjax("del_price_variation", "noms", [{name: "id", value: id}], 1)
                    .done(function(data){
                        if (data.sccss) {
                            modalOffcanvas.hide();
                            fncMarkItemEdited(nomenclature_id);
                            fncProductTabLoad(nomenclature_id, "prices");
                        } else {
                            fncShowFormError(data.msg ?? "Не удалось удалить цену");
                            $("#btnDeletePriceVariation").prop("disabled", false);
                        }
                    })
                    .fail(function(){ $("#btnDeletePriceVariation").prop("disabled", false); });
            });
        });
    });

});
