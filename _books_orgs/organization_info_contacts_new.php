<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();
?>

<form id="formNew">
    <div class="row">

        <div class="col-12 mb-3">
            <label for="inpContactName" class="my-input-label">Имя</label>
            <input type="text" class="form-in form-inp" id="inpContactName"
                data-name="contact-name" data-type="text" data-required="1"
                autocomplete="off">
        </div>

        <div class="col-12 mb-3">
            <label for="inpContactPosition" class="my-input-label">Должность</label>
            <input type="text" class="form-in form-inp" id="inpContactPosition"
                data-name="contact-position" data-type="text"
                autocomplete="off">
        </div>

        <div class="col-12 col-md-6 mb-3">
            <label for="inpContactPhone" class="my-input-label">Телефон</label>
            <input type="text" class="form-in form-inp" id="inpContactPhone"
                data-name="contact-phone" data-type="text"
                placeholder="+7 999 123 45 67"
                autocomplete="off">
        </div>

        <div class="col-12 col-md-6 mb-3">
            <label for="inpContactEmail" class="my-input-label">Email</label>
            <input type="text" class="form-in form-inp" id="inpContactEmail"
                data-name="contact-email" data-type="email"
                autocomplete="off">
        </div>

        <div class="col-12 mb-3">
            <label for="inpContactNote" class="my-input-label">Примечание</label>
            <input type="text" class="form-in form-inp" id="inpContactNote"
                data-name="contact-note" data-type="text"
                autocomplete="off">
        </div>

        <div class="col-12 mt-2 d-none" id="divFormError">
            <div class="form-error-msg" id="spnFormError"></div>
        </div>

        <div class="col-12">
            <button type="submit" class="btn-action-main" id="btnSave">
                <span id="btnSaveText">Сохранить</span>
                <div class="spinner-border spinner-border-sm d-none" id="divSaveLoading"></div>
            </button>
        </div>

    </div>
</form>
