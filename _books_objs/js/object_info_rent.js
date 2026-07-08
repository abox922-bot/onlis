$(function(){

    let id = +$("#inpObjectId").val();

    $(".inline-tab-sub").off("click").on("click", function(){
        $(".inline-tab-sub").removeClass("active");
        $(this).addClass("active");
        fncObjectRentTabLoad(id, $(this).data("target"));
    });

    fncObjectRentTabLoad(id, "lease");

});

function fncObjectRentTabLoad(id, target) {
    $(".inline-tab-sub").prop("disabled", true);
    $("#divObjectRentContent").html(spnr_loading);
    let path = new URL(`./_books_objs/object_info_rent_${target}.php`, url);
    $("#divObjectRentContent").load(path.href, {id}, function(){
        $(".inline-tab-sub").prop("disabled", false);
    });
}
