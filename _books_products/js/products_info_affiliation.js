$(function(){

    if (!canDo('products.manage')) {
        $(".slctWorkstation").prop("disabled", true);
        return;
    }

    let product_id = +$("#inpAffiliationProductId").val();

    $(".slctWorkstation").off("change").on("change", function(){
        let $select = $(this);
        let type_id = $select.data("type-id");
        let workstation_id = $select.val();

        $(`#spnWorkstationError${type_id}`).addClass("d-none").html("");
        $(`#spnWorkstationLoading${type_id}`).removeClass("d-none");
        $select.prop("disabled", true);

        fncMyAjax("upd_nomenclature_workstation", "noms", [
            {name: "nomenclature_id", value: product_id},
            {name: "object_type_id", value: type_id},
            {name: "workstation_id", value: workstation_id}
        ], 1)
            .done(function(data){
                if (data.sccss) {
                    fncMarkItemEdited(product_id);
                } else {
                    $(`#spnWorkstationError${type_id}`).removeClass("d-none").html(data.msg ?? "Не удалось сохранить привязку");
                }
            })
            .fail(function(){
                $(`#spnWorkstationError${type_id}`).removeClass("d-none").html("Не удалось сохранить привязку");
            })
            .always(function(){
                $(`#spnWorkstationLoading${type_id}`).addClass("d-none");
                $select.prop("disabled", false);
            });
    });
});
