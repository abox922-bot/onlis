$(function(){

    if (!canDo('nomenclature.manage')) {
        $("#btnSave, #btnArchive, #btnRestore").hide();
        $("#formInfo").off("submit");
        return;
    }

    $("#formInfo").off("submit").on("submit", function(e){
        e.preventDefault();
        e.stopImmediatePropagation();

        if ($("input[name='foodProductRadio']").length > 0) {
            let food_product_value = $("input[name='foodProductRadio']:checked").val();
            if (food_product_value === undefined) {
                fncShowFormError("Укажите, является ли позиция пищевой продукцией");
                return;
            }
        }

        let item_id = +$("#inpNomenclatureId").val();
        let params_arr = [];
        params_arr.push({name: "id", value: item_id});
        if ($("input[name='foodProductRadio']").length > 0) {
            params_arr.push({name: "is_food_product", value: food_product_value});
        }
        let crt_arr = fncParamsCrt(".form-inp", params_arr);
        if (crt_arr["all_good"]) {
            $("#btnSave").prop("disabled", true);
            $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
            fncMyAjax("upd_nomenclature", "noms", crt_arr["params"], 1)
                .done(function(data){
                    if (data.sccss) {
                        //listLoadFunction();
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
        let confirmed = await fncConfirm("Архивировать позицию?");
        if (!confirmed) return;
        let item_id = +$("#inpNomenclatureId").val();
        $(this).prop("disabled", true);
        fncMyAjax("archive_nomenclature", "noms", [{name: "id", value: item_id}], 1)
            .done(function(data){
                if (data.sccss) {
                    main_modal.hide();
                    //listLoadFunction();
                    fncMarkItemEdited(item_id);
                } else {
                    fncShowFormError(data.msg ?? "Не удалось архивировать позицию");
                    $("#btnArchive").prop("disabled", false);
                }
            })
            .fail(function(){ $("#btnArchive").prop("disabled", false); });
    });

    $("#btnRestore").off("click").on("click", async function(){
        let confirmed = await fncConfirm("Восстановить позицию?");
        if (!confirmed) return;
        let item_id = +$("#inpNomenclatureId").val();
        $(this).prop("disabled", true);
        fncMyAjax("restore_nomenclature", "noms", [{name: "id", value: item_id}], 1)
            .done(function(data){
                if (data.sccss) {
                    main_modal.hide();
                    //listLoadFunction();
                    fncMarkItemEdited(item_id);
                } else {
                    fncShowFormError(data.msg ?? "Не удалось восстановить позицию");
                    $("#btnRestore").prop("disabled", false);
                }
            })
            .fail(function(){ $("#btnRestore").prop("disabled", false); });
    });

});
