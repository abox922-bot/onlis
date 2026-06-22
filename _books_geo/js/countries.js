//==============================================================================
$(function(){
    listLoadFunction();
    //~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    $("#btnFastNew").click(function(){
        $("#mainModalBody").html(spnr_loading);
        $("#mainModalLabel").html("Добавление страны");
        main_modal.show();
        let path = new URL("./_books_geo/countries_new.php", url);
        $("#mainModalBody").load(path.href, function(){
            $("#formNew").submit(function(e){
                e.preventDefault();
                e.stopImmediatePropagation();
                let crt_arr = fncParamsCrt(".form-inp");
                if (crt_arr["all_good"]) {
                    $("#btnSave").prop("disabled", true);
                    $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
                    fncMyAjax("new_country", "geo", crt_arr["params"], 1)
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
                            listLoadFunction();
                        });
                }
            });
        });
    });
    //~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
});
//==============================================================================
function listLoadFunction() {
    $("#divChptContent").html(spnr_loading);
    let path = new URL("./_books_geo/countries_list.php", url);
    $("#divChptContent").load(path.href, function(){
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
    $("#mainModalLabel").html("Информация о стране");
    main_modal.show();
    let path = new URL("./_books_geo/countries_info.php", url);
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
                fncMyAjax("upd_country", "geo", crt_arr["params"], 0)
                    .done(function(data) {
                        if (data.sccss) {
                            main_modal.hide();
                        } else {
                            fncBtnReset();
                        }
                    })
                    .fail(function() {
                        fncBtnReset();
                    })
                    .always(function() {
                        listLoadFunction();
                    });
            }
        });
    });
}
//==============================================================================
