$(function(){

    let id = +$("#inpObjectId").val();

    let preselect = {
        region: +$("#hdnAddrRegionId").val(),
        city:   +$("#hdnAddrCityId").val(),
        street: +$("#hdnAddrStreetId").val()
    };

    if (window.addrCountryPicker) window.addrCountryPicker.destroy();
    window.addrCountryPicker = new TomSelect("#slctCountry", { maxOptions: null, plugins: ["clear_button"] });

    window.addrRegionPicker = null;
    window.addrCityPicker   = null;
    window.addrStreetPicker = null;

    function loadRegions(countryId, preselectRegionId){
        if (window.addrRegionPicker) { window.addrRegionPicker.destroy(); window.addrRegionPicker = null; }
        if (window.addrCityPicker)   { window.addrCityPicker.destroy();   window.addrCityPicker   = null; }
        if (window.addrStreetPicker) { window.addrStreetPicker.destroy(); window.addrStreetPicker = null; }

        $("#slctRegion").prop("disabled", true).html('<option value="">Выберите регион</option>');
        $("#slctCity").prop("disabled", true).html('<option value="">Выберите населённый пункт</option>');
        $("#slctStreet").prop("disabled", true).html('<option value="">Выберите улицу</option>');

        if (!countryId) return;

        $("#spnRegionLoading").removeClass("d-none");

        fncMyAjax("regions_list", "geo", [{name: "country", value: countryId}], 1)
            .done(function(data){
                data.forEach(function(item){
                    $("#slctRegion").append(`<option value="${item.id}">${item.name}</option>`);
                });
                $("#slctRegion").prop("disabled", false);
                window.addrRegionPicker = new TomSelect("#slctRegion", { maxOptions: null, plugins: ["clear_button"] });

                if (preselectRegionId > 0) {
                    window.addrRegionPicker.setValue(preselectRegionId, true);
                    loadCities(preselectRegionId, preselect.city);
                }
            })
            .always(function(){
                $("#spnRegionLoading").addClass("d-none");
            });
    }

    function loadCities(regionId, preselectCityId){
        if (window.addrCityPicker)   { window.addrCityPicker.destroy();   window.addrCityPicker   = null; }
        if (window.addrStreetPicker) { window.addrStreetPicker.destroy(); window.addrStreetPicker = null; }

        $("#slctCity").prop("disabled", true).html('<option value="">Выберите населённый пункт</option>');
        $("#slctStreet").prop("disabled", true).html('<option value="">Выберите улицу</option>');

        if (!regionId) return;

        $("#spnCityLoading").removeClass("d-none");

        fncMyAjax("cities_list", "geo", [
            {name: "country", value: +window.addrCountryPicker.getValue()},
            {name: "region",  value: regionId}
        ], 1)
            .done(function(data){
                data.forEach(function(item){
                    $("#slctCity").append(`<option value="${item.id}">${item.name}</option>`);
                });
                $("#slctCity").prop("disabled", false);
                window.addrCityPicker = new TomSelect("#slctCity", { maxOptions: null, plugins: ["clear_button"] });

                if (preselectCityId > 0) {
                    window.addrCityPicker.setValue(preselectCityId, true);
                    loadStreets(preselectCityId, preselect.street);
                }
            })
            .always(function(){
                $("#spnCityLoading").addClass("d-none");
            });
    }

    function loadStreets(cityId, preselectStreetId){
        if (window.addrStreetPicker) { window.addrStreetPicker.destroy(); window.addrStreetPicker = null; }

        $("#slctStreet").prop("disabled", true).html('<option value="">Выберите улицу</option>');

        if (!cityId) return;

        $("#spnStreetLoading").removeClass("d-none");

        fncMyAjax("streets_list", "geo", [{name: "city", value: cityId}], 1)
            .done(function(data){
                data.forEach(function(item){
                    $("#slctStreet").append(`<option value="${item.id}">${item.name}</option>`);
                });
                $("#slctStreet").prop("disabled", false);
                window.addrStreetPicker = new TomSelect("#slctStreet", { maxOptions: null, plugins: ["clear_button"] });

                if (preselectStreetId > 0) {
                    window.addrStreetPicker.setValue(preselectStreetId, true);
                }
            })
            .always(function(){
                $("#spnStreetLoading").addClass("d-none");
            });
    }

    // Первичная загрузка каскада, если у объекта уже указан адрес
    let initial_country = +window.addrCountryPicker.getValue();
    if (initial_country > 0) {
        loadRegions(initial_country, preselect.region);
    }

    $("#slctCountry").on("change", function(){
        loadRegions(+$(this).val(), 0);
    });

    $("#slctRegion").on("change", function(){
        loadCities(+$(this).val(), 0);
    });

    $("#slctCity").on("change", function(){
        loadStreets(+$(this).val(), 0);
    });

    // Сабмит
    if (!canDo('objects.manage')) {
        $("#btnSave").hide();
        return;
    }

    $("#formInfo").submit(function(e){
        e.preventDefault();
        e.stopImmediatePropagation();

        let country_id = window.addrCountryPicker ? +window.addrCountryPicker.getValue() : 0;
        let region_id  = window.addrRegionPicker  ? +window.addrRegionPicker.getValue()  : 0;
        let city_id    = window.addrCityPicker    ? +window.addrCityPicker.getValue()    : 0;
        let street_id  = window.addrStreetPicker  ? +window.addrStreetPicker.getValue()  : 0;

        let address_valid = !!(country_id && region_id && city_id && street_id);

        let params_arr = [];
        params_arr.push({name: "id",         value: id});
        params_arr.push({name: "country_id", value: country_id});
        params_arr.push({name: "region_id",  value: region_id});
        params_arr.push({name: "city_id",    value: city_id});
        params_arr.push({name: "street_id",  value: street_id});

        let crt_arr = fncParamsCrt(".form-inp", params_arr);

        if (!address_valid) {
            fncShowFormError("Заполните адрес полностью — страну, регион, населённый пункт и улицу");
            return;
        }

        if (crt_arr["all_good"]) {
            $("#btnSave").prop("disabled", true);
            $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
            fncMyAjax("upd_object_address", "objs", crt_arr["params"], 1)
                .done(function(data){
                    if (!data.sccss) {
                        fncShowFormError(data.msg ?? "Проверьте введённые данные");
                    }
                })
                .fail(function(){ fncBtnReset(); })
                .always(function(){
                    fncBtnReset();
                });
        }
    });
});
