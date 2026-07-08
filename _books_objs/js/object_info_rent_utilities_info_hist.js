$(function(){

    let utility_id = $("#inpUtilityId").val();

    // Дефолтный период — последние 10 дней
    let end = new Date();
    let start = new Date();
    start.setDate(start.getDate() - 10);

    function fncDateStr(d) {
        return d.toISOString().slice(0, 10);
    }

    $("#inpHistStartDate").val(fncDateStr(start));
    $("#inpHistEndDate").val(fncDateStr(end));

    fncReadingsListLoad();

    $("#inpHistStartDate, #inpHistEndDate").off("change").on("change", function(){
        fncReadingsListLoad();
    });

    $("#btnNewReading").off("click").on("click", function(){
        $("#divNewReading").removeClass("d-none");
        $("#btnNewReading").addClass("d-none");
        $("#inpReadingDate").val(fncDateStr(new Date()));
    });

    $("#btnReadingClose").off("click").on("click", function(){
        $("#divNewReading").addClass("d-none");
        $("#btnNewReading").removeClass("d-none");
        $("#divReadingFormError").addClass("d-none");
    });

    $("#formReadingNew").off("submit").on("submit", function(e){
        e.preventDefault();
        e.stopImmediatePropagation();
        $("#divReadingFormError").addClass("d-none");
        let params_arr = [];
        params_arr.push({name: "utility_type_id", value: utility_id});
        let crt_arr = fncParamsCrt(".form-inp", params_arr);
        if (crt_arr["all_good"]) {
            $("#btnReadingSave").prop("disabled", true);
            $("#btnReadingSaveText, #divReadingSaveLoading").toggleClass("d-none");
            fncMyAjax("new_object_utility_reading", "objs", crt_arr["params"], 1)
                .done(function(data){
                    if (data.sccss) {
                        $("#divNewReading").addClass("d-none");
                        $("#btnNewReading").removeClass("d-none");
                        $("#inpReadingValue").val("");
                    } else {
                      $("#spnReadingFormError").html(data.msg ?? "Проверьте введённые данные");
                      $("#divReadingFormError").removeClass("d-none");
                    }
                })
                .fail(function(){ fncBtnReset(); })
                .always(function(){
                    $("#btnReadingSave").prop("disabled", false);
                    $("#btnReadingSaveText, #divReadingSaveLoading").toggleClass("d-none");
                    fncReadingsListLoad();
                });
        }
    });

});

function fncReadingsListLoad() {
    let utility_id = $("#inpUtilityId").val();
    $("#divHistListContent").html(spnr_loading);
    let path = new URL("./_books_objs/object_info_rent_utilities_info_hist_list.php", url);
    $("#divHistListContent").load(path.href, {
        utility_type_id: utility_id,
        start_date: $("#inpHistStartDate").val(),
        end_date: $("#inpHistEndDate").val()
    });
}
