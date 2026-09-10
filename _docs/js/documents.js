$(function(){

    $("#btnToggleFilters").off("click").on("click", function(){
        $("#divFiltersGroup").toggleClass("show");
    });

    $("#inpDateFrom, #inpDateTo, #slctTypeFilter, #slctWarehouseFilter").off("change").on("change", function(){
        fncValidateDateRange();
        listLoadFunction();
    });

    listLoadFunction();

    $("#btnFastNew").off("click").on("click", function(){
        $("#mainModalLabel").html("Новый документ");
        $("#mainModalBody").html(spnr_loading);
        $("#mainModal").removeClass("modal-xl");
        fncHideFormError();
        main_modal._config.backdrop = false;
        main_modal.show();
        let path = new URL("./_docs/documents_new.php", url);
        $("#mainModalBody").load(path.href, function(){
            fncInitDocumentForm();
        });
    });

});

// ─────────────────────────────────────────────────────────────────────────────

function fncValidateDateRange() {
    let date_from = $("#inpDateFrom").val();
    let date_to   = $("#inpDateTo").val();

    if (date_from && date_to && date_from > date_to) {
        $("#inpDateTo").val(date_from);
    }
}

// ─────────────────────────────────────────────────────────────────────────────

function listLoadFunction() {
    let date_from    = $("#inpDateFrom").val();
    let date_to      = $("#inpDateTo").val();
    let type_code    = $("#slctTypeFilter").val();
    let warehouse_id = $("#slctWarehouseFilter").val();

    $("#inpDateFrom").toggleClass("is-invalid", !date_from);
    $("#inpDateTo").toggleClass("is-invalid", !date_to);

    if (!date_from || !date_to) {
        return;
    }

    function fncLoadList() {
        $("#divChptContent").html(spnr_loading);
        let path = new URL("./_docs/documents_list.php", url);
        $("#divChptContent").load(path.href, {date_from, date_to, type_code, warehouse_id}, function(){
            $(".itemTr").off("click").on("click", function(){
                infoLoadFunction(+$(this).data("id"));
            });
            fncCheckNewItem(infoLoadFunction);
        });
    }

    if ($("#mainModal").hasClass("show")) {
        $("#mainModal").one("hidden.bs.modal", fncLoadList);
        main_modal.hide();
    } else {
        fncLoadList();
    }
}

// ─────────────────────────────────────────────────────────────────────────────

function fncInitDocumentForm() {
    let types_arr = [];
    let warehouses_arr = [];

    $("#slctDocumentType option[value!='0']").each(function(){
        types_arr.push({
            code: $(this).val(),
            direction: $(this).data("direction"),
            warehouse_count: +$(this).data("warehouse-count"),
            warehouse_field: $(this).data("warehouse-field"),
            numbering_mode: $(this).data("numbering-mode"),
        });
    });

    $("#slctWarehouseOut option[value!='0']").each(function(){
        warehouses_arr.push({
            id: +$(this).val(),
            organization_id: +$(this).data("organization"),
            name: $(this).text(),
        });
    });
    $("#slctWarehouseOut option[value!='0'], #slctWarehouseIn option[value!='0']").remove();

    window.documentCounterpartyPicker = new TomSelect("#slctCounterparty", {
        maxOptions: null,
        plugins: ["clear_button"],
        onChange: function(){
            window.documentCounterpartyPicker.blur();
        }
    });

    $("#slctOrganization").off("change").on("change", function(){
        fncFillWarehouseSelects(+$(this).val(), warehouses_arr);
    });

    $("#slctDocumentType").off("change").on("change", function(){
        let selected_type = types_arr.find(t => t.code === $(this).val());
        fncToggleDocumentFields(selected_type);
    });

    $("#slctWarehouseOut, #slctWarehouseIn").off("change").on("change", function(){
        fncUpdateWarehouseAvailability();
    });

    $("#formNew").off("submit").on("submit", function(e){
        e.preventDefault();
        e.stopImmediatePropagation();
        fncSubmitDocumentForm();
    });
}

// ─────────────────────────────────────────────────────────────────────────────

function fncFillWarehouseSelects(organization_id, warehouses_arr) {
    $("#slctWarehouseOut option[value!='0'], #slctWarehouseIn option[value!='0']").remove();

    warehouses_arr.forEach(function(w){
        if (w.organization_id === organization_id) {
            $("#slctWarehouseOut, #slctWarehouseIn").append(`<option value="${w.id}">${w.name}</option>`);
        }
    });

    fncUpdateWarehouseAvailability();
}

// ─────────────────────────────────────────────────────────────────────────────

function fncUpdateWarehouseAvailability() {
    let selected_out = $("#slctWarehouseOut").val();
    let selected_in  = $("#slctWarehouseIn").val();

    $("#slctWarehouseIn option").prop("disabled", false);
    $("#slctWarehouseOut option").prop("disabled", false);

    if (selected_out && selected_out !== "0") {
        $(`#slctWarehouseIn option[value="${selected_out}"]`).prop("disabled", true);
    }
    if (selected_in && selected_in !== "0") {
        $(`#slctWarehouseOut option[value="${selected_in}"]`).prop("disabled", true);
    }
}

// ─────────────────────────────────────────────────────────────────────────────

function fncToggleDocumentFields(document_type) {
    let show_counterparty  = false;
    let show_warehouse_out = false;
    let show_warehouse_in  = false;
    let show_number        = false;
    let show_document_date = false;
    let show_sum           = false;
    let show_tax_sum       = false;

    if (document_type) {
        let is_incoming = document_type.direction === "incoming";
        let is_outgoing = document_type.direction === "outgoing";

        show_counterparty  = is_incoming || is_outgoing;
        show_number        = document_type.numbering_mode === "manual";
        show_document_date = is_incoming;
        show_sum           = is_incoming;
        show_tax_sum       = is_incoming;

        if (document_type.warehouse_count === 2) {
            show_warehouse_out = true;
            show_warehouse_in  = true;
        } else if (document_type.warehouse_field === "out") {
            show_warehouse_out = true;
        } else if (document_type.warehouse_field === "in") {
            show_warehouse_in = true;
        }
    }

    $("#divCounterpartyWrap").toggleClass("d-none", !show_counterparty);

    $("#divWarehouseOutWrap").toggleClass("d-none", !show_warehouse_out);
    $("#slctWarehouseOut").toggleClass("form-inp", show_warehouse_out);

    $("#divWarehouseInWrap").toggleClass("d-none", !show_warehouse_in);
    $("#slctWarehouseIn").toggleClass("form-inp", show_warehouse_in);

    $("#divDocumentNumberWrap").toggleClass("d-none", !show_number);
    $("#inpDocumentNumber").toggleClass("form-inp", show_number);

    $("#divDocumentDateWrap").toggleClass("d-none", !show_document_date);
    $("#inpDocumentDate").toggleClass("form-inp", show_document_date);

    $("#divSumWrap").toggleClass("d-none", !show_sum);
    $("#inpSum").toggleClass("form-inp", show_sum);

    $("#divTaxSumWrap").toggleClass("d-none", !show_tax_sum);
    $("#inpTaxSum").toggleClass("form-inp", show_tax_sum);

    $("#divDynamicFields").toggleClass("d-none", !document_type);

    if (!show_counterparty) {
        window.documentCounterpartyPicker.clear();
    }
}

// ─────────────────────────────────────────────────────────────────────────────

function fncSubmitDocumentForm() {
    fncHideFormError();

    let params_arr = [];

    let counterparty_visible = !$("#divCounterpartyWrap").hasClass("d-none");
    if (counterparty_visible) {
        let counterparty_id = window.documentCounterpartyPicker.getValue();
        if (!counterparty_id) {
            fncShowFormError("Выберите контрагента");
            return;
        }
        params_arr.push({name: "counterparty_id", value: counterparty_id});
    }

    let crt_arr = fncParamsCrt(".form-inp", params_arr);
    if (crt_arr["all_good"]) {
        $("#btnSave").prop("disabled", true);
        $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
        fncMyAjax("new_document", "docs", crt_arr["params"], 1)
            .done(function(data){
                if (data.sccss) {
                    localStorage.setItem("new_item", data.id);
                    listLoadFunction();
                } else {
                    fncBtnReset();
                    fncShowFormError(data.msg ?? "Проверьте введённые данные");
                }
            })
            .fail(function(){ fncBtnReset(); });
    }
}

// ─────────────────────────────────────────────────────────────────────────────

function infoLoadFunction(document_id) {
    let item_name = $(`.itemName[data-id="${document_id}"]`).first().text().trim();
    $("#mainModalBody").html(spnr_loading);
    $("#mainModalLabel").html(item_name);
    fncHideFormError();
    main_modal._config.backdrop = false;
    main_modal.show();
    let path = new URL("./_docs/document_info.php", url);
    $("#mainModalBody").load(path.href, {id: document_id}, function(){
        fncInitDocumentInfo(document_id);
        fncLoadDocumentItems(document_id);
    });
}

// ─────────────────────────────────────────────────────────────────────────────

function fncInitDocumentInfo(document_id) {

    if ($("#slctItemNomenclature").length) {
        window.docItemNomenclaturePicker = new TomSelect("#slctItemNomenclature", {
            maxOptions: null,
            plugins: ["clear_button"],
            onChange: function(value){
                window.docItemNomenclaturePicker.blur();
                if (value) {
                    $("#inpItemQuantity").trigger("focus");
                    if ($("#inpItemPrice").length) {
                        let price = $(`#slctItemNomenclature option[value="${value}"]`).data("price");
                        if (price !== undefined) {
                            $("#inpItemPrice").val(parseFloat(price).toFixed(2));
                            fncRecalcItemFromPriceOrQuantity();
                        }
                    }
                }
            }
        });
    }

    $("#divDocHeader").off("click").on("click", function(){
        $("#modalOffcanvasBody").html(spnr_loading);
        $("#modalOffcanvasLabel").html("Изменение документа");
        modalOffcanvas.show();
        let path = new URL("./_docs/document_info_header.php", url);
        $("#modalOffcanvasBody").load(path.href, {id: document_id}, function(){
            fncInitEditHeaderForm(document_id);
        });
    });

    $("#inpItemPrice, #inpItemQuantity").off("input").on("input", function(){
        fncRecalcItemFromPriceOrQuantity();
    });

    $("#inpItemSum").off("input").on("input", function(){
        fncRecalcItemFromSum();
    });

    fncInitItemFieldsNavigation();

    $("#btnAddItem").off("click").on("click", function(){
        fncAddDocumentItem(document_id);
    });
}

// ─────────────────────────────────────────────────────────────────────────────

function fncInitItemFieldsNavigation() {
    let field_ids = ["#inpItemQuantity", "#inpItemPrice", "#inpItemSum", "#inpItemTaxSum"]
        .filter(id => $(id).length && !$(id).prop("disabled"));

    field_ids.forEach(function(id, index){
        $(id).off("keydown").on("keydown", function(e){
            if (e.key !== "Enter") return;
            e.preventDefault();
            let next_id = field_ids[index + 1];
            if (next_id) {
                $(next_id).trigger("focus").trigger("select");
            } else {
                $("#btnAddItem").trigger("click");
            }
        });
    });
}

// ─────────────────────────────────────────────────────────────────────────────

function fncInitEditHeaderForm(document_id) {

    if ($("#slctDocCounterparty").length) {
        window.docCounterpartyPicker = new TomSelect("#slctDocCounterparty", {
            maxOptions: null,
            plugins: ["clear_button"],
            onChange: function(){
                window.docCounterpartyPicker.blur();
            }
        });
    }

    $("#formEditHeader").off("submit").on("submit", function(e){
        e.preventDefault();
        e.stopImmediatePropagation();

        let params_arr = [];
        params_arr.push({name: "id", value: document_id});

        if ($("#slctDocCounterparty").length) {
            let counterparty_id = window.docCounterpartyPicker.getValue();
            if (!counterparty_id) {
                fncShowFormError("Выберите контрагента");
                return;
            }
            params_arr.push({name: "counterparty_id", value: counterparty_id});
        }

        let crt_arr = fncParamsCrt("#formEditHeader .form-inp", params_arr);
        if (crt_arr["all_good"]) {
            $("#btnSave").prop("disabled", true);
            $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
            fncMyAjax("upd_document_header", "docs", crt_arr["params"], 1)
                .done(function(data){
                    if (data.sccss) {
                        localStorage.setItem("new_item", document_id);
                        modalOffcanvas.hide();
                        listLoadFunction();
                    } else {
                        fncBtnReset();
                        fncShowFormError(data.msg ?? "Проверьте введённые данные");
                    }
                })
                .fail(function(){ fncBtnReset(); });
        }
    });

    $("#btnDeleteDocument").off("click").on("click", async function(){
        let confirmed = await fncConfirm("Удалить документ безвозвратно?");
        if (!confirmed) return;
        $(this).prop("disabled", true);
        fncMyAjax("del_document", "docs", [{name: "id", value: document_id}], 1)
            .done(function(data){
                if (data.sccss) {
                    modalOffcanvas.hide();
                    main_modal.hide();
                    listLoadFunction();
                } else {
                    fncShowFormError(data.msg ?? "Не удалось удалить документ");
                    $("#btnDeleteDocument").prop("disabled", false);
                }
            })
            .fail(function(){ $("#btnDeleteDocument").prop("disabled", false); });
    });
}

// ─────────────────────────────────────────────────────────────────────────────

function fncRecalcItemFromPriceOrQuantity() {
    let quantity = parseFloat($("#inpItemQuantity").val().replace(",", ".")) || 0;
    let price    = parseFloat($("#inpItemPrice").val().replace(",", ".")) || 0;
    $("#inpItemSum").val((quantity * price).toFixed(2));
}

// ─────────────────────────────────────────────────────────────────────────────

function fncRecalcItemFromSum() {
    let quantity = parseFloat($("#inpItemQuantity").val().replace(",", ".")) || 0;
    let sum      = parseFloat($("#inpItemSum").val().replace(",", ".")) || 0;
    if (quantity > 0) {
        $("#inpItemPrice").val((sum / quantity).toFixed(2));
    }
}

// ─────────────────────────────────────────────────────────────────────────────

function fncLoadDocumentItems(document_id) {
    $("#divDocumentItemsList").html(spnr_loading);
    let path = new URL("./_docs/document_info_items.php", url);
    $("#divDocumentItemsList").load(path.href, {id: document_id}, function(){
        $(".composition-row--clickable").off("click").on("click", function(){
            fncOpenItemEdit(+$(this).data("id"), document_id);
        });

        let $inner = $("#divDocumentItemsInner");
        let has_items = $inner.data("has-items") === 1;
        let is_incoming = $inner.data("is-incoming") === 1;
        let total_sum = parseFloat($inner.data("total-sum")) || 0;

        fncUpdateDocumentFooter(document_id, has_items, is_incoming, total_sum);
    });
}

// ─────────────────────────────────────────────────────────────────────────────

function fncUpdateDocumentFooter(document_id, has_items, is_incoming, total_sum) {
    if (!has_items) {
        $("#mainModalFooter").addClass("d-none").html("");
        return;
    }

    let total_html = is_incoming
        ? `<div class="fw-bold">Итого: ${fncFormatNumber(total_sum)} ₽</div>`
        : `<div></div>`;

    let footer_html = `
        <div class="d-flex justify-content-between align-items-center w-100">
            ${total_html}
            <button type="button" class="btn-action-main" id="btnPostDocument">Провести</button>
        </div>
    `;

    $("#mainModalFooter").removeClass("d-none").html(footer_html);

    $("#btnPostDocument").off("click").on("click", async function(){
        let confirmed = await fncConfirm("Провести документ?");
        if (!confirmed) return;
    });
}

// ─────────────────────────────────────────────────────────────────────────────

function fncOpenItemEdit(item_id, document_id) {
    $("#modalOffcanvasBody").html(spnr_loading);
    $("#modalOffcanvasLabel").html("Редактирование строки");
    modalOffcanvas.show();
    let path = new URL("./_docs/document_info_item_edit.php", url);
    $("#modalOffcanvasBody").load(path.href, {item_id: item_id}, function(){
        fncInitEditItemForm(item_id, document_id);
    });
}

// ─────────────────────────────────────────────────────────────────────────────

function fncInitEditItemForm(item_id, document_id) {

    $("#inpEditItemPrice, #inpEditItemQuantity").off("input").on("input", function(){
        let quantity = parseFloat($("#inpEditItemQuantity").val().replace(",", ".")) || 0;
        let price    = parseFloat($("#inpEditItemPrice").val().replace(",", ".")) || 0;
        if ($("#inpEditItemSum").length) {
            $("#inpEditItemSum").val((quantity * price).toFixed(2));
        }
    });

    $("#inpEditItemSum").off("input").on("input", function(){
        let quantity = parseFloat($("#inpEditItemQuantity").val().replace(",", ".")) || 0;
        let sum      = parseFloat($("#inpEditItemSum").val().replace(",", ".")) || 0;
        if (quantity > 0 && $("#inpEditItemPrice").length) {
            $("#inpEditItemPrice").val((sum / quantity).toFixed(2));
        }
    });

    $("#formEditItem").off("submit").on("submit", function(e){
        e.preventDefault();
        e.stopImmediatePropagation();

        let params_arr = [{name: "id", value: item_id}];
        let crt_arr = fncParamsCrt("#formEditItem .form-inp", params_arr);
        if (crt_arr["all_good"]) {
            $("#btnSave").prop("disabled", true);
            $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
            fncMyAjax("upd_document_item", "docs", crt_arr["params"], 1)
                .done(function(data){
                    if (data.sccss) {
                        modalOffcanvas.hide();
                        fncLoadDocumentItems(document_id);
                    } else {
                        fncBtnReset();
                        fncShowFormError(data.msg ?? "Проверьте введённые данные");
                    }
                })
                .fail(function(){ fncBtnReset(); });
        }
    });

    $("#btnDeleteItem").off("click").on("click", async function(){
        let confirmed = await fncConfirm("Удалить строку?");
        if (!confirmed) return;
        $(this).prop("disabled", true);
        fncMyAjax("del_document_item", "docs", [{name: "id", value: item_id}], 1)
            .done(function(data){
                if (data.sccss) {
                    modalOffcanvas.hide();
                    fncLoadDocumentItems(document_id);
                } else {
                    fncShowFormError(data.msg ?? "Не удалось удалить строку");
                    $("#btnDeleteItem").prop("disabled", false);
                }
            })
            .fail(function(){ $("#btnDeleteItem").prop("disabled", false); });
    });
}

// ─────────────────────────────────────────────────────────────────────────────

function fncAddDocumentItem(document_id) {
    fncNormalizeInp("#inpItemQuantity", "digits_double");

    let nomenclature_id = window.docItemNomenclaturePicker.getValue();
    let quantity = $("#inpItemQuantity").val();

    $("#divItemFormError").addClass("d-none");

    if (!nomenclature_id || !quantity) {
        $("#spnItemFormError").html("Заполните обязательные поля");
        $("#divItemFormError").removeClass("d-none");
        return;
    }

    let params_arr = [
        {name: "document_id", value: document_id},
        {name: "nomenclature_id", value: nomenclature_id},
        {name: "quantity", value: quantity},
    ];

    if ($("#inpItemPrice").length) {
        fncNormalizeInp("#inpItemPrice", "digits_double");
        fncNormalizeInp("#inpItemSum", "digits_double");
        fncNormalizeInp("#inpItemTaxSum", "digits_double");

        let price = $("#inpItemPrice").val();
        let sum   = $("#inpItemSum").val();
        let tax_sum = $("#inpItemTaxSum").val();
        if (!price || !sum || tax_sum === "") {
            $("#spnItemFormError").html("Заполните обязательные поля");
            $("#divItemFormError").removeClass("d-none");
            return;
        }
        params_arr.push({name: "price", value: price});
        params_arr.push({name: "sum", value: sum});
        params_arr.push({name: "tax_sum", value: tax_sum});
    }

    $("#btnAddItem").prop("disabled", true);
    $("#btnAddItemText, #divAddItemLoading").toggleClass("d-none");

    fncMyAjax("new_document_item", "docs", params_arr, 1)
        .done(function(data){
            if (data.sccss) {
                window.docItemNomenclaturePicker.clear();
                $("#inpItemQuantity, #inpItemPrice, #inpItemSum").val("");
                if (!$("#inpItemTaxSum").prop("disabled")) {
                    $("#inpItemTaxSum").val("");
                }
                fncLoadDocumentItems(document_id);
                window.docItemNomenclaturePicker.focus();
            } else {
                $("#spnItemFormError").html(data.msg ?? "Не удалось добавить строку");
                $("#divItemFormError").removeClass("d-none");
            }
        })
        .always(function(){
            $("#btnAddItem").prop("disabled", false);
            $("#btnAddItemText, #divAddItemLoading").toggleClass("d-none");
        });
}
