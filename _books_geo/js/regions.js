//==============================================================================
$(function(){
    if (!canDo('geography.edit')) {
        $("#btnFastNew").hide();
    }
    //~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    $("#slctCountry").change(function(){
        if (+$(this).val() > 0) {
            $("#divEmptyHint").addClass("d-none");
            $("#btnFastNew").prop("disabled", false);
            listLoadFunction(+$(this).val());
        } else {
            $("#divChptContent").addClass("d-none").html("");
            $("#divEmptyHint").removeClass("d-none");
            $("#btnFastNew").prop("disabled", true);
        }
    });
    //~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    $("#btnFastNew").click(function(){
        let country_name = $("#slctCountry option:selected").text();
        $("#mainModalBody").html(spnr_loading);
        $("#mainModalLabel").html("Добавление региона");
        main_modal.show();
        let path = new URL("./_books_geo/regions_new.php", url);
        $("#mainModalBody").load(path.href, {country_name}, function(){
          if (!canDo('geography.edit')) {
              $("#btnSave").hide();
              $("#formNew").off("submit");
          } else {
            $("#formNew").submit(function(e){
                e.preventDefault();
                e.stopImmediatePropagation();
                let params_arr = [];
                params_arr.push({name: "country", value: +$("#slctCountry").val()});
                let crt_arr = fncParamsCrt(".form-inp", params_arr);
                if (crt_arr["all_good"]) {
                    $("#btnSave").prop("disabled", true);
                    $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
                    fncMyAjax("new_region", "geo", crt_arr["params"], 1)
                        .done(function(data) {
                            if (data.sccss) {
                                localStorage.setItem("new_item", data["id"]);
                            } else {
                                fncBtnReset();
                            }
                        })
                        .fail(function() {
                            fncBtnReset();
                        })
                        .always(function() {
                            listLoadFunction($("#slctCountry").val());
                        });
                }
            });
          }
        });
    });
    //~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
});
//==============================================================================
function listLoadFunction(country) {
    $("#divChptContent").removeClass("d-none").html(spnr_loading);
    let path = new URL("./_books_geo/regions_list.php", url);
    $("#divChptContent").load(path.href, {country}, function(){
        searchFunction();
        $(".itemTr").off("click").on("click", function(){
            infoLoadFunction(+$(this).data("id"));
        });
        fncCheckNewItem(infoLoadFunction);
    });
}
//==============================================================================
function infoLoadFunction(item_id) {
    let item_name = $(`.itemName[data-id="${item_id}"]`).html();
    $("#mainModalBody").html(spnr_loading);
    $("#mainModalLabel").html("Информация о регионе");
    main_modal.show();
    let path = new URL("./_books_geo/regions_info.php", url);
    $("#mainModalBody").load(path.href, {id: item_id}, function(){
      if (!canDo('geography.edit')) {
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
                fncMyAjax("upd_region", "geo", crt_arr["params"], 0)
                    .done(function(data) {
                      main_modal.hide();
                    })
                    .fail(function() {
                        fncBtnReset();
                    })
                    .always(function() {
                      listLoadFunction($("#slctCountry").val());
                    });
            }
        });
      }
    });
}
//==============================================================================
