$(function(){

    let nomenclature_id = +$("#inpNutritionNomenclatureId").val();

    if (canDo('nomenclature.manage')) {
        $("#formNutrition").off("submit").on("submit", function(e){
            e.preventDefault();
            e.stopImmediatePropagation();
            let params_arr = [];
            params_arr.push({name: "nomenclature_id", value: nomenclature_id});
            let crt_arr = fncParamsCrt(".form-inp", params_arr);
            if (crt_arr["all_good"]) {
                $("#btnSave").prop("disabled", true);
                $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
                fncMyAjax("upd_nutrition", "noms", crt_arr["params"], 1)
                    .done(function(data){
                        if (data.sccss) {
                            fncNomenclatureTabLoad(nomenclature_id, "nutrition");
                        } else {
                            fncShowFormError(data.msg ?? "Проверьте введённые данные");
                            fncBtnReset();
                        }
                    })
                    .fail(function(){ fncBtnReset(); });
            }
        });
    } else {
        $("#btnSave").hide();
        $(".form-inp").prop("disabled", true);
        $("#formNutrition").off("submit");
    }

    $("#btnClearNutrition").off("click").on("click", async function(){
        let confirmed = await fncConfirm("Очистить данные КБЖУ?");
        if (!confirmed) return;
        $(this).prop("disabled", true);
        fncMyAjax("clear_nutrition", "noms", [{name: "nomenclature_id", value: nomenclature_id}], 1)
            .done(function(data){
                if (data.sccss) {
                    fncNomenclatureTabLoad(nomenclature_id, "nutrition");
                } else {
                    fncShowFormError(data.msg ?? "Не удалось очистить данные");
                    $("#btnClearNutrition").prop("disabled", false);
                }
            })
            .fail(function(){ $("#btnClearNutrition").prop("disabled", false); });
    });

});
