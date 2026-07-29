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
            let obj_name = $(`.itemName[data-id="${obj_id}"]`).first().text().trim();
            $("#modalOffcanvasLabel").html(obj_name);
            $("#modalOffcanvasBody").html(spnr_loading);
            modalOffcanvas.show();
            // При необходимости загружаем детали из модуля Объекты
            // let path = new URL("../_books_objs/object_info_main.php", url);
            // $("#modalOffcanvasBody").load(path.href, {id: obj_id});
        });
    });
}
