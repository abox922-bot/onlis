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

    $("#divChptContent").html(spnr_loading);
    let path = new URL("./_docs/documents_list.php", url);
    $("#divChptContent").load(path.href, {date_from, date_to, type_code, warehouse_id});
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
                    $("#mainModal").one("hidden.bs.modal", function(){
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
}
