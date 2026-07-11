$(function(){

    listTempLoad();

    $("#btnNewTemp").off("click").on("click", function(){
        let object_id = $("#inpObjectId").val();

        $("#modalOffcanvasLabel").html("Добавление изменения графика");
        $("#modalOffcanvasBody").html(spnr_loading);
        modalOffcanvas.show();
        let path = new URL("./_books_objs/object_info_schedule_temp_new.php", url);
        $("#modalOffcanvasBody").load(path.href, function(){
            $("#formTempNew").submit(function(e){
                e.preventDefault();
                e.stopImmediatePropagation();
                let params_arr = [];
                params_arr.push({name: "object_id", value: object_id});
                let crt_arr = fncParamsCrt(".form-inp", params_arr);
                if (crt_arr["all_good"]) {
                    $("#btnSave").prop("disabled", true);
                    $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
                    fncMyAjax("new_object_schedule_temporary", "objs", crt_arr["params"], 1)
                        .done(function(data){
                            if (data.sccss) {
                                modalOffcanvas.hide();
                            } else {
                                fncShowFormError(data.msg ?? "Проверьте введённые данные");
                            }
                        })
                        .fail(function(){ fncBtnReset(); })
                        .always(function(){ fncBtnReset(); listTempLoad(); });
                }
            });
        });
    });

});

function listTempLoad() {
    let object_id = $("#inpObjectId").val();
    $("#divTempList").html(spnr_loading);
    let path = new URL("./_books_objs/object_info_schedule_temp_list.php", url);
    $("#divTempList").load(path.href, {id: object_id}, function(){

        $(".periodDeleteBtn").off("click").on("click", async function(){
            let period_id = $(this).closest(".tempPeriodCard").data("id");
            let confirmed = await fncConfirm("Удалить это изменение графика?");
            if (confirmed) {
                fncMyAjax("del_object_schedule_temporary", "objs", [{name: "id", value: period_id}], 1)
                    .always(function(){ listTempLoad(); });
            }
        });

        $(".periodBreakItem").off("click").on("click", async function(){
            let break_id = $(this).data("id");
            let confirmed = await fncConfirm("Удалить перерыв?");
            if (confirmed) {
                fncMyAjax("del_object_schedule_temporary_break", "objs", [{name: "id", value: break_id}], 1)
                    .always(function(){ listTempLoad(); });
            }
        });

        $(".periodNewBreakBtn").off("click").on("click", function(){
            let period_id = $(this).closest(".tempPeriodCard").data("id");

            $("#modalOffcanvasLabel").html("Добавление перерыва");
            $("#modalOffcanvasBody").html(spnr_loading);
            modalOffcanvas.show();
            let path = new URL("./_books_objs/object_info_schedule_main_new_break.php", url);
            $("#modalOffcanvasBody").load(path.href, function(){
                $("#formBreakNew").submit(function(e){
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    let params_arr = [];
                    params_arr.push({name: "schedule_temporary_id", value: period_id});
                    let crt_arr = fncParamsCrt(".form-break-inp", params_arr);
                    if (crt_arr["all_good"]) {
                        $("#btnBreakSave").prop("disabled", true);
                        $("#btnBreakSaveText, #divBreakSaveLoading").toggleClass("d-none");
                        fncMyAjax("new_object_schedule_temporary_break", "objs", crt_arr["params"], 1)
                            .done(function(data){
                                if (data.sccss) {
                                    modalOffcanvas.hide();
                                } else {
                                    $("#spnBreakFormError").html(data.msg ?? "Проверьте введённые данные");
                                    $("#divBreakFormError").removeClass("d-none");
                                }
                            })
                            .fail(function(){})
                            .always(function(){
                                $("#btnBreakSave").prop("disabled", false);
                                $("#btnBreakSaveText, #divBreakSaveLoading").toggleClass("d-none");
                                listTempLoad();
                            });
                    }
                });
            });
        });

    });
}
