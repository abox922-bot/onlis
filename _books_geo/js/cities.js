//==============================================================================
$(document).ready(function(){
  let regions_arr = [];
  //~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
  $("#slctRegion option[value!=0]").each(function(){
    regions_arr.push({id: +$(this).val(), country: +$(this).attr("data-country"), name: $(this).html()});
    $(this).remove();
  });
  //~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
  $("#slctCountry").change(function(){
    $("#btnFastNew").prop("disabled", true);
    $("#divChptContent").addClass("d-none");
    $("#divChptContent").html("");
    if (+$(this).val() > 0) {
      $("#slctRegion option[value!=0]").remove();
      regions_arr.forEach((item, i) => {
        if (item["country"] == $("#slctCountry").val()) {
          $("#slctRegion").append(`<option value="${item["id"]}">${item["name"]}</option>`);
        }
      });
      $("#slctRegion").prop("disabled", false);
    } else {
      $("#slctRegion option[value!=0]").remove();
      $("#slctRegion").prop("disabled", true);
    }
  });
  //~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
  $("#slctRegion").change(function(){
    if (+$(this).val() > 0) {
      $("#btnFastNew").prop("disabled", false);
      listLoadFunction(+$("#slctCountry").val(), +$("#slctRegion").val());
    } else {
      $("#btnFastNew").prop("disabled", true);
      $("#divChptContent").addClass("d-none");
      $("#divChptContent").html("");
    }
  });
  //~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
  $("#btnFastNew").click(function(){
    let country_name = $("#slctCountry option:selected").html();
    let region_name = $("#slctRegion option:selected").html();
    $("#mainModalBody").html(spnr_loading);
    $("#mainModalLabel").html("Добавление города");
    let path = new URL("./_books_geo/cities_new.php", url);
    $("#mainModalBody").load(path.href, {country_name, region_name}, function(){
      main_modal.show();
      $("#formNew").submit(function(e){
        e.preventDefault();
        let params_arr = [];
        params_arr.push({name: "country", value: +$("#slctCountry").val()});
        params_arr.push({name: "region", value: +$("#slctRegion").val()});
        let crt_arr = fncParamsCrt(".form-inp", params_arr);
        if (crt_arr["all_good"] && confirm("Сохранить?")) {
          $("#btnSave, #divSaveLoading").toggleClass("d-none");
          $.ajax({type: "POST",	url: rqst_path.href, data: {params: JSON.stringify(crt_arr["params"]), action: "new_city", module: "geo", return_data: 0},	success: function(data){
            listLoadFunction(+$("#slctCountry").val(), +$("#slctRegion").val());
            main_modal.hide();
          }});
        }
      });
    });
  });
  //~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
});
//==============================================================================
function listLoadFunction(country, region) {
  $("#divChptContent").removeClass("d-none");
  $("#divChptContent").html(spnr_loading);
  let path = new URL("./_books_geo/cities_list.php", url);
	$("#divChptContent").load(path.href, {country, region}, function(){
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
  $("#mainModalLabel").html(`<small class="fw-normal">Информация о городе</small><br>${item_name}`);
  let path = new URL("./_books_geo/cities_info.php", url);
  $("#mainModalBody").load(path.href, {id: item_id}, function(){
    main_modal.show();
    $("#formInfo").submit(function(e){
      e.preventDefault();
      let params_arr = [];
      params_arr.push({name: "item-id", value: item_id});
      let crt_arr = fncParamsCrt(".form-inp", params_arr);
      if (crt_arr["all_good"] && confirm("Сохранить?")) {
        $("#btnSave, #divSaveLoading").toggleClass("d-none");
        $.ajax({type: "POST",	url: rqst_path.href, data: {params: JSON.stringify(crt_arr["params"]), action: "upd_city", module: "geo", return_data: 0},	success: function(){
          listLoadFunction(+$("#slctCountry").val(), +$("#slctRegion").val());
          main_modal.hide();
        }});
      }
    });
  });
}
//==============================================================================
