$(function(){

    let id = +$("#hdnOrgId").val();

    window.currentMoneyStatus = 'active';
    moneyStoragesListLoad(id);

    // Фильтр Актуальные / Все — собственный класс, чтобы не перехватить
    // обработчики .dropdown-item[data-status] раздела позади модалки
    $(".moneyStatusItem").off("click").on("click", function(e){
        e.preventDefault();
        window.currentMoneyStatus = $(this).data("status");
        $("#btnMoneyStatusFilter").text($(this).text());
        moneyStoragesListLoad(id);
    });

    if (!canDo('organizations.manage')) {
        $("#btnNewMoneyStorage").hide();
        return;
    }

    $("#btnNewMoneyStorage").off("click").on("click", function(){
        $("#modalOffcanvasLabel").html("Новое место хранения");
        $("#modalOffcanvasBody").html(spnr_loading);
        fncHideFormError();
        modalOffcanvas.show();
        let path = new URL("./_books_orgs/organization_info_money_new.php", url);
        $("#modalOffcanvasBody").load(path.href, {id}, function(){

            // Смена типа: объект только у наличных, подсказка к названию — своя
            $("input[name='rdoStorageType']").off("change").on("change", function(){
                let is_cash = $(this).val() === "cash_place";
                $("#divStorageObject").toggleClass("d-none", !is_cash);
                $("#divStorageNameHint").text(is_cash
                    ? "Например «Сейф офиса», «У владельца»"
                    : "Например «Карта Сбер *4774»");
            });

            $("#formNew").off("submit").on("submit", function(e){
                e.preventDefault();
                e.stopImmediatePropagation();
                let storage_type = $("input[name='rdoStorageType']:checked").val();
                let params_arr = [];
                params_arr.push({name: "org-id",         value: id});
                params_arr.push({name: "storage-type",   value: storage_type});
                params_arr.push({name: "storage-object", value: storage_type === "cash_place" ? $("#slctStorageObject").val() : 0});
                let crt_arr = fncParamsCrt(".form-inp", params_arr);
                if (crt_arr["all_good"]) {
                    $("#btnSave").prop("disabled", true);
                    $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
                    fncMyAjax("new_organization_money_storage", "orgs", crt_arr["params"], 1)
                        .done(function(data){
                            if (data.sccss) {
                                modalOffcanvas.hide();
                                moneyStoragesListLoad(id);
                            } else {
                                fncBtnReset();
                                fncShowFormError(data.msg ?? "Не удалось сохранить");
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

function moneyStoragesListLoad(id) {
    $("#divMoneyStoragesList").html(spnr_loading);
    let path = new URL("./_books_orgs/organization_info_money_list.php", url);
    $("#divMoneyStoragesList").load(path.href, {id, status: window.currentMoneyStatus}, function(){
      $(".moneyStorageTr").off("click").on("click", function(){
          moneyStorageInfoLoad(id, +$(this).data("id"));
      });
    });
}

// ─────────────────────────────────────────────────────────────────────────────

function moneyStorageInfoLoad(id, storage_id) {
    let storage_name = $(`.moneyStorageName[data-id="${storage_id}"]`).first().text().trim();
    $("#modalOffcanvasLabel").text(storage_name);
    $("#modalOffcanvasBody").html(spnr_loading);
    fncHideFormError();
    modalOffcanvas.show();
    let path = new URL("./_books_orgs/organization_info_money_info.php", url);
    $("#modalOffcanvasBody").load(path.href, {id, storage_id}, function(){

        // Живое обновление подписи переключателя
        $("#chckStorageActive").off("change").on("change", function(){
            $("#lblStorageActive").text($(this).prop("checked") ? "Действующее" : "Архивное");
        });

        if (!canDo('organizations.manage')) {
            $("#btnSave").hide();
            $("#formInfo").off("submit");
            return;
        }

        $("#formInfo").off("submit").on("submit", function(e){
            e.preventDefault();
            e.stopImmediatePropagation();
            let params_arr = [];
            params_arr.push({name: "org-id",         value: id});
            params_arr.push({name: "storage-id",     value: storage_id});
            params_arr.push({name: "storage-object", value: $("#slctStorageObject").length ? $("#slctStorageObject").val() : 0});
            let crt_arr = fncParamsCrt(".form-inp", params_arr);
            if (crt_arr["all_good"]) {
                $("#btnSave").prop("disabled", true);
                $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
                fncMyAjax("upd_organization_money_storage", "orgs", crt_arr["params"], 1)
                    .done(function(data){
                        if (data.sccss) {
                            modalOffcanvas.hide();
                            moneyStoragesListLoad(id);
                        } else {
                            fncBtnReset();
                            fncShowFormError(data.msg ?? "Не удалось сохранить");
                        }
                    })
                    .fail(function(){
                        fncBtnReset();
                    });
            }
        });

    });
}
