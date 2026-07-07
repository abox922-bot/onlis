if (!canDo('objects.manage')) {
    $("#btnSave").hide();
    $("#formInfoMain").off("submit");
} else {
    $("#formInfoMain").submit(function(e){
        e.preventDefault();
        e.stopImmediatePropagation();
        let params_arr = [];
        params_arr.push({name: "id", value: $("#inpObjectId").val()});
        let crt_arr = fncParamsCrt(".form-inp", params_arr);
        if (crt_arr["all_good"]) {
            $("#btnSave").prop("disabled", true);
            $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
            fncMyAjax("upd_object_main", "objs", crt_arr["params"], 1)
                .done(function(data){
                    if (!data.sccss) {
                        fncShowFormError(data.msg ?? "Проверьте введённые данные");
                    }
                })
                .fail(function(){ fncBtnReset(); })
                .always(function(){
                    fncBtnReset();
                    listLoadFunction();
                });
        }
    });
}
