$(function(){

    listStaffLoad();

    $("#btnNewStaff").off("click").on("click", function(){
        let object_id = $("#inpObjectId").val();

        $("#modalOffcanvasLabel").html("Добавление сотрудника");
        $("#modalOffcanvasBody").html(spnr_loading);
        modalOffcanvas.show();
        let path = new URL("./_books_objs/object_info_staff_new.php", url);
        $("#modalOffcanvasBody").load(path.href, {id: object_id}, function(){
            $(".freeTr").off("click").on("click", async function(){
                let confirmed = await fncConfirm("Добавить сотрудника на объект?");
                if (confirmed) {
                    let user_id = $(this).data("id");
                    fncMyAjax("new_object_staff", "objs", [
                        {name: "object_id", value: object_id},
                        {name: "user_id", value: user_id}
                    ], 1)
                        .always(function(){
                            modalOffcanvas.hide();
                            listStaffLoad();
                        });
                }
            });
        });
    });

});
//==============================================================================
function listStaffLoad() {
    let object_id = $("#inpObjectId").val();
    $("#divStaffList").html(spnr_loading);
    let path = new URL("./_books_objs/object_info_staff_list.php", url);
    $("#divStaffList").load(path.href, {id: object_id}, function(){
        $(".staffTr").off("click").on("click", function(){
            let staff_id = $(this).data("id");
            let user_id  = $(this).data("user-id");
            let staff_name = $(`.staffName[data-id="${staff_id}"]`).first().text().trim();

            $("#modalOffcanvasLabel").html(staff_name);
            $("#modalOffcanvasBody").html(spnr_loading);
            modalOffcanvas.show();

            let path = new URL("./_books_objs/object_info_staff_info.php", url);
            $("#modalOffcanvasBody").load(path.href, {staff_id, user_id}, function(){

                $(".inline-tab-info").off("click").on("click", function(){
                    $(".inline-tab-info").removeClass("active");
                    $(this).addClass("active");
                    fncStaffInfoTabLoad($(this).data("target"));
                });

                fncStaffInfoTabLoad("main");

            });
        });
    });
}
//==============================================================================
function fncStaffInfoTabLoad(target) {
    $(".inline-tab-info").prop("disabled", true);
    $("#divStaffInfoContent").html(spnr_loading);

    let user_id = $("#hdnStaffUserId").val();
    let path = target === "person"
        ? new URL("../_books_users/users_info_person.php", url)
        : new URL(`./_books_objs/object_info_staff_info_${target}.php`, url);

    $("#divStaffInfoContent").load(path.href, {user_id}, function(){
        $(".inline-tab-info").prop("disabled", false);
        if (target === "person") {
            fncInitPersonForm(user_id, listStaffLoad);
        }
    });
}
//==============================================================================
