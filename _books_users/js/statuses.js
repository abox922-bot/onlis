//==============================================================================
$(document).ready(function(){
  listLoadFunction();
  //~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
  $("#btnFastNew").click(function(){
    $("#mainModalBody").html(spnr_loading);
    $("#mainModalLabel").html("Добавление должности");
    let path = new URL("./_books_users/statuses_new.php", url);
    $("#mainModalBody").load(path.href, function(){
      main_modal.show();
      $("#formNew").submit(function(e){
        e.preventDefault();
        let crt_arr = fncParamsCrt(".form-inp");
        if (crt_arr["all_good"] && confirm("Сохранить?")) {
          $("#btnSave, #divSaveLoading").toggleClass("d-none");
          $.ajax({type: "POST",	url: rqst_path.href, data: {params: JSON.stringify(crt_arr["params"]), action: "new_status", module: "users", return_data: 0},	success: function(){
            listLoadFunction();
            main_modal.hide();
          }});
        }
      });
    });
  });
  //~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
});
//==============================================================================
function listLoadFunction() {
	$("#divChptContent").html(spnr_loading);
	let path = new URL("./_books_users/statuses_list.php", url);
	$("#divChptContent").load(path.href, function(){
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
  $("#mainModalLabel").html(`<small class="fw-normal">Информация о должности</small><br>${item_name}`);
  let path = new URL("./_books_users/statuses_info.php", url);
  $("#mainModalBody").load(path.href, {id: item_id}, function(){
    main_modal.show();
    $("#formInfo").submit(function(e){
      e.preventDefault();
      let params_arr = [];
      params_arr.push({name: "item-id", value: item_id});
      let crt_arr = fncParamsCrt(".form-inp", params_arr);
      if (crt_arr["all_good"] && confirm("Сохранить?")) {
        $("#btnSave, #divSaveLoading").toggleClass("d-none");
        $.ajax({type: "POST",	url: rqst_path.href, data: {params: JSON.stringify(crt_arr["params"]), action: "upd_status", module: "users", return_data: 0},	success: function(){
          listLoadFunction();
          main_modal.hide();
        }});
      }
    });
  });
}
//==============================================================================
