$(function(){

    let st_id    = +$("#hdnStId").val();
    let org_id   = +$("#hdnOrgId").val();

    let phone_mask = $("#inpPhone").data("phone-mask");
    if (phone_mask) $("#inpPhone").mask(phone_mask);

    if (!canDo('organizations.manage')) {
        $("#btnSave").hide();
        return;
    }

    $("#formStaffPerson").submit(function(e){
        e.preventDefault();
        e.stopImmediatePropagation();
        let params_arr = [];
        params_arr.push({name: "st-id", value: st_id});
        let crt_arr = fncParamsCrt(".form-inp", params_arr);
        if (crt_arr["all_good"]) {
            $("#btnSave").prop("disabled", true);
            $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
            fncMyAjax("upd_organization_staff_person", "orgs", crt_arr["params"], 1)
                .done(function(data){
                    if (data.sccss) {
                        staffListLoad(org_id);
                        fncBtnReset();
                        //modalOffcanvas.hide();
                    } else {
                        fncBtnReset();
                        fncShowFormError(data.msg ?? "Проверьте введённые данные");
                    }
                })
                .fail(function(){ fncBtnReset(); });
        }
    });

});
