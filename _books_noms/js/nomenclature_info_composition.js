$(function(){

    let nomenclature_id = +$("#inpCompositionNomenclatureId").val();

    if (canDo('nomenclature.manage')) {
        window.ingredientPicker = new TomSelect("#slctIngredient", {
            maxOptions: null,
            onChange: function(value){
                if (!value) return;

                fncMyAjax("new_composition_item", "noms", [
                    {name: "nomenclature_id", value: nomenclature_id},
                    {name: "ingredient_id", value: value}
                ], 1)
                    .done(function(data){
                        if (data.sccss) {
                            window.ingredientPicker.clear(true);
                            window.ingredientPicker.removeOption(value);
                            fncCompositionListLoad(nomenclature_id);
                            fncMarkItemEdited(nomenclature_id);
                        }
                    });
            }
        });
    } else {
        $("#slctIngredient").prop("disabled", true);
    }

    fncCompositionListLoad(nomenclature_id);

});

// ─────────────────────────────────────────────────────────────────────────────

function fncCompositionListLoad(nomenclature_id) {
    $("#divCompositionList").html(spnr_loading);
    let path = new URL("./_books_noms/nomenclature_info_composition_list.php", url);
    $("#divCompositionList").load(path.href, {id: nomenclature_id}, function(){
        if (canDo('nomenclature.manage')) {
            $(".itemCompositionTr").off("click").on("click", async function(){
                let confirmed = await fncConfirm("Удалить ингредиент из состава?");
                if (!confirmed) return;
                fncMyAjax("del_composition_item", "noms", [{name: "id", value: $(this).data("id")}], 1)
                    .always(function(){
                        fncCompositionListLoad(nomenclature_id);
                        fncMarkItemEdited(nomenclature_id);
                    });
            });
        }
    });
}
