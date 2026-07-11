$(function(){

    fncDowLoad();

    $("#slctDow").off("change").on("change", function(){
        fncDowLoad();
    });

});

function fncDowLoad() {
    let id  = $("#inpObjectId").val();
    let dow = $("#slctDow").val();

    $("#divDowContent").html(spnr_loading);
    let path = new URL("./_books_objs/object_info_schedule_main_dow.php", url);
    $("#divDowContent").load(path.href, {id, dow}, function(){

        if (!canDo('objects.manage')) {
            $("#btnSave").hide();
            $("#btnNewBreak").hide();
            $("#formDow").off("submit");
        } else {
            $("#formDow").submit(function(e){
                e.preventDefault();
                e.stopImmediatePropagation();
                let params_arr = [];
                params_arr.push({name: "object_id", value: id});
                params_arr.push({name: "dow", value: dow});
                let crt_arr = fncParamsCrt(".form-inp", params_arr);
                if (crt_arr["all_good"]) {
                    $("#btnSave").prop("disabled", true);
                    $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
                    fncMyAjax("upd_object_schedule_day", "objs", crt_arr["params"], 1)
                        .done(function(data){
                            if (!data.sccss) {
                                fncShowFormError(data.msg ?? "Проверьте введённые данные");
                            }
                        })
                        .fail(function(){ fncBtnReset(); })
                        .always(function(){
                            fncBtnReset();
                            fncDowLoad();
                        });
                }
            });

            $("#btnNewBreak").off("click").on("click", function(){
                let schedule_id = $(this).data("schedule-id");

                $("#modalOffcanvasLabel").html("Добавление перерыва");
                $("#modalOffcanvasBody").html(spnr_loading);
                modalOffcanvas.show();
                let path = new URL("./_books_objs/object_info_schedule_main_new_break.php", url);
                $("#modalOffcanvasBody").load(path.href, function(){
                    $("#formBreakNew").submit(function(e){
                        e.preventDefault();
                        e.stopImmediatePropagation();
                        let params_arr = [];
                        params_arr.push({name: "schedule_id", value: schedule_id});
                        let crt_arr = fncParamsCrt(".form-break-inp", params_arr);
                        if (crt_arr["all_good"]) {
                            $("#btnBreakSave").prop("disabled", true);
                            $("#btnBreakSaveText, #divBreakSaveLoading").toggleClass("d-none");
                            fncMyAjax("new_object_schedule_break", "objs", crt_arr["params"], 1)
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
                                    fncDowLoad();
                                });
                        }
                    });
                });
            });
        }

        $(".breakItem").off("click").on("click", async function(){
            let confirmed = await fncConfirm("Удалить перерыв?");
            if (confirmed) {
                fncMyAjax("del_object_schedule_break", "objs", [{name: "id", value: $(this).data("id")}], 1)
                    .always(function(){ fncDowLoad(); });
            }
        });
    });
}
