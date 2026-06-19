//==============================================================================
$(document).ready(function(){
  //~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
  $("#inpCity").click(function() {
    $(".trCitySrch").removeClass("d-none");
    $("#rowCitiesList").removeClass("d-none");
    if($("#inpCity").val().length > 0){
      $("#rowCitiesList").removeClass("d-none");
      let val = $("#inpCity").val();
      let str;
      $(".trCitySrch").addClass("d-none");
      val = val.toLowerCase();
      $(".tdCitySrch").each(function(){
        str = $(this).html().toLowerCase();
        if(str.includes(val)) {
          let td_id = $(this).attr("data-id");
          $(`.trCitySrch[data-id=${td_id}]`).removeClass("d-none");
        }
      });
    } else if ($("#inpCity").val().length == 0) {
      $(".trCitySrch").removeClass("d-none");
    }
  });
  //~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
  $("#inpCity").keyup(function() {
    $(".trCitySrch").attr("data-selected", 0);
    if($("#inpCity").val().length > 0){
      $("#rowCitiesList").removeClass("d-none");
      let val = $("#inpCity").val();
      let str;
      $(".trCitySrch").addClass("d-none");
      val = val.toLowerCase();
      $(".tdCitySrch").each(function(){
        str = $(this).html().toLowerCase();
        if(str.includes(val)) {
          let td_id = $(this).attr("data-id");
          $(`.trCitySrch[data-id=${td_id}]`).removeClass("d-none");
        }
      });
    } else if ($("#inpCity").val().length == 0) {
      $(".trCitySrch").removeClass("d-none");
    }
  });
  //~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
  $(".trCitySrch").click(function() {
    $(".trCitySrch").attr("data-selected", 0);
    $(this).attr("data-selected", 1);
    let td_id = $(this).attr("data-id");
    $("#inpCity").val($(`.tdCitySrch[data-id=${td_id}]`).html());
    $("#rowCitiesList").addClass("d-none");
    listLoadFunction(td_id);
    $("#btnFastNew").prop("disabled", false);
  });
  //~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
  $("#btnFastNew").click(function(){
    let country = +$(".trCitySrch[data-selected=1]").attr("data-country");
    let region = +$(".trCitySrch[data-selected=1]").attr("data-region");
    let city = +$(".trCitySrch[data-selected=1]").attr("data-id");
    let city_name = $(`.tdCityReg[data-id=${city}]`).html() + ", " + $(`.tdCitySrch[data-id=${city}]`).html();
    $("#mainModalBody").html(spnr_loading);
    $("#mainModalLabel").html("Добавление улицы");
    let path = new URL("./_books_geo/streets_new.php", url);
    $("#mainModalBody").load(path.href, {city_name}, function(){
      main_modal.show();
      $("#formNew").submit(function(e){
        e.preventDefault();
        let params_arr = [];
        params_arr.push({name: "country", value: country});
        params_arr.push({name: "region", value: region});
        params_arr.push({name: "city", value: city});
        let crt_arr = fncParamsCrt(".form-inp", params_arr);
        if (crt_arr["all_good"] && confirm("Сохранить?")) {
          $("#btnSave, #divSaveLoading").toggleClass("d-none");
          $.ajax({type: "POST",	url: rqst_path.href, data: {params: JSON.stringify(crt_arr["params"]), action: "new_street", module: "geo", return_data: 0},	success: function(data){
            listLoadFunction(+$(".trCitySrch[data-selected=1]").attr("data-id"));
            main_modal.hide();
          }});
        }
      });
    });
  });
  //~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
});
//==============================================================================
function listLoadFunction(city) {
  $("#divChptContent").removeClass("d-none");
  $("#divChptContent").html(spnr_loading);
  let path = new URL("./_books_geo/streets_list.php", url);
	$("#divChptContent").load(path.href, {city}, function(){
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
  let city = +$(".trCitySrch[data-selected=1]").attr("data-id");
  let city_name = $(`.tdCityReg[data-id=${city}]`).html() + ", " + $(`.tdCitySrch[data-id=${city}]`).html();
  let item_name = $(`.itemName[data-id=${item_id}]`).html();
  $("#mainModalBody").html(spnr_loading);
  $("#mainModalLabel").html(`<small class="fw-normal">Информация об улице</small><br>${item_name}`);
  let path = new URL("./_books_geo/streets_info.php", url);
  $("#mainModalBody").load(path.href, {id: item_id, city_name}, function(){
    main_modal.show();
    $("#formInfo").submit(function(e){
      e.preventDefault();
      let params_arr = [];
      params_arr.push({name: "item-id", value: item_id});
      let crt_arr = fncParamsCrt(".form-inp", params_arr);
      if (crt_arr["all_good"] && confirm("Сохранить?")) {
        $("#btnSave, #divSaveLoading").toggleClass("d-none");
        $.ajax({type: "POST",	url: rqst_path.href, data: {params: JSON.stringify(crt_arr["params"]), action: "upd_street", module: "geo", return_data: 0},	success: function(){
          listLoadFunction(+$(".trCitySrch[data-selected=1]").attr("data-id"));
          main_modal.hide();
        }});
      }
    });
  });
}
//==============================================================================
