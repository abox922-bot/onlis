if (localStorage.getItem('new_item') !== null) {
  localStorage.removeItem('new_item');
}
//==============================================================================
  $(document).ready(function(){
    fncStartFnc();
    listLoadFunction();
    $("#btnFastNew").click(function(){
      main_modal._config.backdrop = false;
      $("#mainModalLabel").text("Добавление пользователя");
      $("#mainModalBody").html(spnr_loading);
      main_modal.show();
      let path = new URL("./_books_users/users_new.php", url);
      $("#mainModalBody").load(path.href, function(){
        $("#inpGroup").change(function(){
          if (+$(this).val() == 2) {
            $("#chckCoach").prop("checked", true);
            $("#chckCoach").prop("disabled", true);
          } else {
            $("#chckCoach").prop("checked", false);
            $("#chckCoach").prop("disabled", false);
          }
        });
        $("#inpPhone").mask($("#inpPhone").data("phone-mask"));
        $("#formNew").submit(function(e){
          e.preventDefault();
          e.stopImmediatePropagation();
          let crt_arr = [];
          crt_arr = fncParamsCrt(".form-inp");
          if (crt_arr["all_good"] && confirm("Сохранить?")) {
            $("#btnSave").prop("disabled", true);
            $("#btnText, #divSaveLoading").toggleClass("d-none");
            fncMyAjax("new", "users", crt_arr["params"])
        			.done(function(data) {
                listLoadFunction();
                main_modal.hide();
        			})
        			.fail(function() {
                $("#btnText, #divSaveLoading").toggleClass("d-none");
                $("#btnSave").prop("disabled", false);
        			});
          }
        });
      });
    });
  });
//==============================================================================
function listLoadFunction() {
  $("#divContent").html(spnr_loading);
  let path = new URL("./_books_users/users_list.php", url);
	$("#divContent").load(path.href, function(){
    let actl = $("#btnActSlct").val();
    $(".itemTr").addClass("d-none");
    $(`.itemTr[data-actual=${actl}]`).removeClass("d-none");
    $(".itemTr").click(function(){
      infoLoadFunction(+$(this).attr("data-id"));
    });
	});
}
//==============================================================================
function infoLoadFunction(item_id) {
  let item_name = $(`.itemName[data-id=${item_id}]`).html();
  $("#mainModalBody").html(spnr_loading);
  $("#mainModalLabel").html(item_name);
  main_modal.show();
  let path = new URL("./_books_users/users_info.php", url);
  $("#mainModalBody").load(path.href, {id: item_id}, function() {
    //+++++++++++++++++++++++++++++++++++
    $("#inpPhone").mask($("#inpPhone").data("phone-mask"));
    //+++++++++++++++++++++++++++++++++++
    $("#btnNewLogin").click(function(){
      $("#inpLogin").val(getRandomCode(10000, 99999));
    });
    //+++++++++++++++++++++++++++++++++++
    $("#btnNewPin").click(function(){
      $("#inpPin").val(getRandomCode(1000, 9999));
    });
    //+++++++++++++++++++++++++++++++++++
    $("#formInfo").submit(function(e){
      e.preventDefault();
      e.stopImmediatePropagation();
      let params_arr = [];
      params_arr.push({name: "item-id", value: $("#formInfo").attr("data-id")});
      params_arr.push({name: "actual", value: $(".btnItemActual:checked").attr("data-target")});
      let crt_arr = fncParamsCrt(".form-inp", params_arr);
      if (crt_arr["all_good"] && confirm("Сохранить?")) {
        $("#btnSave").prop("disabled", true);
        $("#btnText, #divSaveLoading").toggleClass("d-none");
        fncMyAjax("upd", "users", crt_arr["params"])
          .done(function(data) {
            listLoadFunction();
            main_modal.hide();
          })
          .fail(function() {
            $("#btnText, #divSaveLoading").toggleClass("d-none");
            $("#btnSave").prop("disabled", false);
          });
      }
    });
    //+++++++++++++++++++++++++++++++++++
  });
}
//==============================================================================
