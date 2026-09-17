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

    window.currentDocWarehouseOutId = $("#inpDocumentId").data("warehouse-out-id") || null;

    if ($("#slctItemNomenclature").length) {
        window.docItemNomenclaturePicker = new TomSelect("#slctItemNomenclature", {
            maxOptions: null,
            plugins: ["clear_button"],
            openOnFocus: false,
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
                    let $option = $(`#slctItemNomenclature option[value="${value}"]`);
                    if ($option.data("stock") !== undefined) {
                        window.currentItemStock = parseFloat($option.data("stock")) || 0;
                        $("#divStockHint").html("Доступно: " + fncFormatNumber(window.currentItemStock)).show();
                    } else {
                        window.currentItemStock = null;
                        $("#divStockHint").hide();
                    }
                    if ($("#divBatchSelectionRow").length) {
                        $("#divBatchSelectionRow").show();
                        fncResetBatchSelection(true);
                    }
                } else {
                    window.currentItemStock = null;
                    $("#divStockHint").hide();
                    if ($("#divBatchSelectionRow").length) {
                        $("#divBatchSelectionRow").hide();
                    }
                }
            }
        });
    }

    if ($("#divDocHeader").hasClass("doc-header-compact--clickable")) {
        $("#divDocHeader").off("click").on("click", function(){
            $("#modalOffcanvasBody").html(spnr_loading);
            $("#modalOffcanvasLabel").html("Изменение документа");
            modalOffcanvas.show();
            let path = new URL("./_docs/document_info_header.php", url);
            $("#modalOffcanvasBody").load(path.href, {id: document_id}, function(){
                fncInitEditHeaderForm(document_id);
            });
        });
    }

    $("#inpItemPrice").off("input").on("input", function(){
        fncRecalcItemFromPriceOrQuantity();
    });

    $("#inpItemQuantity").off("input").on("input", function(){
        fncRecalcItemFromPriceOrQuantity();
        if (window.currentItemStock !== null && window.currentItemStock !== undefined) {
            let entered = parseFloat($(this).val().replace(",", ".")) || 0;
            $(this).toggleClass("is-invalid", entered > window.currentItemStock);
        }
        fncResetBatchSelection();
    });

    $("#inpItemSum").off("input").on("input", function(){
        fncRecalcItemFromSum();
    });

    $("#chkAutoBatch").off("change").on("change", function(){
        let is_manual = !$(this).prop("checked");
        $("#divManualBatchWrap").toggleClass("d-none", !is_manual);
        fncResetBatchSelection();
    });

    $("#btnSelectBatches").off("click").on("click", function(){
        fncOpenBatchSelection(document_id);
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
    if (!$("#inpItemPrice").length) return;

    let quantity = parseFloat($("#inpItemQuantity").val().replace(",", ".")) || 0;
    let price    = parseFloat($("#inpItemPrice").val().replace(",", ".")) || 0;
    $("#inpItemSum").val((quantity * price).toFixed(2));
}

// ─────────────────────────────────────────────────────────────────────────────

function fncRecalcItemFromSum() {
    if (!$("#inpItemSum").length) return;

    let quantity = parseFloat($("#inpItemQuantity").val().replace(",", ".")) || 0;
    let sum      = parseFloat($("#inpItemSum").val().replace(",", ".")) || 0;
    if (quantity > 0) {
        $("#inpItemPrice").val((sum / quantity).toFixed(2));
    }
}

// ─────────────────────────────────────────────────────────────────────────────

function fncResetBatchSelection(reset_mode = false) {
    window.selectedBatches = null;
    if ($("#spnBatchStatus").length) {
        $("#spnBatchStatus").html("Не распределено").removeClass("text-success").addClass("text-muted");
    }
    if (reset_mode && $("#chkAutoBatch").length) {
        $("#chkAutoBatch").prop("checked", true);
        $("#divManualBatchWrap").addClass("d-none");
    }
}

// ─────────────────────────────────────────────────────────────────────────────

function fncOpenBatchSelection(document_id) {
    let nomenclature_id = window.docItemNomenclaturePicker.getValue();
    let quantity = $("#inpItemQuantity").val();

    if (!nomenclature_id || !quantity) {
        $("#spnItemFormError").html("Сначала выберите номенклатуру и укажите количество");
        $("#divItemFormError").removeClass("d-none");
        return;
    }

    $("#modalOffcanvasBody").html(spnr_loading);
    $("#modalOffcanvasLabel").html("Выбор партий");
    modalOffcanvas.show();
    let path = new URL("./_docs/document_item_batch_select.php", url);
    $("#modalOffcanvasBody").load(path.href, {document_id, nomenclature_id, warehouse_id: window.currentDocWarehouseOutId, needed_quantity: quantity}, function(){
        fncInitBatchSelection();
    });
}

// ─────────────────────────────────────────────────────────────────────────────

function fncInitBatchSelection() {
    let needed = parseFloat($("#inpBatchNeededQuantity").val()) || 0;

    function fncRecalcDistributed() {
        let total = 0;
        $(".batch-quantity-input").each(function(){
            total += parseFloat($(this).val().replace(",", ".")) || 0;
        });
        $("#spnBatchDistributed").html(fncFormatNumber(total));
        return total;
    }

    $(".batch-quantity-input").off("input").on("input", function(){
        fncNormalizeInp(this, "digits_double");

        let remaining = parseFloat($(this).closest(".composition-row").data("batch-remaining")) || 0;
        let entered = parseFloat($(this).val().replace(",", ".")) || 0;
        $(this).toggleClass("is-invalid", entered > remaining);

        fncRecalcDistributed();
    });

    $("#btnConfirmBatches").off("click").on("click", function(){
        let total = fncRecalcDistributed();
        $("#divBatchFormError").addClass("d-none");

        if ($(".batch-quantity-input.is-invalid").length > 0) {
            $("#spnBatchFormError").html("Одна из партий превышает свой остаток");
            $("#divBatchFormError").removeClass("d-none");
            return;
        }

        if (Math.abs(total - needed) > 0.001) {
            $("#spnBatchFormError").html("Сумма по партиям должна равняться " + fncFormatNumber(needed));
            $("#divBatchFormError").removeClass("d-none");
            return;
        }

        let distribution = [];
        $(".composition-row[data-batch-id]").each(function(){
            let qty = parseFloat($(this).find(".batch-quantity-input").val().replace(",", ".")) || 0;
            if (qty > 0) {
                distribution.push({batch_id: $(this).data("batch-id"), quantity: qty});
            }
        });

        window.selectedBatches = distribution;
        $("#spnBatchStatus").html("Распределено: " + fncFormatNumber(total)).removeClass("text-muted").addClass("text-success");
        modalOffcanvas.hide();
    });
}

// ─────────────────────────────────────────────────────────────────────────────

function fncLoadDocumentItems(document_id) {
    $("#divDocumentItemsList").html(spnr_loading);
    let path = new URL("./_docs/document_info_items.php", url);
    $("#divDocumentItemsList").load(path.href, {id: document_id}, function(){
        let $inner = $("#divDocumentItemsInner");
        let has_items     = $inner.data("has-items") === 1;
        let is_incoming   = $inner.data("is-incoming") === 1;
        let is_draft      = $inner.data("is-draft") === 1;
        let total_sum     = parseFloat($inner.data("total-sum")) || 0;
        let total_tax_sum = parseFloat($inner.data("total-tax-sum")) || 0;

        $(".composition-row--clickable").off("click").on("click", function(){
            let clicked_item_id = +$(this).data("id");
            if (is_draft) {
                fncOpenItemEdit(clicked_item_id, document_id);
            } else {
                fncOpenItemLedger(clicked_item_id);
            }
        });

        fncUpdateDocumentFooter(document_id, has_items, is_incoming, is_draft, total_sum, total_tax_sum);

        if (window.docItemNomenclaturePicker) {
            window.docItemNomenclaturePicker.focus();
        }
    });
}

// ─────────────────────────────────────────────────────────────────────────────

function fncOpenItemLedger(item_id, nomenclature_name) {
    $("#modalOffcanvasBody").html(spnr_loading);
    $("#modalOffcanvasLabel").html(nomenclature_name || "Складские операции");
    modalOffcanvas.show();
    let path = new URL("./_docs/document_item_ledger.php", url);
    $("#modalOffcanvasBody").load(path.href, {item_id: item_id}, function(){
        $("[data-ledger-toggle]").off("click").on("click", function(){
            let idx = $(this).data("ledger-toggle");
            $(`#divLedgerDetails${idx}`).toggleClass("d-none");
            $(`#chevronLedger${idx}`).toggleClass("bi-chevron-down bi-chevron-up");
        });
    });
}

// ─────────────────────────────────────────────────────────────────────────────

function fncUpdateDocumentFooter(document_id, has_items, is_incoming, is_draft, total_sum, total_tax_sum) {
    if (!has_items) {
        $("#mainModalFooter").addClass("d-none").html("");
        return;
    }

    let total_html = is_incoming
        ? `<div class="fw-bold">Итого: ${fncFormatNumber(total_sum)} ₽ <span class="text-muted fw-normal">(НДС: ${fncFormatNumber(total_tax_sum)} ₽)</span></div>`
        : `<div class="fw-bold">Итого: ${fncFormatNumber(total_sum)} ₽</div>`;

    let post_button_html = is_draft
        ? `<button type="button" class="btn-action-main" id="btnPostDocument">Провести</button>`
        : '';

    let footer_html = `
        <div class="d-flex justify-content-between align-items-center w-100">
            ${total_html}
            ${post_button_html}
        </div>
    `;

    $("#mainModalFooter").removeClass("d-none").html(footer_html);

    if (is_draft) {
        $("#btnPostDocument").off("click").on("click", async function(){
            if (is_incoming) {
                let header_sum = parseFloat($("#inpDocumentId").data("header-sum"));
                let header_tax_sum = parseFloat($("#inpDocumentId").data("header-tax-sum"));

                if (Math.abs(header_sum - total_sum) > 0.01 || Math.abs(header_tax_sum - total_tax_sum) > 0.01) {
                    let confirmed_mismatch = await fncConfirm(
                        `Сумма документа (${fncFormatNumber(header_sum)} ₽, НДС ${fncFormatNumber(header_tax_sum)} ₽) не совпадает с суммой строк (${fncFormatNumber(total_sum)} ₽, НДС ${fncFormatNumber(total_tax_sum)} ₽). Провести документ?`
                    );
                    if (!confirmed_mismatch) return;
                } else {
                    let confirmed = await fncConfirm("Провести документ?");
                    if (!confirmed) return;
                }
            } else {
                let confirmed = await fncConfirm("Провести документ?");
                if (!confirmed) return;
            }

            $(this).prop("disabled", true);
            $(this).html('<div class="spinner-border spinner-border-sm"></div>');

            fncMyAjax("post_document", "docs", [{name: "id", value: document_id}], 1)
                .done(function(data){
                    if (data.sccss) {
                        main_modal.hide();
                        listLoadFunction();
                    } else {
                        fncShowFormError(data.msg ?? "Не удалось провести документ");
                        $("#btnPostDocument").prop("disabled", false).html("Провести");
                    }
                })
                .fail(function(){
                    $("#btnPostDocument").prop("disabled", false).html("Провести");
                });
        });
    }
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

    if ($("#formEditItem").length) {
        $("#inpEditItemPrice, #inpEditItemQuantity").off("input").on("input", function(){
            if (!$("#inpEditItemPrice").length) return;
            let quantity = parseFloat($("#inpEditItemQuantity").val().replace(",", ".")) || 0;
            let price    = parseFloat($("#inpEditItemPrice").val().replace(",", ".")) || 0;
            $("#inpEditItemSum").val((quantity * price).toFixed(2));
        });

        $("#inpEditItemSum").off("input").on("input", function(){
            if (!$("#inpEditItemPrice").length) return;
            let quantity = parseFloat($("#inpEditItemQuantity").val().replace(",", ".")) || 0;
            let sum      = parseFloat($("#inpEditItemSum").val().replace(",", ".")) || 0;
            if (quantity > 0) {
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
    }

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

    if (window.currentItemStock !== null && window.currentItemStock !== undefined) {
        if (parseFloat(quantity) > window.currentItemStock) {
            $("#spnItemFormError").html("Количество превышает доступный остаток");
            $("#divItemFormError").removeClass("d-none");
            return;
        }
    }

    let params_arr = [
        {name: "document_id", value: document_id},
        {name: "nomenclature_id", value: nomenclature_id},
        {name: "quantity", value: quantity},
    ];

    if ($("#inpItemPrice").length) {
        fncNormalizeInp("#inpItemPrice", "digits_double");
        fncNormalizeInp("#inpItemSum", "digits_double");

        let price = $("#inpItemPrice").val();
        let sum   = $("#inpItemSum").val();
        if (!price || !sum) {
            $("#spnItemFormError").html("Заполните обязательные поля");
            $("#divItemFormError").removeClass("d-none");
            return;
        }
        params_arr.push({name: "price", value: price});
        params_arr.push({name: "sum", value: sum});
    }

    if ($("#inpItemTaxSum").length) {
        fncNormalizeInp("#inpItemTaxSum", "digits_double");
        let tax_sum = $("#inpItemTaxSum").val();
        if (tax_sum === "") {
            $("#spnItemFormError").html("Заполните обязательные поля");
            $("#divItemFormError").removeClass("d-none");
            return;
        }
        params_arr.push({name: "tax_sum", value: tax_sum});
    }

    if ($("#divBatchSelectionRow").length) {
        let is_auto = $("#chkAutoBatch").prop("checked");
        params_arr.push({name: "auto_batch", value: is_auto ? "1" : "0"});

        if (!is_auto) {
            if (!window.selectedBatches || window.selectedBatches.length === 0) {
                $("#spnItemFormError").html("Распределите количество по партиям");
                $("#divItemFormError").removeClass("d-none");
                return;
            }
            params_arr.push({name: "batches", value: JSON.stringify(window.selectedBatches)});
        }
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
                fncResetBatchSelection(true);
                if ($("#divBatchSelectionRow").length) {
                    $("#divBatchSelectionRow").hide();
                }
                fncLoadDocumentItems(document_id);
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
