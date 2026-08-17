$(function(){

    let nomenclature_id = +$("#inpPackageNomenclatureId").val();

    if (canDo('nomenclature.manage')) {
        $("#btnAddPackage").off("click").on("click", function(){
            $("#modalOffcanvasLabel").html("Новая упаковка");
            $("#modalOffcanvasBody").html(spnr_loading);
            modalOffcanvas.show();
            let path = new URL("./_books_noms/nomenclature_info_packages_new.php", url);
            $("#modalOffcanvasBody").load(path.href, {nomenclature_id: nomenclature_id}, function(){
                $("#formPackageNew").submit(function(e){
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    let params_arr = [];
                    params_arr.push({name: "nomenclature_id", value: nomenclature_id});
                    let crt_arr = fncParamsCrt(".form-inp", params_arr);
                    if (crt_arr["all_good"]) {
                        $("#btnSave").prop("disabled", true);
                        $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
                        fncMyAjax("new_package", "noms", crt_arr["params"], 1)
                            .done(function(data){
                                if (data.sccss) {
                                    modalOffcanvas.hide();
                                    fncPackagesListLoad(nomenclature_id);
                                    fncMarkItemEdited(nomenclature_id);
                                } else {
                                    fncBtnReset();
                                    fncShowFormError(data.msg ?? "Проверьте введённые данные");
                                }
                            })
                            .fail(function(){ fncBtnReset(); });
                    }
                });
            });
        });
    }

    $(".packageTr").off("click").on("click", function(){
        fncPackageInfoLoad($(this).data("id"), nomenclature_id);
    });

});

// ─────────────────────────────────────────────────────────────────────────────

function fncPackagesListLoad(nomenclature_id) {
    fncNomenclatureTabLoad(nomenclature_id, "packages");
}

// ─────────────────────────────────────────────────────────────────────────────

function fncPackageInfoLoad(package_id, nomenclature_id) {
    let package_name = $(`.packageName[data-id="${package_id}"]`).first().text().trim();

    $("#modalOffcanvasLabel").html(package_name);
    $("#modalOffcanvasBody").html(spnr_loading);
    modalOffcanvas.show();
    let path = new URL("./_books_noms/nomenclature_info_packages_info.php", url);
    $("#modalOffcanvasBody").load(path.href, {id: package_id}, function(){
        if (!canDo('nomenclature.manage')) {
            $("#btnSave").hide();
            $("#formPackageInfo").off("submit");
        } else {
            $("#formPackageInfo").submit(function(e){
                e.preventDefault();
                e.stopImmediatePropagation();
                let params_arr = [];
                params_arr.push({name: "id", value: package_id});
                let crt_arr = fncParamsCrt(".form-inp", params_arr);
                if (crt_arr["all_good"]) {
                    $("#btnSave").prop("disabled", true);
                    $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
                    fncMyAjax("upd_package", "noms", crt_arr["params"], 1)
                        .done(function(data){
                            if (data.sccss) {
                                modalOffcanvas.hide();
                                fncPackagesListLoad(nomenclature_id);
                                fncMarkItemEdited(nomenclature_id);
                            } else {
                                fncBtnReset();
                                fncShowFormError(data.msg ?? "Проверьте введённые данные");
                            }
                        })
                        .fail(function(){ fncBtnReset(); });
                }
            });
        }
    });
}
