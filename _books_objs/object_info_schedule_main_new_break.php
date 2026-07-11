<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();
?>
<form id="formBreakNew">
    <div class="row">
        <div class="col-6 mb-3">
            <label class="my-input-label" for="inpBreakStart">Начало</label>
            <input type="time" class="form-in form-break-inp" data-name="start_time" data-type="text" data-required="1" id="inpBreakStart">
        </div>
        <div class="col-6 mb-3">
            <label class="my-input-label" for="inpBreakEnd">Окончание</label>
            <input type="time" class="form-in form-break-inp" data-name="end_time" data-type="text" data-required="1" id="inpBreakEnd">
        </div>
        <div class="col-12 mt-2 d-none" id="divBreakFormError">
            <div class="form-error-msg" id="spnBreakFormError"></div>
        </div>
        <div class="col-12 mt-2">
            <button type="submit" class="btn-action-main" id="btnBreakSave">
                <span id="btnBreakSaveText">Сохранить</span>
                <div class="spinner-border spinner-border-sm d-none" id="divBreakSaveLoading"></div>
            </button>
        </div>
    </div>
</form>
