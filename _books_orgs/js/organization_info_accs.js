$(function(){

    let id       = +$("#hdnOrgId").val();
    let org_type = $("#hdnOrgType").val();

    accsListLoad(id);

    if (!canDo('organizations.manage')) {
        $("#btnNewAcc").hide();
        return;
    }

    $("#btnNewAcc").on("click", function(){
        $("#modalOffcanvasLabel").html("Новый счёт");
        $("#modalOffcanvasBody").html(spnr_loading);
        modalOffcanvas.show();
        let path = new URL("./_books_orgs/organization_info_accs_new.php", url);
        $("#modalOffcanvasBody").load(path.href, {id}, function(){

            $("#formNew").submit(function(e){
                e.preventDefault();
                e.stopImmediatePropagation();
                let params_arr = [];
                params_arr.push({name: "org-id", value: id});
                let crt_arr = fncParamsCrt(".form-inp", params_arr);
                if (crt_arr["all_good"]) {
                    $("#btnSave").prop("disabled", true);
                    $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
                    fncMyAjax("new_organization_bank_account", "orgs", crt_arr["params"], 1)
                        .done(function(data){
                            if (data.sccss) {
                                modalOffcanvas.hide();
                                accsListLoad(id);
                            } else {
                                fncBtnReset();
                                fncShowFormError(data.msg ?? "Номер счёта не уникален");
                            }
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

function accsListLoad(id) {
    $("#divAccsList").html(spnr_loading);
    let path = new URL("./_books_orgs/organization_info_accs_list.php", url);
    $("#divAccsList").load(path.href, {id}, function(){

        // Переключение активности счёта
        $(".accActChck").off("change").on("change", function(){
            let acc_id = +$(this).data("id");
            let is_active = $(this).prop("checked") ? 1 : 0;
            fncMyAjax("toggle_bank_account_active", "orgs", [
                {name: "acc-id",    value: acc_id},
                {name: "is-active", value: is_active}
            ], 0)
            .always(function(){
                accsListLoad(id);
            });
        });

        // Клик по строке — копирование реквизитов в буфер
        $(".accTr").off("click").on("click", function(e){
            if (!e.target.closest(".form-switch")) {
                let acc_id = +$(this).data("id");
                let text = $("#mainModalLabel").html().trim() + "\n";
                $(`.bankReq[data-acc="${acc_id}"]`).each(function(){
                    text += $(this).html() + "\n";
                });
                navigator.clipboard.writeText(text).then(function(){
                    // можно добавить toast в будущем
                });
            }
        });

    });
}
