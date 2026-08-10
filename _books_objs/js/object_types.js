$(function(){

    if (!canDo('objects.manage')) {
        $("#btnFastNew").hide();
    }

    listLoadFunction();

    if (canDo('objects.manage')) {
        $("#btnFastNew").on("click", function(){
            $("#mainModalBody").html(spnr_loading);
            $("#mainModalLabel").html("Новый тип объектов");
            $("#mainModal").removeClass("modal-xl");
            fncHideFormError();
            main_modal.show();
            let path = new URL("./_books_objs/object_types_new.php", url);
            $("#mainModalBody").load(path.href, function(){

                systemToggle();

                $("#formNew").submit(function(e){
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    let params_arr = [];
                    let crt_arr = fncParamsCrt(".form-inp", params_arr);
                    if (crt_arr["all_good"]) {
                        $("#btnSave").prop("disabled", true);
                        $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
                        fncMyAjax("new_object_type", "objs", crt_arr["params"], 1)
                            .done(function(data){
                                if (data.sccss) {
                                    main_modal.hide();
                                } else {
                                    fncBtnReset();
                                    fncShowFormError(data.msg ?? "Проверьте введённые данные");
                                }
                            })
                            .fail(function(){ fncBtnReset(); })
                            .always(function(){ listLoadFunction(); });
                    }
                });

            });
        });
    }

});

// ─────────────────────────────────────────────────────────────────────────────

function listLoadFunction() {
    $("#divChptContent").html(spnr_loading);
    let path = new URL("./_books_objs/object_types_list.php", url);
    $("#divChptContent").load(path.href, function(){
        searchFunction();
        $(".itemTr").off("click").on("click", function(){
            infoLoadFunction($(this).data("id"));
        });
    });
}

// ─────────────────────────────────────────────────────────────────────────────

function infoLoadFunction(item_id) {
    let item_name = $(`.itemName[data-id="${item_id}"]`).first().text().trim();
    $("#mainModalBody").html(spnr_loading);
    $("#mainModalLabel").html(item_name);
    $("#mainModal").removeClass("modal-xl");
    fncHideFormError();
    main_modal.show();
    let path = new URL("./_books_objs/object_types_info.php", url);
    $("#mainModalBody").load(path.href, {id: item_id}, function(){

        $(".inline-tab").off("click").on("click", function(){
            $(".inline-tab").removeClass("active");
            $(this).addClass("active");
            fncTypeTabLoad(item_id, $(this).data("target"));
        });

        fncTypeTabLoad(item_id, "main");

    });
}

// ─────────────────────────────────────────────────────────────────────────────

function fncTypeTabLoad(type_id, target) {
    $(".inline-tab").prop("disabled", true);
    $("#divTypeInfoContent").html(spnr_loading);
    let path = new URL(`./_books_objs/object_types_info_${target}.php`, url);
    $("#divTypeInfoContent").load(path.href, {id: type_id}, function(){
        $(".inline-tab").prop("disabled", false);

        if (target === "main") {
            systemToggle();

            if (!canDo('objects.manage')) {
                $("#btnSave").hide();
                $("#formInfo").off("submit");
            } else {
                $("#formInfo").submit(function(e){
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    let params_arr = [];
                    params_arr.push({name: "id", value: type_id});
                    let crt_arr = fncParamsCrt(".form-inp", params_arr);
                    if (crt_arr["all_good"]) {
                        $("#btnSave").prop("disabled", true);
                        $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
                        fncMyAjax("upd_object_type", "objs", crt_arr["params"], 1)
                            .done(function(data){
                                if (!data.sccss) {
                                    fncShowFormError(data.msg ?? "Проверьте введённые данные");
                                }
                            })
                            .fail(function(){ fncBtnReset(); })
                            .always(function(){
                                fncBtnReset();
                                listLoadFunction();
                            });
                    }
                });
            }
        }

        if (target === "workstations") {
            typeWorkstationsListLoad(type_id);

            $("#btnWorkstationNew").off("click").on("click", function(){
                $("#modalOffcanvasBody").html(spnr_loading);
                $("#modalOffcanvasLabel").html("Добавление станции");
                modalOffcanvas.show();
                let path = new URL("./_books_objs/object_type_workstations_new.php", url);
                $("#modalOffcanvasBody").load(path.href, {type_id}, function(){
                    $(".freeWorkstationTr").off("click").on("click", async function(){
                        let workstation_id = $(this).data("id");
                        let confirmed = await fncConfirm("Добавить станцию к типу?");
                        if (confirmed) {
                            fncMyAjax("new_object_type_workstation", "objs", [
                                {name: "type_id", value: type_id},
                                {name: "workstation_id", value: workstation_id}
                            ], 1)
                            .always(function(){
                                modalOffcanvas.hide();
                                typeWorkstationsListLoad(type_id);
                            });
                        }
                    });
                });
            });
        }

    });
}

// ─────────────────────────────────────────────────────────────────────────────

function typeWorkstationsListLoad(type_id) {
    $("#divWorkstationsList").html(spnr_loading);
    let path = new URL("./_books_objs/object_type_workstations_list.php", url);
    $("#divWorkstationsList").load(path.href, {type_id}, function(){
        $(".itemWorkstationTr").off("click").on("click", async function(){
            let confirmed = await fncConfirm("Убрать станцию из типа?");
            if (confirmed) {
                fncMyAjax("del_object_type_workstation", "objs", [{name: "id", value: $(this).data("id")}], 1)
                    .always(function(){ typeWorkstationsListLoad(type_id); });
            }
        });
    });
}

// ─────────────────────────────────────────────────────────────────────────────

function systemToggle() {
    $("#chckIsSystem").off("change").on("change", function(){
        if ($(this).prop("checked")) {
            $("#rowOrganization").addClass("d-none");
            $("#slctOrganization").val("0").removeClass("form-inp");
        } else {
            $("#rowOrganization").removeClass("d-none");
            $("#slctOrganization").addClass("form-inp");
        }
    });
}
