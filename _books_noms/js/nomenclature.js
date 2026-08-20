$(function(){

    $("#btnToggleFilters").off("click").on("click", function(){
        $("#divFiltersGroup").toggleClass("show");
    });

    window.currentStatus = "active";

    $(".dropdown-item[data-status]").off("click").on("click", function(e){
        e.preventDefault();
        window.currentStatus = $(this).data("status");
        $("#btnStatusFilter").html($(this).text());
        listLoadFunction();
    });

    $("#slctGroupFilter").off("change").on("change", function(){
        listLoadFunction();
    });

    if (!canDo('nomenclature.manage')) {
        $("#btnFastNew").hide();
    }

    listLoadFunction();

    if (canDo('nomenclature.manage')) {
        $("#btnFastNew").off("click").on("click", function(){
            $("#mainModalLabel").html("Новая позиция номенклатуры");
            $("#mainModalBody").html(spnr_loading);
            $("#mainModal").removeClass("modal-xl");
            fncHideFormError();
            main_modal.show();
            let path = new URL("./_books_noms/nomenclature_new.php", url);
            $("#mainModalBody").load(path.href, function(){
                $("#formNew").submit(function(e){
                    e.preventDefault();
                    e.stopImmediatePropagation();

                    let product_type_value = $("input[name='productTypeRadio']:checked").val();
                    if (product_type_value === undefined) {
                        fncShowFormError("Укажите, является ли позиция пищевой продукцией");
                        return;
                    }

                    let params_arr = [];
                    params_arr.push({name: "section", value: "purchased"});
                    params_arr.push({name: "product_type", value: product_type_value});
                    let crt_arr = fncParamsCrt(".form-inp", params_arr);
                    if (crt_arr["all_good"]) {
                        $("#btnSave").prop("disabled", true);
                        $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
                        fncMyAjax("new_nomenclature", "noms", crt_arr["params"], 1)
                            .done(function(data){
                                if (data.sccss) {
                                    $("#mainModal").one("hidden.bs.modal", function(){
                                        localStorage.setItem('new_item', data.id);
                                        listLoadFunction();
                                    });
                                    main_modal.hide();
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
    }

});

// ─────────────────────────────────────────────────────────────────────────────

function listLoadFunction() {
    let group_id = $("#slctGroupFilter").val() || "";
    let status = window.currentStatus || "active";

    $("#divChptContent").html(spnr_loading);
    let path = new URL("./_books_noms/nomenclature_list.php", url);
    $("#divChptContent").load(path.href, {section: "purchased", group_id: group_id, status: status}, function(){
        searchFunction();
        $(".itemTr").off("click").on("click", function(){
            infoLoadFunction(+$(this).data("id"));
        });
        fncCheckNewItem(function(id){ infoLoadFunction(id, "general"); });
    });
}

// ─────────────────────────────────────────────────────────────────────────────

function infoLoadFunction(item_id, target = "prices") {
    let item_name = $(`.itemName[data-id="${item_id}"]`).first().text().trim();
    $("#mainModalBody").html(spnr_loading);
    $("#mainModalLabel").html(item_name);
    fncHideFormError();
    $("#mainModal").one("shown.bs.modal", function(){
        let $active_tab = $(`.inline-tab[data-target=${target}]`);
        if ($active_tab.length) {
            $active_tab[0].scrollIntoView({ behavior: "smooth", block: "nearest", inline: "center" });
        }
    });
    main_modal.show();
    let path = new URL("./_books_noms/nomenclature_info.php", url);
    $("#mainModalBody").load(path.href, {id: item_id}, function(){
        $(".inline-tab").off("click").on("click", function(){
            fncNomenclatureTabLoad(item_id, $(this).data("target"));
        });
        fncNomenclatureTabLoad(item_id, target);
    });
}

// ─────────────────────────────────────────────────────────────────────────────

function fncNomenclatureTabLoad(item_id, target) {
    $(".inline-tab").removeClass("active");
    let $active_tab = $(`.inline-tab[data-target=${target}]`);
    $active_tab.addClass("active");
    $(".inline-tab").prop("disabled", true);
    $("#divNomenclatureInfoContent").html(spnr_loading);
    let path = new URL(`./_books_noms/nomenclature_info_${target}.php`, url);
    $("#divNomenclatureInfoContent").load(path.href, {id: item_id}, function(){
        $(".inline-tab").prop("disabled", false);
    });
}
