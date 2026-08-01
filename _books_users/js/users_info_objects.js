$(function(){
    let user_id = +$("#hdnUserId").val();

    $(".objTr").off("click").on("click", function(){
        let staff_id = +$(this).data("id");
        let obj_name = $(this).find(".objName").text().trim();

        $("#modalOffcanvasLabel").html(obj_name);
        $("#modalOffcanvasBody").html(spnr_loading);
        modalOffcanvas.show();

        let path = new URL("../_books_objs/object_info_staff_info_main.php", url);
        $("#modalOffcanvasBody").load(path.href, {staff_id}, function(){
            fncInitObjectStaffMainForm(staff_id, function(){
                fncUserTabLoad(user_id, "objects");
                modalOffcanvas.hide();
            });
        });
    });

    $("#btnAddObject").off("click").on("click", async function(){
        $("#modalOffcanvasLabel").html("Привязать к объекту");
        $("#modalOffcanvasBody").html(spnr_loading);
        modalOffcanvas.show();

        let candidates = await fncMyAjax("user_object_candidates", "users", [
            {name: "user_id", value: user_id}
        ], 1);
        if (!Array.isArray(candidates)) candidates = [];

        let html = '<div class="row"><div class="col-12">';
        if (candidates.length === 0) {
            html += '<div class="empty-hint"><i class="bi bi-shop empty-hint__icon"></i>' +
                    '<div class="empty-hint__text">Нет доступных объектов</div></div>';
        } else {
            html += '<table class="table table-sm table-hover mt-2"><tbody>';
            candidates.forEach(function(o){
                html += `<tr class="listTr freeObjectTr" data-id="${o.id}"><td class="py-2">${o.name}</td></tr>`;
            });
            html += '</tbody></table>';
        }
        html += '</div></div>';
        $("#modalOffcanvasBody").html(html);

        $(".freeObjectTr").off("click").on("click", async function(){
            let object_id = +$(this).data("id");
            let confirmed = await fncConfirm("Привязать сотрудника к этому объекту?");
            if (!confirmed) return;
            fncMyAjax("new_object_staff", "objs", [
                {name: "object_id", value: object_id},
                {name: "user_id", value: user_id}
            ], 1)
            .always(function(){
                modalOffcanvas.hide();
                fncUserTabLoad(user_id, "objects");
            });
        });
    });
});
