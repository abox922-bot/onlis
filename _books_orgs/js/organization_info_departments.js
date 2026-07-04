$(function(){

    let id       = +$("#hdnOrgId").val();
    let org_type = $("#hdnOrgType").val();

    depsListLoad(id);

    if (!canDo('organizations.manage')) {
        $("#btnNewDep").hide();
        return;
    }

    $("#btnNewDep").on("click", function(){
        $("#modalOffcanvasLabel").html("Новый отдел");
        $("#modalOffcanvasBody").html(spnr_loading);
        modalOffcanvas.show();
        let path = new URL("./_books_orgs/organization_info_departments_new.php", url);
        $("#modalOffcanvasBody").load(path.href, function(){
            $("#formNew").submit(function(e){
                e.preventDefault();
                e.stopImmediatePropagation();
                let params_arr = [];
                params_arr.push({name: "org-id", value: id});
                let crt_arr = fncParamsCrt(".form-inp", params_arr);
                if (crt_arr["all_good"]) {
                    $("#btnSave").prop("disabled", true);
                    $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
                    fncMyAjax("new_organization_department", "orgs", crt_arr["params"], 0)
                        .done(function(){
                            modalOffcanvas.hide();
                            depsListLoad(id);
                        })
                        .fail(function(){
                            fncBtnReset();
                        });
                }
            });
        });
    });

});

// ─────────────────────────────────────────────────────────────────────────────

function depsListLoad(id) {
    $("#divDepsList").html(spnr_loading);
    let path = new URL("./_books_orgs/organization_info_departments_list.php", url);
    $("#divDepsList").load(path.href, {id}, function(){
        $(".depTr").off("click").on("click", function(){
            let dep_id = +$(this).data("id");
            let dep_name = $(`.itemName[data-id="${dep_id}"]`).html();
            $("#modalOffcanvasLabel").html(dep_name);
            $("#modalOffcanvasBody").html(spnr_loading);
            modalOffcanvas.show();
            let path = new URL("./_books_orgs/organization_info_departments_info.php", url);
            $("#modalOffcanvasBody").load(path.href, {id: dep_id}, function(){

                // Живое обновление лейбла чекбокса
                $("#chckDepActive").on("change", function(){
                    $("#lblDepActive").html($(this).prop("checked") ? "Активный" : "Архивный");
                });

                if (!canDo('organizations.manage')) {
                    $("#btnSave").hide();
                    $("#formInfo").off("submit");
                    return;
                }

                $("#formInfo").submit(function(e){
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    let params_arr = [];
                    params_arr.push({name: "dep-id", value: dep_id});
                    let crt_arr = fncParamsCrt(".form-inp", params_arr);
                    if (crt_arr["all_good"]) {
                        $("#btnSave").prop("disabled", true);
                        $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
                        fncMyAjax("upd_organization_department", "orgs", crt_arr["params"], 0)
                            .done(function(){
                                modalOffcanvas.hide();
                                depsListLoad(id);
                            })
                            .fail(function(){
                                fncBtnReset();
                            });
                    }
                });
            });
        });
    });
}
