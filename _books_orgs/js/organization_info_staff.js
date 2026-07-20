$(function(){

    let id       = +$("#hdnOrgId").val();
    let org_type = $("#hdnOrgType").val();

    staffListLoad(id);

    if (!canDo('organizations.manage')) {
        $("#btnNewStaff").hide();
        return;
    }

    $("#btnNewStaff").on("click", function(){
        $("#modalOffcanvasLabel").html("Добавление сотрудника");
        $("#modalOffcanvasBody").html(spnr_loading);
        modalOffcanvas.show();
        let path = new URL("./_books_orgs/organization_info_staff_new.php", url);
        $("#modalOffcanvasBody").load(path.href, {id}, function(){

            $(".inline-tab-info").off("click").on("click", function(){
                $(".inline-tab-info").removeClass("active");
                $(this).addClass("active");
                $(".inline-tab-info-pane").addClass("d-none");
                $($(this).data("target")).removeClass("d-none");
            });

            let phone_mask = $("#inpPhone").data("phone-mask");
            if (phone_mask) $("#inpPhone").mask(phone_mask);

            $(".freeTr").off("click").on("click", async function(){
                let usr_id = +$(this).data("id");
                let confirmed = await fncConfirm("Добавить сотрудника в штат организации?");
                if (confirmed) {
                    fncMyAjax("add_staff_to_organization", "orgs", [
                        {name: "org-id",  value: id},
                        {name: "user-id", value: usr_id}
                    ], 0)
                    .always(function(){
                        staffListLoad(id);
                        modalOffcanvas.hide();
                    });
                }
            });

            $("#formNew").submit(function(e){
                e.preventDefault();
                e.stopImmediatePropagation();
                let params_arr = [];
                params_arr.push({name: "organization-id", value: id});
                let crt_arr = fncParamsCrt(".form-inp", params_arr);
                if (crt_arr["all_good"]) {
                    $("#btnSave").prop("disabled", true);
                    $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
                    fncMyAjax("new", "users", crt_arr["params"], 1)
                        .done(function(data){
                            if (data.sccss) {
                                modalOffcanvas.hide();
                                staffListLoad(id);
                            } else {
                                fncBtnReset();
                                fncShowFormError(data.msg ?? "Проверьте введённые данные");
                            }
                        })
                        .fail(function(){ fncBtnReset(); });
                }
            });

        });
    });

});

// ─────────────────────────────────────────────────────────────────────────────

function staffListLoad(org_id) {
    $("#divStaffList").html(spnr_loading);
    let path = new URL("./_books_orgs/organization_info_staff_list.php", url);
    $("#divStaffList").load(path.href, {id: org_id}, function(){
        $(".staffTr").off("click").on("click", function(){
            let st_id    = +$(this).data("id");
            let user_id  = +$(this).data("user-id");
            let st_name  = $(`.staffName[data-id="${st_id}"]`).clone()
                            .children().remove().end().text().trim();
            let org_type = $("#hdnOrgType").val();

            $("#modalOffcanvasLabel").html(st_name);
            $("#modalOffcanvasBody").html(spnr_loading);
            modalOffcanvas.show();

            let path = new URL("./_books_orgs/organization_info_staff_info.php", url);
            $("#modalOffcanvasBody").load(path.href, {st_id, user_id, org_id, org_type}, function(){

                $(".inline-tab-info").off("click").on("click", function(){
                    $(".inline-tab-info").removeClass("active");
                    $(this).addClass("active");
                    staffTabLoad(st_id, user_id, $(this).data("target"));
                });

                staffTabLoad(st_id, user_id, "main");
            });
        });
    });
}

// ─────────────────────────────────────────────────────────────────────────────

function staffTabLoad(st_id, user_id, target) {
    $(".inline-tab-info").prop("disabled", true);
    $("#divStaffInfoContent").html(spnr_loading);
    let path = new URL(`./_books_orgs/organization_info_staff_info_${target}.php`, url);
    $("#divStaffInfoContent").load(path.href, {st_id, user_id}, function(){
        $(".inline-tab-info").prop("disabled", false);
    });
}
