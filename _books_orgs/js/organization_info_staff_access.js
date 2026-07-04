$(function(){

    let st_id  = +$("#hdnStId").val();
    let org_id = +$("#hdnOrgId").val();

    // Живое обновление лейбла чекбокса
    $("#chckIsActive").on("change", function(){
        $("#lblIsActive").html($(this).prop("checked") ? "Активен" : "Заблокирован");
    });

    if (!canDo('organizations.manage')) {
        $("#btnSave, #btnResetPass").hide();
        return;
    }

    // Сохранение доступа и логина
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

    // Сброс пароля
    $("#btnResetPass").on("click", function(){
        if (confirm("Сгенерировать новый пароль для сотрудника?")) {
            $(this).prop("disabled", true);
            fncMyAjax("reset_staff_password", "orgs", [{name: "st-id", value: st_id}], 1)
                .done(function(data){
                    if (data.sccss) {
                        $("#spnNewPassword").html(data.password);
                        $("#divNewPassword").removeClass("d-none");
                    } else {
                        fncShowFormError(data.msg ?? "Ошибка сброса пароля");
                    }
                })
                .fail(function(){
                    fncShowFormError("Ошибка сброса пароля");
                })
                .always(function(){
                    $("#btnResetPass").prop("disabled", false);
                });
        }
    });

});
