$(function(){

    $("#chckIsActive").on("change", function(){
        $("#lblIsActive").html($(this).prop("checked") ? "Активен" : "Заблокирован");
    });

    $("#btnGenLogin").on("click", function(){
        let login = String(Math.floor(10000 + Math.random() * 90000));
        $("#inpLogin").val(login).prop("disabled", false);
    });

    $("#btnGenPass").on("click", function(){
        let pass = String(Math.floor(1000 + Math.random() * 9000));
        $("#inpPassword").val(pass).prop("disabled", false);
    });

    if (!canDo('organizations.manage')) {
        $("#btnSave, #btnGenLogin, #btnGenPass").hide();
        return;
    }

    $("#formStaffAccess").submit(function(e){
        e.preventDefault();
        e.stopImmediatePropagation();
        let params_arr = [];
        params_arr.push({name: "user-id", value: +$("#hdnUserId").val()});
        let crt_arr = fncParamsCrt(".form-inp", params_arr);
        if (crt_arr["all_good"]) {
            $("#btnSave").prop("disabled", true);
            $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
            fncMyAjax("upd_access", "users", crt_arr["params"], 1)
                .done(function(data){
                    if (data.sccss) {
                        fncBtnReset();
                        staffListLoad(+$("#hdnOrgId").val());
                    } else {
                        fncBtnReset();
                        fncShowFormError(data.msg ?? "Проверьте введённые данные");
                    }
                })
                .fail(function(){ fncBtnReset(); });
        }
    });

});
