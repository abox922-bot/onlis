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

    if (!canDo('products.manage')) {
        $("#btnFastNew").hide();
    }

    listLoadFunction();

    if (canDo('products.manage')) {
        $("#btnFastNew").off("click").on("click", function(){
            $("#mainModalLabel").html("Новый товар");
            $("#mainModalBody").html(spnr_loading);
            $("#mainModal").removeClass("modal-xl");
            fncHideFormError();
            main_modal.show();
            let path = new URL("./_books_products/products_new.php", url);
            $("#mainModalBody").load(path.href, function(){

                $("input[name='productTypeRadio']").off("change").on("change", function(){
                    let type_value = $(this).val();
                    if (type_value === "service") {
                        $("#divUnitWrap").addClass("d-none");
                        $("#slctUnit").removeAttr("data-required");
                    } else {
                        $("#divUnitWrap").removeClass("d-none");
                        $("#slctUnit").attr("data-required", "1");
                    }
                });

                $("#formNew").submit(function(e){
                    e.preventDefault();
                    e.stopImmediatePropagation();

                    let product_type_value = $("input[name='productTypeRadio']:checked").val();
                    if (product_type_value === undefined) {
                        fncShowFormError("Укажите тип товара");
                        return;
                    }

                    let params_arr = [];
                    params_arr.push({name: "product_type", value: product_type_value});
                    let crt_arr = fncParamsCrt(".form-inp", params_arr);
                    if (crt_arr["all_good"]) {
                        $("#btnSave").prop("disabled", true);
                        $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
                        fncMyAjax("new_product", "noms", crt_arr["params"], 1)
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
    let path = new URL("./_books_products/products_list.php", url);
    $("#divChptContent").load(path.href, {group_id: group_id, status: status}, function(){
        searchFunction();
        $(".itemTr").off("click").on("click", function(){
            infoLoadFunction(+$(this).data("id"));
        });
        $(".tree-toggle[data-options-toggle]").off("click").on("click", function(e){
            e.stopPropagation();
            fncToggleProductOptions($(this));
        });
        fncCheckNewItem(function(id){ infoLoadFunction(id, "general"); });
    });
}

// ─────────────────────────────────────────────────────────────────────────────

function fncToggleProductOptions($toggle) {
    let nomenclature_id = $toggle.data("options-toggle");
    let $container = $(`tr[data-options-container="${nomenclature_id}"]`);
    let $content = $(`[data-options-content="${nomenclature_id}"]`);

    $toggle.toggleClass("bi-chevron-right bi-chevron-down");
    $container.toggleClass("d-none");

    if (!$container.hasClass("d-none") && $content.is(":empty")) {
        $content.html(spnr_loading);
        let path = new URL("./_books_products/products_list_options.php", url);
        $content.load(path.href, {nomenclature_id: nomenclature_id}, function(){
            $(this).find(".itemOptionName").off("click").on("click", function(){
                infoLoadFunction(+$(this).data("id"));
            });
            $(this).find(".tree-toggle:not([data-options-toggle])").off("click").on("click", function(e){
                e.stopPropagation();
                fncToggleOptionNode($(this));
            });
        });
    }
}

// ─────────────────────────────────────────────────────────────────────────────

function infoLoadFunction(item_id, target = "general") {
    let item_name = $(`.itemName[data-id="${item_id}"], .itemOptionName[data-id="${item_id}"]`).first().text().trim();
    $("#mainModalBody").html(spnr_loading);
    $("#mainModalLabel").html(item_name);
    fncHideFormError();
    main_modal.show();
    let path = new URL("./_books_products/products_info.php", url);
    $("#mainModalBody").load(path.href, {id: item_id}, function(){
        $(".inline-tab").off("click").on("click", function(){
            fncProductTabLoad(item_id, $(this).data("target"));
        });
        fncProductTabLoad(item_id, target);
    });
}

// ─────────────────────────────────────────────────────────────────────────────

function fncProductTabLoad(item_id, target) {
    $(".inline-tab").removeClass("active");
    $(`.inline-tab[data-target=${target}]`).addClass("active");
    $(".inline-tab").prop("disabled", true);
    $("#divProductInfoContent").html(spnr_loading);
    let path = new URL(`./_books_products/products_info_${target}.php`, url);
    $("#divProductInfoContent").load(path.href, {id: item_id}, function(){
        $(".inline-tab").prop("disabled", false);
    });
}
