$(function(){

    let id       = +$("#hdnOrgId").val();
    let org_type = $("#hdnOrgType").val();
    let cities_arr  = [];
    let streets_arr = [];

    // 1. Сначала все DOM-манипуляции
    $("#slctStreet option[value!='0']").each(function(){
        streets_arr.push({id: +$(this).val(), city: +$(this).data("city"), name: $(this).html()});
        $(this).remove();
    });

    $("#slctCity option[value!='0']").each(function(){
        cities_arr.push({id: +$(this).val(), region: +$(this).data("region"), name: $(this).html()});
        $(this).remove();
    });

    if (+$("#slctRegion").val() > 0) {
        cities_arr.forEach(function(item){
            if (item.region == +$("#slctRegion").val()) {
                $("#slctCity").append(`<option value="${item.id}">${item.name}</option>`);
            }
        });
        $("#slctCity").prop("disabled", false);
    }

    let city_slct = +$("#slctCity").data("selected");
    if (city_slct > 0 && !$("#slctCity").prop("disabled")) {
        $(`#slctCity option[value="${city_slct}"]`).prop("selected", true);
    }

    if (+$("#slctCity").val() > 0) {
        streets_arr.forEach(function(item){
            if (item.city == +$("#slctCity").val()) {
                $("#slctStreet").append(`<option value="${item.id}">${item.name}</option>`);
            }
        });
        $("#slctStreet").prop("disabled", false);
    }

    let street_slct = +$("#slctStreet").data("selected");
    if (street_slct > 0 && !$("#slctStreet").prop("disabled")) {
        $(`#slctStreet option[value="${street_slct}"]`).prop("selected", true);
    }

    // 2. Инициализируем Tom Select — ПОСЛЕ всех DOM-манипуляций
    if (window.regionPicker) window.regionPicker.destroy();
    window.regionPicker = new TomSelect("#slctRegion", { maxOptions: null, plugins: ["clear_button"] });

    if (window.cityPicker) window.cityPicker.destroy();
    window.cityPicker = null;
    if (!$("#slctCity").prop("disabled")) {
        window.cityPicker = new TomSelect("#slctCity", { maxOptions: null, plugins: ["clear_button"] });
    }

    if (window.streetPicker) window.streetPicker.destroy();
    window.streetPicker = null;
    if (!$("#slctStreet").prop("disabled")) {
        window.streetPicker = new TomSelect("#slctStreet", { maxOptions: null, plugins: ["clear_button"] });
    }

    // 3. Обработчики change — на оригинальные select (не на Tom Select)
    $("#slctRegion").on("change", function(){
        if (window.cityPicker)   { window.cityPicker.destroy();   window.cityPicker   = null; }
        if (window.streetPicker) { window.streetPicker.destroy(); window.streetPicker = null; }

        $("#slctCity option[value!='0'], #slctStreet option[value!='0']").remove();
        $("#slctStreet").prop("disabled", true);

        if (+$(this).val() > 0) {
            cities_arr.forEach(function(item){
                if (item.region == +$("#slctRegion").val()) {
                    $("#slctCity").append(`<option value="${item.id}">${item.name}</option>`);
                }
            });
            $("#slctCity").prop("disabled", false);
            window.cityPicker = new TomSelect("#slctCity", { maxOptions: null, plugins: ["clear_button"] });
        } else {
            $("#slctCity").prop("disabled", true);
        }
    });

    $("#slctCity").on("change", function(){
        if (window.streetPicker) { window.streetPicker.destroy(); window.streetPicker = null; }
        $("#slctStreet option[value!='0']").remove();

        if (+$(this).val() > 0) {
            streets_arr.forEach(function(item){
                if (item.city == +$("#slctCity").val()) {
                    $("#slctStreet").append(`<option value="${item.id}">${item.name}</option>`);
                }
            });
            $("#slctStreet").prop("disabled", false);
            window.streetPicker = new TomSelect("#slctStreet", { maxOptions: null, plugins: ["clear_button"] });
        } else {
            $("#slctStreet").prop("disabled", true);
        }
    });

    // Сабмит
    if (!canDo('organizations.manage')) {
        $("#btnSave").hide();
        return;
    }

    $("#formInfo").submit(function(e){
        e.preventDefault();
        e.stopImmediatePropagation();

        let params_arr = [];
        params_arr.push({name: "item-id",  value: id});
        params_arr.push({name: "adr-reg",  value: window.regionPicker  ? +window.regionPicker.getValue()  : 0});
        params_arr.push({name: "adr-city", value: window.cityPicker    ? +window.cityPicker.getValue()    : 0});
        params_arr.push({name: "adr-str",  value: window.streetPicker  ? +window.streetPicker.getValue()  : 0});

        let crt_arr = fncParamsCrt(".form-inp", params_arr);

        if (crt_arr["all_good"]) {
            $("#btnSave").prop("disabled", true);
            $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
            fncMyAjax("upd_organization_address", "orgs", crt_arr["params"], 0)
                .done(function(){ main_modal.hide(); })
                .fail(function(){ fncBtnReset(); })
                .always(function(){ listLoadFunction(org_type); });
        }
    });

});
