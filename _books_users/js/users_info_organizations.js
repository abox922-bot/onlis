$(function(){

    let user_id = +$("#hdnUserId").val();

    $(".orgTr").off("click").on("click", function(){
        let st_id = +$(this).data("id");
        let org_name = $(this).find(".orgName").text().trim();
        $("#modalOffcanvasLabel").html(org_name);
        $("#modalOffcanvasBody").html(spnr_loading);
        modalOffcanvas.show();
        let path = new URL("../_books_orgs/organization_info_staff_info_main.php", url);
        $("#modalOffcanvasBody").load(path.href, {st_id}, function(){
            fncInitStaffMainForm(st_id, function(){
                fncUserTabLoad(user_id, "organizations");
            });
        });
    });

    $("#btnAddOrg").off("click").on("click", async function(){
        $("#modalOffcanvasLabel").html("Привязать к организации");
        $("#modalOffcanvasBody").html(spnr_loading);
        modalOffcanvas.show();

        let orgs_result = await fncMyAjax("organizations_list", "orgs", [
            {name: "org_type", value: "all"}
        ], 1);

        if (!Array.isArray(orgs_result)) orgs_result = [];

        let already_org_ids = $(".orgTr").map(function(){
            return +$(this).data("org-id");
        }).get();

        let free_orgs = orgs_result.filter(function(o){
            return !already_org_ids.includes(+o.id);
        });

        let html = '<div class="row"><div class="col-12">';
        if (free_orgs.length === 0) {
            html += '<div class="empty-hint"><i class="bi bi-building empty-hint__icon"></i>' +
                    '<div class="empty-hint__text">Нет доступных организаций</div></div>';
        } else {
            html += '<table class="table table-sm table-hover mt-2"><tbody>';
            free_orgs.forEach(function(o){
                html += `<tr class="listTr freeOrgTr" data-id="${o.id}"><td class="py-2">${o.display_name}</td></tr>`;
            });
            html += '</tbody></table>';
        }
        html += '</div></div>';

        $("#modalOffcanvasBody").html(html);

        $(".freeOrgTr").off("click").on("click", async function(){
            let organization_id = +$(this).data("id");
            let confirmed = await fncConfirm("Привязать сотрудника к этой организации?");
            if (!confirmed) return;

            fncMyAjax("add_staff_to_organization", "orgs", [
                {name: "org-id",  value: organization_id},
                {name: "user-id", value: user_id}
            ], 0)
            .always(function(){
                modalOffcanvas.hide();
                fncUserTabLoad(user_id, "organizations");
            });
        });
    });

});
