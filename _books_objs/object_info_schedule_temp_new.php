<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();
?>
<form id="formTempNew">
    <div class="row">
        <div class="col-6 mb-3">
            <label class="my-input-label" for="inpTempFrom">С даты</label>
            <input type="date" class="form-in form-inp" id="inpTempFrom" data-name="valid_from" data-type="text" data-required="1">
        </div>
        <div class="col-6 mb-3">
            <label class="my-input-label" for="inpTempTo">По дату</label>
            <input type="date" class="form-in form-inp" id="inpTempTo" data-name="valid_to" data-type="text" data-required="1">
        </div>

        <div class="col-6 mb-3">
            <label class="my-input-label" for="inpTempStart">Начало</label>
            <input type="time" class="form-in form-inp" id="inpTempStart" data-name="start_time" data-type="text">
        </div>
        <div class="col-6 mb-3">
            <label class="my-input-label" for="inpTempEnd">Окончание</label>
            <input type="time" class="form-in form-inp" id="inpTempEnd" data-name="end_time" data-type="text">
        </div>

        <div class="col-12 mb-3">
            <div class="form-check form-switch">
                <input class="form-check-input form-inp" type="checkbox" role="switch" id="chckTempAllDay" data-name="is_all_day" data-type="check">
                <label class="form-check-label" for="chckTempAllDay">Круглосуточно</label>
            </div>
        </div>
        <div class="col-12 mb-3">
            <div class="form-check form-switch">
                <input class="form-check-input form-inp" type="checkbox" role="switch" id="chckTempDayOff" data-name="is_day_off" data-type="check">
                <label class="form-check-label" for="chckTempDayOff">Выходной</label>
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
