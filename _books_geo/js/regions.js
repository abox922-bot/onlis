//==============================================================================
$(document).ready(function(){
  //~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
  $("#slctCountry").change(function(){
    if (+$(this).val() > 0) {
      $("#btnFastNew").prop("disabled", false);
      listLoadFunction(+$("#slctCountry").val());
    } else {
      $("#btnFastNew").prop("disabled", true);
      $("#divChptContent").addClass("d-none");
      $("#divChptContent").html("");
    }
  });
  //~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
  $("#btnFastNew").click(function(){
    let country_name = $("#slctCountry option:selected").html();
    $("#mainModalBody").html(spnr_loading);
    $("#mainModalLabel").html("Добавление региона");
    let path = new URL("./_books_geo/regions_new.php", url);
    $("#mainModalBody").load(path.href, {country_name}, function(){
      main_modal.show();
      $("#formNew").submit(function(e){
        e.preventDefault();
        let params_arr = [];
        params_arr.push({name: "country", value: +$("#slctCountry").val()});
        let crt_arr = fncParamsCrt(".form-inp", params_arr);
        if (crt_arr["all_good"] && confirm("Сохранить?")) {
          $("#btnSave, #divSaveLoading").toggleClass("d-none");
          $.ajax({type: "POST",	url: rqst_path.href, data: {params: JSON.stringify(crt_arr["params"]), action: "new_region", module: "geo", return_data: 1},	success: function(data){
            data = JSON.parse(data);
            localStorage.setItem('new_item', data["id"]);
            listLoadFunction(+$("#slctCountry").val());
          }});
        }
      });
    });
  });
  //~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
});
//==============================================================================
function listLoadFunction(country) {
  $("#divChptContent").removeClass("d-none");
  $("#divChptContent").html(spnr_loading);
  let path = new URL("./_books_geo/regions_list.php", url);
	$("#divChptContent").load(path.href, {country}, function(){
		searchFunction();
    $(".itemTr").click(function(){
      infoLoadFunction(+$(this).attr("data-id"));
    });
    if (localStorage.getItem('new_item') !== null) {
      infoLoadFunction(localStorage.getItem('new_item'));
      localStorage.removeItem('new_item');
    }
	});
}
//==============================================================================
function infoLoadFunction(item_id) {
  let item_name = $(`.itemName[data-id=${item_id}]`).html();
  $("#mainModalBody").html(spnr_loading);
  $("#mainModalLabel").html(`<small class="fw-normal">Информация о регионе</small><br>${item_name}`);
  let path = new URL("./_books_geo/regions_info.php", url);
  $("#mainModalBody").load(path.href, {id: item_id}, function(){
    main_modal.show();
    $("#formInfo").submit(function(e){
      e.preventDefault();
      let params_arr = [];
      params_arr.push({name: "item-id", value: item_id});
      let crt_arr = fncParamsCrt(".form-inp", params_arr);
      if (crt_arr["all_good"] && confirm("Сохранить?")) {
        $("#btnSave, #divSaveLoading").toggleClass("d-none");
        $.ajax({type: "POST",	url: rqst_path.href, data: {params: JSON.stringify(crt_arr["params"]), action: "upd_region", module: "geo", return_data: 0},	success: function(){
          listLoadFunction(+$("#slctCountry").val());
          main_modal.hide();
        }});
      }
    });
  });
}
//==============================================================================
