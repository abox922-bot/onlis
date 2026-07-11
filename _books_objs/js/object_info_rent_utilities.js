$(function(){

    let object_id = +$("#inpObjectId").val();

    listUtilitiesLoad();

    $("#btnNewUtility").off("click").on("click", function(){
        $("#modalOffcanvasBody").html(spnr_loading);
        $("#modalOffcanvasLabel").html("Добавление счётчика");
        modalOffcanvas.show();
        let path = new URL("./_books_objs/object_info_rent_utilities_new.php", url);
        $("#modalOffcanvasBody").load(path.href, function(){
            $("#formNewUtility").submit(function(e){
                e.preventDefault();
                e.stopImmediatePropagation();
                let params_arr = [];
                params_arr.push({name: "object_id", value: object_id});
                let crt_arr = fncParamsCrt(".form-inp", params_arr);
                if (crt_arr["all_good"]) {
                    $("#btnSave").prop("disabled", true);
                    $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
                    fncMyAjax("new_object_utility_type", "objs", crt_arr["params"], 1)
                        .done(function(data){
                            if (data.sccss) {
                                modalOffcanvas.hide();
                            } else {
                                fncBtnReset();
                                fncShowFormError(data.msg ?? "Проверьте введённые данные");
                            }
                        })
                        .fail(function(){ fncBtnReset(); })
                        .always(function(){ listUtilitiesLoad(); });
                }
            });
        });
    });

});
//==============================================================================
function listUtilitiesLoad() {
    let object_id = +$("#inpObjectId").val();
    $("#divUtilitiesList").html(spnr_loading);
    let path = new URL("./_books_objs/object_info_rent_utilities_list.php", url);
    $("#divUtilitiesList").load(path.href, {id: object_id}, function(){
        $(".utlTr").off("click").on("click", function(){
            utilityInfoLoad($(this).data("id"));
        });
    });
}
//==============================================================================
function utilityInfoLoad(item_id) {
    let item_name = $(`.utlName[data-id="${item_id}"]`).html();
    $("#modalOffcanvasBody").html(spnr_loading);
    $("#modalOffcanvasLabel").html(item_name);
    modalOffcanvas.show();
    let path = new URL("./_books_objs/object_info_rent_utilities_info.php", url);
    $("#modalOffcanvasBody").load(path.href, function(){
        $("#inpUtilityId").val(item_id);
        $(".inline-tab-info").off("click").on("click", function(){
            $(".inline-tab-info").removeClass("active");
            $(this).addClass("active");
            fncUtilityInfoTabLoad(item_id, $(this).data("target"));
        });

        fncUtilityInfoTabLoad(item_id, "main");

    });
}
//==============================================================================
function fncUtilityInfoTabLoad(id, target) {
    $(".inline-tab-info").prop("disabled", true);
    $("#divUtilityInfoContent").html(spnr_loading);
    let path = new URL(`./_books_objs/object_info_rent_utilities_info_${target}.php`, url);
    $("#divUtilityInfoContent").load(path.href, {id}, function(){
        $(".inline-tab-info").prop("disabled", false);
    });
}
