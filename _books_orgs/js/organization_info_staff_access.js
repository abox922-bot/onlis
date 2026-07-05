$(function(){

    let st_id  = +$("#hdnStId").val();
    let org_id = +$("#hdnOrgId").val();

    // Живое обновление лейбла
    $("#chckIsActive").on("change", function(){
        $("#lblIsActive").html($(this).prop("checked") ? "Активен" : "Заблокирован");
    });

    // Генерация логина — 5 цифр
    $("#btnGenLogin").on("click", function(){
        let login = String(Math.floor(10000 + Math.random() * 90000));
        $("#inpLogin").val(login).prop("disabled", false);
    });

    // Генерация пароля — 4 цифры
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
        params_arr.push({name: "st-id", value: st_id});
        let crt_arr = fncParamsCrt(".form-inp", params_arr);
        if (crt_arr["all_good"]) {
            $("#btnSave").prop("disabled", true);
            $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
            fncMyAjax("upd_organization_staff_access", "orgs", crt_arr["params"], 1)
                .done(function(data){
                    if (data.sccss) {
                        fncBtnReset();
                        staffListLoad(org_id);
                    } else {
                        fncBtnReset();
                        fncShowFormError(data.msg ?? "Проверьте введённые данные");
                    }
                })
                .fail(function(){ fncBtnReset(); });
        }
    });

});
