//==============================================================================
$(function(){
    if (!canDo('geography.manage')) {
        $("#btnFastNew").hide();
    }

    if (window.cityPicker) window.cityPicker.destroy();
    window.cityPicker = new TomSelect("#slctCity", {
        placeholder: "Выберите город",
        allowEmptyOption: false,
        maxOptions: null,
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
                window.cityPicker.blur();
            } else {
                $("#divChptContent").addClass("d-none").html("");
                $("#divEmptyHint").removeClass("d-none");
                $("#btnFastNew").prop("disabled", true);
            }
        }
    });
    //~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    $("#btnFastNew").click(function(){
        let city      = window.cityPicker.getValue();
        let option    = document.querySelector(`#slctCity option[value="${city}"]`);
        let region    = option.dataset.region;
        let country   = option.dataset.country;
        let city_name = window.cityPicker.getOption(city).textContent.trim().split(" — ")[0].trim();
        $("#mainModalBody").html(spnr_loading);
        $("#mainModalLabel").html("Добавление улицы");
        main_modal.show();
        let path = new URL("./_books_geo/streets_new.php", url);
        $("#mainModalBody").load(path.href, {city_name}, function(){
          if (!canDo('geography.manage')) {
              $("#btnSave").hide();
              $("#formNew").off("submit");
          } else {
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
                        .done(function() {
                            main_modal.hide();
                        })
                        .fail(function() {
                            fncBtnReset();
                        })
                        .always(function() {
                            listLoadFunction(window.cityPicker.getValue());
                        });
                }
            });
          }
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
        $(".itemTr").off("click").on("click", function(){
            infoLoadFunction(+$(this).data("id"));
        });
        fncCheckNewItem(infoLoadFunction);
    });
}
//==============================================================================
function infoLoadFunction(item_id) {
    let city      = window.cityPicker.getValue();
    let city_name = window.cityPicker.getOption(city).textContent.trim().split(" — ")[0].trim();
    let item_name = $(`.itemName[data-id="${item_id}"]`).html();
    $("#mainModalBody").html(spnr_loading);
    $("#mainModalLabel").html("Информация об улице");
    main_modal.show();
    let path = new URL("./_books_geo/streets_info.php", url);
    $("#mainModalBody").load(path.href, {id: item_id, city_name}, function(){
      if (!canDo('geography.manage')) {
          $("#btnSave").hide();
          $("#formInfo").off("submit");
      } else {
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
                    .done(function() {
                        main_modal.hide();
                    })
                    .fail(function() {
                        fncBtnReset();
                    })
                    .always(function() {
                        listLoadFunction(window.cityPicker.getValue());
                    });
            }
        });
      }
    });
}
//==============================================================================
