//==============================================================================
const main_modal = new bootstrap.Modal('#mainModal');
const myOffcanvas = new bootstrap.Offcanvas('#myOffcanvas');
const modalOffcanvas = new bootstrap.Offcanvas('#modalOffcanvas');
//==============================================================================
$(document).ready(function(){
	$('body, html').animate({scrollTop: 0}, 100, "linear");

	const $startItem = $(".link-item[data-onload=1]");
	if ($startItem.length) {
	    fncChptLoad($startItem.attr("data-module"), $startItem.attr("data-ttl"));
	}
	//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	$("#mainModal").on("shown.bs.modal", function(){
		$("html").css("overflow-y", "hidden");
	});
	//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	$("#mainModal").on("hidden.bs.modal", function(){
		main_modal._config.backdrop = true;
		$("html").css("overflow-y", "auto");
		$("#mainModal").addClass("modal-xl");
		$(".modal-dialog").addClass("modal-dialog-centered");
		$("#mainModalBody").html("");
	});
	//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	$(".my-menu-div-btn").click(function(){
		$(".my-nav-item_second_level").addClass("d-none");
		myOffcanvas.show();
	});
	//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	$(document).click(function(e){
		if (e.target.closest(".head-item")) {
		    const target = $(e.target).closest(".head-item").attr("data-target");
		    $(`.my-nav-item_second_level[data-target!=${target}]`).addClass("d-none");
		    $(`.my-nav-item_second_level[data-target=${target}]`).toggleClass("d-none");
		} else if (e.target.closest(".link-item")) {
			myOffcanvas.hide();
	    const $item = $(e.target).closest(".link-item");
	    fncChptLoad($item.attr("data-module"), $item.attr("data-ttl"));
		} else if (e.target.closest("#spnQuit")) {
			myOffcanvas.hide();
			if (confirm("Выйти из системы?")) {
				fncMyAjax("close_ses", "main")
					.always(function () {
						window.location.reload();
					});
			}
		}
	});
	//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	$("#menuButtonProfile").click(function(){
	  $("#mainModalBody").html(spnr_loading);
	  $("#mainModalLabel").html("Профиль");
	  main_modal.show();
	  let path = new URL("main_profile.php", url);
	  $("#mainModalBody").load(path.href, function() {
	    //+++++++++++++++++++++++++++++++++++
	    $("#inpPhone").mask($("#inpPhone").data("phone-mask"));
	    //+++++++++++++++++++++++++++++++++++
			$("#btnNewLogin").off("click").on("click", function(){
				$("#inpLogin").val(getRandomCode(10000, 99999));
			});
			//+++++++++++++++++++++++++++++++++++
			$("#btnNewPin").off("click").on("click", function(){
				$("#inpPin").val(getRandomCode(1000, 9999));
			});
			//+++++++++++++++++++++++++++++++++++
	    $("#formInfo").off("submit").on("submit", function(e){
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
	});
});
//==============================================================================
function fncStartFnc() {
  $("#btnActSlct").html($(".liActListName:first").html());
  $("#btnActSlct").val($(".liActListName:first").attr("data-val"));
  //++++++++++++++++++++++++++++++++++++
  $(".liAct").click(function(){
    let actl = $(this).attr("data-val");
    $("#btnActSlct").html($(`.liActListName[data-val=${actl}]`).html());
    $(".itemTr").addClass("d-none");
    $(`.itemTr[data-actual=${actl}]`).removeClass("d-none");
    $("#btnActSlct").val($(this).attr("data-val"));
  });
  //++++++++++++++++++++++++++++++++++++
}
//==============================================================================
function fncBookNav() {
	const $shell = $("#divModuleShell");
	const folder = $shell.data("folder");

	if ($("#btnSlct").length == 1) {
		const target = $("#btnSlct").data("target");
		$("#rowContent").html(`<div class="col-12">${spnr_loading}</div>`);
		let path = new URL(`./${folder}/${target}.php`, url);
		$("#rowContent").load(path.href);
		//++++++++++++++++++++++++++++++++++++
		$(".liSlct").click(function(){
			let chpt = $(this).attr("data-target");
			$("#btnSlct").html($(this).find(".liSlctItem").html());
			$("#btnSlct").attr("data-target", chpt);
			$("#rowContent").html(`<div class="col-12">${spnr_loading}</div>`);
			let path = new URL(`./${folder}/${chpt}.php`, url);
			$("#rowContent").load(path.href);
		});
		//++++++++++++++++++++++++++++++++++++
	} else {
		const default_file = $shell.data("default");
		$("#rowContent").html(`<div class="col-12">${spnr_loading}</div>`);
		let path = new URL(`./${folder}/${default_file}.php`, url);
		$("#rowContent").load(path.href);
	}
}//==============================================================================
function fncChptLoad(module_key, chpt_header) {
	$("#divMainContent").html(`<div class="col-12">${spnr_loading}</div>`);
	$("#sectionHeader").html(chpt_header);
	let path = new URL(`main_module.php?module=${module_key}`, url);
	$("#divMainContent").load(path.href, function(){
		$('body, html').animate({scrollTop: 0}, 100, "linear");
		fncBookNav();
	});
}
//==============================================================================
function searchFunction() {
	$("#inpSearchVal").keyup(function() {
		let val = $("#inpSearchVal").val();
		let str;
		if (val.length > 0) {
			$(".itemTr").addClass("d-none");
			val = val.toLowerCase();
			$(".itemTr").each(function() {
				str = $(this).html().toLowerCase();
				if (str.includes(val)) {
					$(this).removeClass("d-none");
				}
			});
		} else if (val.length == 0) {
			$(".itemTr").removeClass("d-none");
		}
	});
}
//==============================================================================
function getRandomCode(min, max) {
  return Math.floor(Math.random() * (max - min + 1)) + min;
}
//==============================================================================
