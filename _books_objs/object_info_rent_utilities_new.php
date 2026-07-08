<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();
?>
<form id="formNewUtility">
    <div class="row">
        <div class="col-12 mb-3">
            <label class="my-input-label" for="inpUtilityName">Название</label>
            <input type="text"
                class="form-in form-inp"
                id="inpUtilityName"
                data-name="name"
                data-type="text"
                data-required="1"
                autocomplete="off">
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
