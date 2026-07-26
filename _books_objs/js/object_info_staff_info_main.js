$(function(){
  $("#btnDismissObjectStaff").off("click").on("click", async function(){
      let confirmed = await fncConfirm("Снять сотрудника с объекта?");
      if (confirmed) {
          let staff_id = $("#hdnStaffId").val();
          fncMyAjax("del_object_staff", "objs", [{name: "id", value: staff_id}], 1)
              .always(function(){
                  modalOffcanvas.hide();
                  listStaffLoad();
              });
      }
  });
});
