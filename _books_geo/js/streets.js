let cityPicker;
//==============================================================================
$(document).ready(function(){
    cityPicker = new TomSelect("#slctCity", {
        placeholder: "Выберите город",
        allowEmptyOption: false,
        maxOptions: null,
        wrapperClass: "ts-wrapper toolbar-filter",
        plugins: ["clear_button"],
        render: {
            item: function(data, escape) {
                const cityName = data.text.split(" — ")[0].trim();
                return `<div class="item">${escape(cityName)}</div>`;
            }
        },
        onChange: function(value) {
            if (value) {
                $("#divEmptyHint").addClass("d-none");
                $("#btnFastNew").prop("disabled", false);
                listLoadFunction(value);
                cityPicker.blur();
            } else {
                $("#divChptContent").addClass("d-none").html("");
                $("#divEmptyHint").removeClass("d-none");
                $("#btnFastNew").prop("disabled", true);
            }
        }
    });
    //~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    $("#btnFastNew").click(function(){
        let city      = cityPicker.getValue();
        let option    = document.querySelector(`#slctCity option[value="${city}"]`);
        let region    = option.dataset.region;
        let country   = option.dataset.country;
        let city_name = cityPicker.getOption(city).textContent.trim();
        $("#mainModalBody").html(spnr_loading);
        $("#mainModalLabel").html("Добавление улицы");
        let path = new URL("./_books_geo/streets_new.php", url);
        $("#mainModalBody").load(path.href, {city_name}, function(){
            main_modal.show();
            $("#formNew").submit(function(e){
                e.preventDefault();
                e.stopImmediatePropagation();
                let params_arr = [];
                params_arr.push({name: "country", value: country});
                params_arr.push({name: "region",  value: region});
                params_arr.push({name: "city",    value: city});
                let crt_arr = fncParamsCrt(".form-inp", params_arr);
                if (crt_arr["all_good"]) {
                    $("#btnSave").prop("disabled", true);
                    $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
                    fncMyAjax("new_street", "geo", crt_arr["params"], 0)
                        .done(function(data) {
                            if (data.sccss) {
                                listLoadFunction(cityPicker.getValue());
                                main_modal.hide();
                            } else {
                                fncBtnReset();
                            }
                        })
                        .fail(function() {
                            fncBtnReset();
                        });
                }
            });
        });
    });
    //~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
});
//==============================================================================
function listLoadFunction(city) {
    $("#divChptContent").removeClass("d-none").html(spnr_loading);
    let path = new URL("./_books_geo/streets_list.php", url);
    $("#divChptContent").load(path.href, {city}, function(){
        searchFunction();
        $(".itemTr").click(function(){
            infoLoadFunction(+$(this).attr("data-id"));
        });
        if (localStorage.getItem("new_item") !== null) {
            infoLoadFunction(localStorage.getItem("new_item"));
            localStorage.removeItem("new_item");
        }
    });
}
//==============================================================================
function infoLoadFunction(item_id) {
    let city      = cityPicker.getValue();
    let option    = document.querySelector(`#slctCity option[value="${city}"]`);
    let city_name = cityPicker.getOption(city).textContent.trim();
    let item_name = $(`.itemName[data-id=${item_id}]`).html();
    $("#mainModalBody").html(spnr_loading);
    $("#mainModalLabel").html(`<small class="fw-normal">Информация об улице</small><br>${item_name}`);
    let path = new URL("./_books_geo/streets_info.php", url);
    $("#mainModalBody").load(path.href, {id: item_id, city_name}, function(){
        main_modal.show();
        $("#formInfo").submit(function(e){
            e.preventDefault();
            e.stopImmediatePropagation();
            let params_arr = [];
            params_arr.push({name: "item-id", value: item_id});
            let crt_arr = fncParamsCrt(".form-inp", params_arr);
            if (crt_arr["all_good"]) {
                $("#btnSave").prop("disabled", true);
                $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
                fncMyAjax("upd_street", "geo", crt_arr["params"], 0)
                    .done(function(data) {
                        if (data.sccss) {
                            listLoadFunction(cityPicker.getValue());
                            main_modal.hide();
                        } else {
                            fncBtnReset();
                        }
                    })
                    .fail(function() {
                        fncBtnReset();
                    });
            }
        });
    });
}
//==============================================================================
