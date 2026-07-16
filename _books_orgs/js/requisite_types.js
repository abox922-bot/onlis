$(function(){

    if (!canDo('organizations.manage')) {
        $("#btnFastNew").hide();
    }

    // Инициализация Tom Select для фильтра страны
    window.countryPicker = new TomSelect("#slctCountry", {
        maxOptions: null,
        plugins: ["clear_button"],
        render: {
            item: function(data, escape) {
                return `<div class="item">${escape(data.text)}</div>`;
            }
        },
        onChange: function(value) {
            if (value) {
                $("#divEmptyHint").addClass("d-none");
                $("#btnFastNew").prop("disabled", false);
                listLoadFunction(value);
                window.countryPicker.blur();
            } else {
                $("#divChptContent").addClass("d-none").html("");
                $("#divEmptyHint").removeClass("d-none");
                $("#btnFastNew").prop("disabled", true);
            }
        }
    });

    // Кнопка добавить
    if (canDo('organizations.manage')) {
        $("#btnFastNew").on("click", function(){
            let country_id = window.countryPicker.getValue();
            let country_name = window.countryPicker.getOption(country_id).textContent;
            $("#mainModalBody").html(spnr_loading);
            $("#mainModalLabel").html("Новый реквизит");
            main_modal.show();
            let path = new URL("./_books_orgs/requisite_types_new.php", url);
            $("#mainModalBody").load(path.href, {country_name}, function(){
                valTypeToggle();
                if (!canDo('organizations.manage')) {
                    $("#btnSave").hide();
                    $("#formNew").off("submit");
                } else {
                    $("#formNew").submit(function(e){
                        e.preventDefault();
                        e.stopImmediatePropagation();
                        let params_arr = [];
                        params_arr.push({name: "country-id", value: country_id});
                        let crt_arr = fncParamsCrt(".form-inp", params_arr);
                        if (crt_arr["all_good"]) {
                            $("#btnSave").prop("disabled", true);
                            $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
                            fncMyAjax("new_requisite_type", "orgs", crt_arr["params"], 1)
                                .done(function(data){
                                    if (data.sccss) {
                                        main_modal.hide();
                                    } else {
                                        fncBtnReset();
                                    }
                                })
                                .fail(function(){
                                    fncBtnReset();
                                })
                                .always(function(){
                                    listLoadFunction(country_id);
                                });
                        }
                    });
                }
            });
        });
    }

});

// ─────────────────────────────────────────────────────────────────────────────

function listLoadFunction(country_id) {
    $("#divChptContent").removeClass("d-none").html(spnr_loading);
    let path = new URL("./_books_orgs/requisite_types_list.php", url);
    $("#divChptContent").load(path.href, {country_id}, function(){
        searchFunction();
        $(".itemTr").off("click").on("click", function(){
            infoLoadFunction($(this).data("id"));
        });
        fncCheckNewItem(infoLoadFunction);
    });
}

// ─────────────────────────────────────────────────────────────────────────────

function infoLoadFunction(item_id) {
    let item_name = $(`.itemName[data-id="${item_id}"]`).html();
    $("#mainModalBody").html(spnr_loading);
    $("#mainModalLabel").html(item_name);
    main_modal.show();
    let path = new URL("./_books_orgs/requisite_types_info.php", url);
    $("#mainModalBody").load(path.href, {id: item_id}, function(){
        valTypeToggle();
        if (!canDo('organizations.manage')) {
            $("#btnSave").hide();
            $("#formInfo").off("submit");
        } else {
            $("#formInfo").submit(function(e){
                e.preventDefault();
                e.stopImmediatePropagation();
                let params_arr = [];
                params_arr.push({name: "item-id", value: item_id});
                let crt_arr = fncParamsCrt(".form-inp", params_arr);
                if (crt_arr["all_good"]) {
                    $("#btnSave").prop("disabled", true);
                    $("#btnSaveText, #divSaveLoading").toggleClass("d-none");
                    fncMyAjax("upd_requisite_type", "orgs", crt_arr["params"], 0)
                        .done(function(){
                            main_modal.hide();
                        })
                        .fail(function(){
                            fncBtnReset();
                        })
                        .always(function(){
                            let country_id = window.countryPicker.getValue();
                            listLoadFunction(country_id);
                        });
                }
            });
        }
    });
}

// ─────────────────────────────────────────────────────────────────────────────

// Логика переключения полей формы при смене value_type
function valTypeToggle() {
    $("#slctValueType").off("change").on("change", function(){
        let val = $(this).val();
        if (val === "date") {
            // Для даты контроль длины не нужен
            $("#rowLengthControl").addClass("d-none");
            $("#chckLengthControl").prop("checked", false);
        } else {
            // text и digits — контроль длины доступен
            $("#rowLengthControl").removeClass("d-none");
        }
    });
}

// ─────────────────────────────────────────────────────────────────────────────
