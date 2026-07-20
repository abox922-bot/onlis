$(function(){

    if (window.countryPicker) window.countryPicker.destroy();
    window.countryPicker = new TomSelect("#slctCountry", {
        maxOptions: null
    });

    if (window.phoneCountryPicker) window.phoneCountryPicker.destroy();
    window.phoneCountryPicker = new TomSelect("#slctPhoneCountry", {
        maxOptions: null,
        onChange: function(value){
            if (!value) {
                $("#inpPhone").prop("disabled", true).val("").unmask();
                return;
            }
            let opt  = $(`#slctPhoneCountry option[value="${value}"]`);
            let mask = opt.data("mask");
            $("#inpPhone").prop("disabled", false).val("").attr("data-phone-mask", mask);
            if (mask) $("#inpPhone").mask(mask);
        }
    });

    let phone_mask = $("#inpPhone").data("phone-mask");
    if (phone_mask) {
        $("#inpPhone").mask(phone_mask);
    }

    if (!canDo('organizations.manage')) {
        $("#btnSave").hide();
    } else {
        $("#formStaffPerson").submit(function(e){
            e.preventDefault();
            e.stopImmediatePropagation();
            let params_arr = [];
            params_arr.push({name: "user-id", value: +$("#hdnUserId").val()});
            params_arr.push({name: "user-country-id",  value: window.countryPicker.getValue()});
            params_arr.push({name: "phone-country-id", value: window.phoneCountryPicker.getValue()});
            let crt_arr = fncParamsCrt(".form-inp", params_arr);
            if (crt_arr["all_good"]) {
                $("#btnSave").prop("disabled", true);
                $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
                fncMyAjax("upd_person", "users", crt_arr["params"], 0)
                    .done(function(){
                        staffListLoad(+$("#hdnOrgId").val());
                        fncBtnReset();
                    })
                    .fail(function(){ fncBtnReset(); });
            }
        });
    }

});
