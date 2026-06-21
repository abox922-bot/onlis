//==============================================================================
$(document).ready(function(){
    listLoadFunction();
    //~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    $("#btnFastNew").click(function(){
        $("#mainModalBody").html(spnr_loading);
        $("#mainModalLabel").html("Добавление страны");
        let path = new URL("./_books_geo/countries_new.php", url);
        $("#mainModalBody").load(path.href, function(){
            main_modal.show();
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
                                listLoadFunction();
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
function listLoadFunction() {
    $("#divChptContent").html(spnr_loading);
    let path = new URL("./_books_geo/countries_list.php", url);
    $("#divChptContent").load(path.href, function(){
        searchFunction();
        $(".itemTr").click(function(){
            infoLoadFunction(+$(this).attr("data-id"));
        });
        fncCheckNewItem(infoLoadFunction);
    });
}
//==============================================================================
function infoLoadFunction(item_id) {
    let item_name = $(`.itemName[data-id=${item_id}]`).html();
    $("#mainModalBody").html(spnr_loading);
    $("#mainModalLabel").html(`<small class="fw-normal">Информация о стране</small><br>${item_name}`);
    let path = new URL("./_books_geo/countries_info.php", url);
    $("#mainModalBody").load(path.href, {id: item_id}, function(){
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
                fncMyAjax("upd_country", "geo", crt_arr["params"], 0)
                    .done(function(data) {
                        if (data.sccss) {
                            listLoadFunction();
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
