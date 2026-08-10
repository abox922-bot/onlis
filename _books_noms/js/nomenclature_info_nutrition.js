$(function(){

    let nomenclature_id = +$("#inpNutritionNomenclatureId").val();

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
                        fncBtnReset();
                    } else {
                        fncShowFormError(data.msg ?? "Проверьте введённые данные");
                        fncBtnReset();
                    }
                })
                .fail(function(){ fncBtnReset(); });
        }
    });

});
