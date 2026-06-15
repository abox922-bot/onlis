//==============================================================================
$(document).ready(function(){
    fncDatesLoad();
    //+++++++++++++++++++++++++++++++++++++++++++++++
    $(document).on("click", ".date-item", function(){
        $("#schRow").html(`<div class="col-12">${spnr_loading}</div>`);
        $(".date-item").removeClass("active");
        $(this).addClass("active");
        listLoadFunction();
    });
    //+++++++++++++++++++++++++++++++++++++++++++++++
    $(document).on("click", ".btnDatesChng", function(e){
        e.stopImmediatePropagation();
        fncDatesLoad($(this).data("date"));
    });
    //+++++++++++++++++++++++++++++++++++++++++++++++
    $(document).on("click", ".free-slot", function(){
      let room_name = $(this).data("name");
      let room_id = +$(this).data("id");
      let sub = $(".date-item.active").data("dow") + ", " + $(".date-item.active").data("day") + " " + $(".date-item.active").data("month");
      let book_time = +$(this).data("hour");
      let book_date = $(".date-item.active").data("date");
      $("#mainModalLabel").html("Бронирование слота");
      $("#mainModal").removeClass("modal-xl");
      $("#mainModalBody").html(spnr_loading);
      main_modal.show();
      let path = new URL("./_main/main_booking.php", url);
      $("#mainModalBody").load(path.href, {id: room_id, room_name, sub, book_time, book_date}, function(){
        $("#btnConfirmBooking").off("click").on("click", function(e){
          e.preventDefault();
          e.stopImmediatePropagation();
          let params_arr = [];
          params_arr.push({name: "id", value: room_id});
          params_arr.push({name: "date", value: book_date});
          params_arr.push({name: "time", value: book_time});
          let crt_arr = fncParamsCrt(".form-inp", params_arr);
          if (crt_arr["all_good"] && confirm("Сохранить?")) {
            fncMyAjax("new", "booking", crt_arr["params"])
        			.always(function() {
                listLoadFunction();
                main_modal.hide();
        			})
          }
        });
      });
    });
    //+++++++++++++++++++++++++++++++++++++++++++++++
    $(document).on("click", ".active-slot", function(){
      let room_name = $(this).data("name");
      let room_id = +$(this).data("id");
      let sub = $(".date-item.active").data("dow") + ", " + $(".date-item.active").data("day") + " " + $(".date-item.active").data("month");
      let book_time = +$(this).data("hour");
      let book_date = $(".date-item.active").data("date");
      let slot = $(this).data("slot");

      let modal_head = $(`.slot-training[data-slot=${slot}]`).html() + "<br><small>" + $(`.slot-trainer[data-slot=${slot}]`).html() + "</small>";
      $("#mainModalLabel").html(modal_head);
      $("#mainModal").removeClass("modal-xl");
      $("#mainModalBody").html(spnr_loading);
      main_modal.show();
      let path = new URL("./_main/main_slot_info.php", url);
      $("#mainModalBody").load(path.href, {id: room_id, room_name, sub, book_time, book_date, slot}, function(){
        $("#btnCancelBooking").off("click").on("click", function(e){
          e.preventDefault();
          e.stopImmediatePropagation();
          let params_arr = [];
          params_arr.push({name: "slot", value: +$(this).data("slot")});
          if (confirm("Отменить?")) {
            fncMyAjax("cancel", "booking", params_arr)
              .always(function() {
                listLoadFunction();
                main_modal.hide();
              })
          }
        });
      });
    });
    //+++++++++++++++++++++++++++++++++++++++++++++++
});
//==============================================================================
function fncDatesLoad(trg_date) {
  $("#divDates").css("height", $("#divDates").outerHeight());
  $("#schRow").html("");
  let path = new URL("./_main/main_dates_list.php", url);
  $("#divDates").fadeTo(150, 0, function(){
      $(this).load(path.href, {trg_date}, function(){
          // Снимаем фиксацию после загрузки
          $("#divDates").css("height", "");
          $(this).fadeTo(150, 1);
          // Загружаем основной контент
          if ($(".date-item.active").length == 1) {
            listLoadFunction();
            scrollToActive();
          }
      });
  });
}
//==============================================================================
function listLoadFunction() {
    $("#schRow").html(`<div class="col-12">${spnr_loading}</div>`);
    let path = new URL("./_main/main_list.php", url);
    $("#schRow").load(path.href, {date: $(".date-item.active").data("date")}, function(){
    });
}
//==============================================================================
function scrollToActive() {
    let container = $(".date-strip-container");
    let active = $(".date-item.active");

    if (active.length) {
        let scrollTo = active.position().left  // позиция элемента
                     + container.scrollLeft()  // текущий скролл
                     - container.width() / 2  // центрируем
                     + active.outerWidth() / 2;

        container.animate({ scrollLeft: scrollTo }, 200);
    }
}
//==============================================================================
