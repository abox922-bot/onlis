$(function(){

    let id       = +$("#hdnOrgId").val();
    let org_type = $("#hdnOrgType").val();

    posListLoad(id);

    if (!canDo('organizations.manage')) {
        $("#btnNewPos").hide();
        return;
    }

    $("#btnNewPos").on("click", function(){
        $("#modalOffcanvasLabel").html("Новая должность");
        $("#modalOffcanvasBody").html(spnr_loading);
        modalOffcanvas.show();
        let path = new URL("./_books_orgs/organization_info_positions_new.php", url);
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
                    fncMyAjax("new_organization_position", "orgs", crt_arr["params"], 0)
                        .done(function(){
                            modalOffcanvas.hide();
                            posListLoad(id);
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

function posListLoad(id) {
    $("#divPosList").html(spnr_loading);
    let path = new URL("./_books_orgs/organization_info_positions_list.php", url);
    $("#divPosList").load(path.href, {id}, function(){
        $(".posTr").off("click").on("click", function(){
            let pos_id   = +$(this).data("id");
            let pos_name = $(`.itemName[data-id="${pos_id}"]`).html();
            $("#modalOffcanvasLabel").html(pos_name);
            $("#modalOffcanvasBody").html(spnr_loading);
            modalOffcanvas.show();
            let path = new URL("./_books_orgs/organization_info_positions_info.php", url);
            $("#modalOffcanvasBody").load(path.href, {id: pos_id}, function(){

                $("#chckPosActive").on("change", function(){
                    $("#lblPosActive").html($(this).prop("checked") ? "Активная" : "Архивная");
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
                    params_arr.push({name: "pos-id", value: pos_id});
                    let crt_arr = fncParamsCrt(".form-inp", params_arr);
                    if (crt_arr["all_good"]) {
                        $("#btnSave").prop("disabled", true);
                        $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
                        fncMyAjax("upd_organization_position", "orgs", crt_arr["params"], 0)
                            .done(function(){
                                modalOffcanvas.hide();
                                posListLoad(id);
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
