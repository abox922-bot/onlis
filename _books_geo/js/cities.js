//==============================================================================
$(function(){
    let regions_arr = [];
    //~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    $("#slctRegion option[value!=0]").each(function(){
        regions_arr.push({id: +$(this).val(), country: +$(this).attr("data-country"), name: $(this).text()});
        $(this).remove();
    });
    //~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    $("#slctCountry").change(function(){
        $("#btnFastNew").prop("disabled", true);
        $("#divChptContent").addClass("d-none").html("");
        $("#divEmptyHint").removeClass("d-none");
        if (+$(this).val() > 0) {
            $("#slctRegion option[value!=0]").remove();
            regions_arr.forEach((item) => {
                if (item["country"] == $(this).val()) {
                    $("#slctRegion").append(`<option value="${item["id"]}">${item["name"]}</option>`);
                }
            });
            $("#slctRegion").prop("disabled", false);
        } else {
            $("#slctRegion option[value!=0]").remove();
            $("#slctRegion").prop("disabled", true);
        }
    });
    //~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    $("#slctRegion").change(function(){
        if (+$(this).val() > 0) {
            $("#divEmptyHint").addClass("d-none");
            $("#btnFastNew").prop("disabled", false);
            listLoadFunction(+$("#slctCountry").val(), +$(this).val());
        } else {
            $("#divChptContent").addClass("d-none").html("");
            $("#divEmptyHint").removeClass("d-none");
            $("#btnFastNew").prop("disabled", true);
        }
    });
    //~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    $("#btnFastNew").click(function(){
        let country_name = $("#slctCountry option:selected").text();
        let region_name  = $("#slctRegion option:selected").text();
        $("#mainModalBody").html(spnr_loading);
        $("#mainModalLabel").html("Добавление города");
        main_modal.show();
        let path = new URL("./_books_geo/cities_new.php", url);
        $("#mainModalBody").load(path.href, {country_name, region_name}, function(){
            $("#formNew").submit(function(e){
                e.preventDefault();
                e.stopImmediatePropagation();
                let params_arr = [];
                params_arr.push({name: "country", value: +$("#slctCountry").val()});
                params_arr.push({name: "region",  value: +$("#slctRegion").val()});
                let crt_arr = fncParamsCrt(".form-inp", params_arr);
                if (crt_arr["all_good"]) {
                    $("#btnSave").prop("disabled", true);
                    $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
                    fncMyAjax("new_city", "geo", crt_arr["params"], 0)
                        .done(function(data) {
                            if (data.sccss) {
                                listLoadFunction(+$("#slctCountry").val(), +$("#slctRegion").val());
                                main_modal.hide();
                            } else {
                                fncBtnReset();
                            }
                        })
                        .fail(function() {
                            fncBtnReset();
                        })
                        .always(function() {
                            listLoadFunction(+$("#slctCountry").val(), +$("#slctRegion").val());
                        });
                }
            });
        });
    });
    //~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
});
//==============================================================================
function listLoadFunction(country, region) {
    $("#divChptContent").removeClass("d-none").html(spnr_loading);
    let path = new URL("./_books_geo/cities_list.php", url);
    $("#divChptContent").load(path.href, {country, region}, function(){
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
    $("#mainModalLabel").html("Информация о городе");
    main_modal.show();
    let path = new URL("./_books_geo/cities_info.php", url);
    $("#mainModalBody").load(path.href, {id: item_id}, function(){
        $("#formInfo").submit(function(e){
            e.preventDefault();
            e.stopImmediatePropagation();
            let params_arr = [];
            params_arr.push({name: "item-id", value: item_id});
            let crt_arr = fncParamsCrt(".form-inp", params_arr);
            if (crt_arr["all_good"]) {
                $("#btnSave").prop("disabled", true);
                $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
                fncMyAjax("upd_city", "geo", crt_arr["params"], 0)
                    .done(function(data) {
                        if (data.sccss) {
                            listLoadFunction(+$("#slctCountry").val(), +$("#slctRegion").val());
                            main_modal.hide();
                        } else {
                            fncBtnReset();
                        }
                    })
                    .fail(function() {
                        fncBtnReset();
                    })
                    .always(function() {
                      listLoadFunction(+$("#slctCountry").val(), +$("#slctRegion").val());
                    });
            }
        });
    });
}
//==============================================================================
