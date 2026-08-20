$(function(){

    let product_id = +$("#inpProductGeneralId").val();

    function fncUpdateOutputVisibility() {
        let type_value = $("input[name='productTypeRadio']:checked").val();
        let unit_is_float = +$("#slctUnit").find("option:selected").data("is-float");

        if (type_value === "service") {
            $("#divUnitWrap").addClass("d-none");
            $("#slctUnit").removeAttr("data-required");
            $("#divOutputQuantityWrap").addClass("d-none");
            $("#inpOutputQuantity").val("");
        } else {
            $("#divUnitWrap").removeClass("d-none");
            $("#slctUnit").attr("data-required", "1");

            if (type_value === "food" && !unit_is_float) {
                $("#divOutputQuantityWrap").removeClass("d-none");
                $("#divUnitWrap").addClass("col-md-6");
            } else {
                $("#divOutputQuantityWrap").addClass("d-none");
                $("#inpOutputQuantity").val("");
                $("#divUnitWrap").removeClass("col-md-6");
            }
        }
    }

    $("input[name='productTypeRadio']").off("change").on("change", fncUpdateOutputVisibility);
    $("#slctUnit").off("change").on("change", fncUpdateOutputVisibility);

    $("#chkOnlineSale").off("change").on("change", function(){
        let $chk = $(this);
        let value = $chk.prop("checked") ? 1 : 0;
        $chk.prop("disabled", true);
        $("#spnOnlineSaleLoading").removeClass("d-none");
        fncMyAjax("upd_product_channel", "noms", [
            {name: "id", value: product_id},
            {name: "channel", value: "is_online_sale"},
            {name: "value", value: value}
        ], 1)
            .done(function(data){
                if (data.sccss) {
                    fncMarkItemEdited(product_id);
                } else {
                    $chk.prop("checked", !value);
                }
            })
            .fail(function(){
                $chk.prop("checked", !value);
            })
            .always(function(){
                $chk.prop("disabled", false);
                $("#spnOnlineSaleLoading").addClass("d-none");
            });
    });

    $("#chkDeliverySale").off("change").on("change", function(){
        let $chk = $(this);
        let value = $chk.prop("checked") ? 1 : 0;
        $chk.prop("disabled", true);
        $("#spnDeliverySaleLoading").removeClass("d-none");
        fncMyAjax("upd_product_channel", "noms", [
            {name: "id", value: product_id},
            {name: "channel", value: "is_delivery_sale"},
            {name: "value", value: value}
        ], 1)
            .done(function(data){
                if (data.sccss) {
                    fncMarkItemEdited(product_id);
                } else {
                    $chk.prop("checked", !value);
                }
            })
            .fail(function(){
                $chk.prop("checked", !value);
            })
            .always(function(){
                $chk.prop("disabled", false);
                $("#spnDeliverySaleLoading").addClass("d-none");
            });
    });

    if (!canDo('products.manage')) {
        $("#formInfo").off("submit");
        return;
    }

    $("#formInfo").off("submit").on("submit", function(e){
        e.preventDefault();
        e.stopImmediatePropagation();

        let product_type_value = $("input[name='productTypeRadio']:checked").val();
        if (product_type_value === undefined) {
            fncShowFormError("Укажите тип товара");
            return;
        }

        let params_arr = [];
        params_arr.push({name: "id", value: product_id});
        params_arr.push({name: "product_type", value: product_type_value});
        let crt_arr = fncParamsCrt(".form-inp", params_arr);
        if (crt_arr["all_good"]) {
            $("#btnSave").prop("disabled", true);
            $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
            fncMyAjax("upd_product", "noms", crt_arr["params"], 1)
                .done(function(data){
                    if (data.sccss) {
                        fncMarkItemEdited(product_id);
                        main_modal.hide();
                    } else {
                        fncShowFormError(data.msg ?? "Проверьте введённые данные");
                        fncBtnReset();
                    }
                })
                .fail(function(){ fncBtnReset(); });
        }
    });

    $("#btnArchive").off("click").on("click", async function(){
        let confirmed = await fncConfirm("Архивировать товар?");
        if (!confirmed) return;
        $(this).prop("disabled", true);
        fncMyAjax("archive_nomenclature", "noms", [{name: "id", value: product_id}], 1)
            .done(function(data){
                if (data.sccss) {
                    fncMarkItemEdited(product_id);
                    main_modal.hide();
                } else {
                    fncShowFormError(data.msg ?? "Не удалось архивировать товар");
                    $("#btnArchive").prop("disabled", false);
                }
            })
            .fail(function(){ $("#btnArchive").prop("disabled", false); });
    });

    $("#btnRestore").off("click").on("click", async function(){
        let confirmed = await fncConfirm("Восстановить товар?");
        if (!confirmed) return;
        $(this).prop("disabled", true);
        fncMyAjax("restore_nomenclature", "noms", [{name: "id", value: product_id}], 1)
            .done(function(data){
                if (data.sccss) {
                    fncMarkItemEdited(product_id);
                    main_modal.hide();
                } else {
                    fncShowFormError(data.msg ?? "Не удалось восстановить товар");
                    $("#btnRestore").prop("disabled", false);
                }
            })
            .fail(function(){ $("#btnRestore").prop("disabled", false); });
    });

});
