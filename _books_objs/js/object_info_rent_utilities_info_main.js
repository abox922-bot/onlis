if (!canDo('objects.manage')) {
    $("#btnSave").hide();
    $("#formUtilityMain").off("submit");
} else {
    $("#formUtilityMain").submit(function(e){
        e.preventDefault();
        e.stopImmediatePropagation();
        let params_arr = [];
        params_arr.push({name: "id", value: $("#inpUtilityId").val()});
        let crt_arr = fncParamsCrt(".form-inp", params_arr);
        if (crt_arr["all_good"]) {
            $("#btnSave").prop("disabled", true);
            $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
            fncMyAjax("upd_object_utility_type", "objs", crt_arr["params"], 1)
                .done(function(data){
                    if (!data.sccss) {
                        fncShowFormError(data.msg ?? "Проверьте введённые данные");
                    }
                })
                .fail(function(){ fncBtnReset(); })
                .always(function(){ fncBtnReset(); listUtilitiesLoad(); });
        }
    });
}
