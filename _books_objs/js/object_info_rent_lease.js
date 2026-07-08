$("#chckInRent").off("change").on("change", function(){
    if ($(this).prop("checked")) {
        $("#divRentInfo").removeClass("d-none");
        $(".rent-info").addClass("form-inp");
    } else {
        $("#divRentInfo").addClass("d-none");
        $(".rent-info").removeClass("form-inp");
    }
});

if (!canDo('objects.manage')) {
    $("#btnSave").hide();
    $("#formRentLease").off("submit");
} else {
    $("#formRentLease").submit(function(e){
        e.preventDefault();
        e.stopImmediatePropagation();
        let params_arr = [];
        params_arr.push({name: "id", value: $("#inpObjectId").val()});
        let crt_arr = fncParamsCrt(".form-inp", params_arr);
        if (crt_arr["all_good"]) {
            $("#btnSave").prop("disabled", true);
            $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
            fncMyAjax("upd_object_rent", "objs", crt_arr["params"], 1)
                .done(function(data){
                    if (!data.sccss) {
                        fncShowFormError(data.msg ?? "Проверьте введённые данные");
                    }
                })
                .fail(function(){ fncBtnReset(); })
                .always(function(){ fncBtnReset(); });
        }
    });
}
