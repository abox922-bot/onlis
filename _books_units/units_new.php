<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();
?>
<form id="formNew">
    <div class="row">
        <div class="col-12 col-md-6 mb-3">
            <label for="inpName" class="my-input-label">Название</label>
            <input type="text"
                class="form-in form-inp"
                id="inpName"
                data-name="name"
                data-type="text"
                data-required="1"
                autocomplete="off">
        </div>
        <div class="col-12 col-md-6 mb-3">
            <label for="inpShortName" class="my-input-label">Краткое обозначение</label>
            <input type="text"
                class="form-in form-inp"
                id="inpShortName"
                data-name="short_name"
                data-type="text"
                autocomplete="off">
        </div>
        <div class="col-12 mb-3">
            <div class="form-check form-switch">
                <input class="form-check-input form-inp" type="checkbox" role="switch"
                    id="chckIsFloat" data-name="is_float" data-type="check">
                <label class="form-check-label" for="chckIsFloat">Дробное значение</label>
            </div>
        </div>
        <div class="col-12 mt-2 d-none" id="divFormError">
            <div class="form-error-msg" id="spnFormError"></div>
        </div>
        <div class="col-12 mt-3">
            <button type="submit" class="btn-action-main" id="btnSave">
                <span id="btnSaveText">Сохранить</span>
                <div class="spinner-border spinner-border-sm d-none" id="divSaveLoading"></div>
            </button>
        </div>
    </div>
</form>
