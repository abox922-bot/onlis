$(function(){

    let id       = +$("#hdnOrgId").val();
    let org_type = $("#hdnOrgType").val();

    if (!canDo('organizations.manage')) {
        $("#btnSave").hide();
        return;
    }

    let phone_mask = $("#inpPhone").data("phone-mask");
    if (phone_mask) {
        $("#inpPhone").mask(phone_mask);
    }

    $("#formInfo").submit(function(e){
        e.preventDefault();
        e.stopImmediatePropagation();

        let all_good = true;
        let reqs_arr = [];

        $(".req-inp").each(function(){
            let $el    = $(this);
            let length = $el.data("length") ? +$el.data("length") : 0;
            let is_req = $el.data("required") ? true : false;

            fncNormalizeInp("#" + $el.attr("id"), $el.data("type"));
            let val = $el.val().trim();

            let valid = true;
            if (length > 0 && val.length !== length) valid = false;
            if (is_req && val.length === 0) valid = false;

            if (!valid) {
                $el.addClass("is-invalid");
                all_good = false;
            } else {
                $el.removeClass("is-invalid");
                reqs_arr.push({
                    id:   $el.data("req-id"),
                    value: val,
                    uniq: $el.data("uniq")
                });
            }
        });

        let params_arr = [];
        params_arr.push({name: "item-id",   value: id});
        params_arr.push({name: "reqs-list", value: reqs_arr});
        let crt_arr = fncParamsCrt(".form-inp", params_arr);

        if (all_good && crt_arr["all_good"]) {
            $("#btnSave").prop("disabled", true);
            $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
            fncMyAjax("upd_organization_main", "orgs", crt_arr["params"], 1)
                .done(function(data){
                    if (data.sccss) {
                        main_modal.hide();
                    } else {
                        fncBtnReset();
                        fncShowFormError(data.msg ?? "Проверьте введённые данные");
                    }
                })
                .fail(function(){
                    fncBtnReset();
                })
                .always(function(){
                    listLoadFunction(org_type);
                });
        }
    });

});
