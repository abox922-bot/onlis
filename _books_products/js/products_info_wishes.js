$(function(){

    let nomenclature_id = +$("#inpProductWishesId").val();

    if (canDo('products.manage')) {
        window.wishPicker = new TomSelect("#slctWish", {
            maxOptions: null,
            onChange: function(value){
                if (!value) return;

                fncMyAjax("new_nomenclature_wish", "noms", [
                    {name: "nomenclature_id", value: nomenclature_id},
                    {name: "wish_id", value: value}
                ], 1)
                    .done(function(data){
                        if (data.sccss) {
                            fncMarkItemEdited(nomenclature_id);
                            fncProductTabLoad(nomenclature_id, "wishes");
                        }
                    });
            }
        });
    }

    $(".btnDeleteWish").off("click").on("click", async function(e){
        e.stopPropagation();
        let confirmed = await fncConfirm("Удалить пожелание?");
        if (!confirmed) return;
        let id = $(this).data("id");
        fncMyAjax("del_nomenclature_wish", "noms", [{name: "id", value: id}], 1)
            .done(function(data){
                if (data.sccss) {
                    fncMarkItemEdited(nomenclature_id);
                    fncProductTabLoad(nomenclature_id, "wishes");
                }
            });
    });

});
