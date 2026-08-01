$(function(){
    let org_id = +$("#hdnOrgId").val();
    objectsListLoad(org_id);
});

// ─────────────────────────────────────────────────────────────────────────────

function objectsListLoad(org_id) {
    $("#divObjectsList").html(spnr_loading);
    let path = new URL("./_books_orgs/organization_info_objects_list.php", url);
    $("#divObjectsList").load(path.href, {id: org_id}, function(){
        $(".objTr").off("click").on("click", function(){
            let obj_id   = +$(this).data("id");
            let obj_name = $(`.objName[data-id="${obj_id}"]`).first().text().trim();
            $("#modalOffcanvasLabel").html(obj_name);
            $("#modalOffcanvasBody").html(spnr_loading);
            modalOffcanvas.show();
            let path = new URL("./_books_orgs/organization_info_objects_info.php", url);
            $("#modalOffcanvasBody").load(path.href, {id: obj_id}, function(){
                let id = $("#inpObjectId").val();
                let obj_path = new URL("../_books_objs/object_info_main.php", url);
                $("#divObjectInfoContent").html(spnr_loading);
                $("#divObjectInfoContent").load(obj_path.href, {id}, function(){
                    fncInitObjectMainForm(id, function(){
                        modalOffcanvas.hide();
                        objectsListLoad(org_id);
                    });
                });
            });
        });
    });
}
