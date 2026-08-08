$(function(){

    let org_id   = +$("#hdnOrgId").val();
    let org_type = $("#hdnOrgType").val();

    contactsListLoad(org_id);

    if (!canDo('organizations.manage')) {
        $("#btnNewContact").hide();
        return;
    }

    $("#btnNewContact").on("click", function(){
        $("#modalOffcanvasLabel").html("Новый контакт");
        $("#modalOffcanvasBody").html(spnr_loading);
        modalOffcanvas.show();
        let path = new URL("./_books_orgs/organization_info_contacts_new.php", url);
        $("#modalOffcanvasBody").load(path.href, function(){
            $("#formNew").submit(function(e){
                e.preventDefault();
                e.stopImmediatePropagation();
                let params_arr = [];
                params_arr.push({name: "org-id", value: org_id});
                let crt_arr = fncParamsCrt(".form-inp", params_arr);
                if (crt_arr["all_good"]) {
                    $("#btnSave").prop("disabled", true);
                    $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
                    fncMyAjax("new_organization_contact", "orgs", crt_arr["params"], 0)
                        .done(function(){
                            modalOffcanvas.hide();
                            contactsListLoad(org_id);
                        })
                        .fail(function(){ fncBtnReset(); });
                }
            });
        });
    });

});

// ─────────────────────────────────────────────────────────────────────────────

function contactsListLoad(org_id) {
    $("#divContactsList").html(spnr_loading);
    let path = new URL("./_books_orgs/organization_info_contacts_list.php", url);
    $("#divContactsList").load(path.href, {id: org_id}, function(){
        $(".contactTr").off("click").on("click", function(){
            let contact_id   = +$(this).data("id");
            let contact_name = $(`.itemName[data-id="${contact_id}"]`).first().text().trim();
            $("#modalOffcanvasLabel").html(contact_name);
            $("#modalOffcanvasBody").html(spnr_loading);
            modalOffcanvas.show();
            let path = new URL("./_books_orgs/organization_info_contacts_info.php", url);
            $("#modalOffcanvasBody").load(path.href, {id: contact_id}, function(){

                if (!canDo('organizations.manage')) {
                    $("#btnSave, #btnDelete").hide();
                    $("#formInfo").off("submit");
                    return;
                }

                $("#formInfo").submit(function(e){
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    let params_arr = [];
                    params_arr.push({name: "contact-id", value: contact_id});
                    let crt_arr = fncParamsCrt(".form-inp", params_arr);
                    if (crt_arr["all_good"]) {
                        $("#btnSave").prop("disabled", true);
                        $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
                        fncMyAjax("upd_organization_contact", "orgs", crt_arr["params"], 0)
                            .done(function(){
                                modalOffcanvas.hide();
                                contactsListLoad(org_id);
                            })
                            .fail(function(){ fncBtnReset(); });
                    }
                });

                $("#btnDelete").on("click", async function(){
                    let confirmed = await fncConfirm("Удалить контактное лицо?");
                    if (confirmed) {
                        fncMyAjax("del_organization_contact", "orgs", [
                            {name: "contact-id", value: contact_id}
                        ], 0)
                        .always(function(){
                            modalOffcanvas.hide();
                            contactsListLoad(org_id);
                        });
                    }
                });

            });
        });
    });
}
