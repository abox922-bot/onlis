$(function(){

    let id = +$("#inpObjectId").val();

    $(".inline-tab-sub").off("click").on("click", function(){
        $(".inline-tab-sub").removeClass("active");
        $(this).addClass("active");
        fncObjectGraphTabLoad(id, $(this).data("target"));
    });

    fncObjectGraphTabLoad(id, "main");

});

function fncObjectGraphTabLoad(id, target) {
    $(".inline-tab-sub").prop("disabled", true);
    $("#divObjectGraphContent").html(spnr_loading);
    let path = new URL(`./_books_objs/object_info_schedule_${target}.php`, url);
    $("#divObjectGraphContent").load(path.href, {id}, function(){
        $(".inline-tab-sub").prop("disabled", false);
    });
}
