$(function(){

    let st_id    = +$("#hdnStId").val();
    let org_id   = +$("#hdnOrgId").val();
    let org_type = $("#hdnOrgType").val();

    let phone_mask = $("#inpWPhone").data("phone-mask");
    if (phone_mask) $("#inpWPhone").mask(phone_mask);

    if (!canDo('organizations.manage')) {
        $("#btnSave, #btnDismiss").hide();
        return;
    }

    $("#formStaffMain").submit(function(e){
        e.preventDefault();
        e.stopImmediatePropagation();
        let params_arr = [];
        params_arr.push({name: "st-id", value: st_id});
        let crt_arr = fncParamsCrt(".form-inp", params_arr);
        if (crt_arr["all_good"]) {
            $("#btnSave").prop("disabled", true);
            $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
            fncMyAjax("upd_organization_staff_main", "orgs", crt_arr["params"], 0)
                .done(function(){
                    staffListLoad(org_id);
                    fncBtnReset();
                    //modalOffcanvas.hide();
                })
                .fail(function(){ fncBtnReset(); });
        }
    });

    $("#btnDismiss").on("click", function(){
        if (confirm("Уволить сотрудника?")) {
            fncMyAjax("dismiss_organization_staff", "orgs", [
                {name: "st-id", value: st_id}
            ], 0)
            .always(function(){
                staffListLoad(org_id);
                modalOffcanvas.hide();
            });
        }
    });

});
