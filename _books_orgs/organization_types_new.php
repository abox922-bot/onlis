<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$country_name = $_POST['country_name'] ?? '';
?>

<form id="formNew">
    <div class="row">

        <div class="col-12">
            <div class="form-context">
                <?php echo htmlspecialchars($country_name); ?>
            </div>
        </div>

        <div class="col-12 col-md-7">
            <label for="inpName" class="my-input-label">Название</label>
            <input type="text"
                class="form-in form-inp"
                id="inpName"
                data-name="type-name"
                data-type="text"
                data-required="1"
                autocomplete="off">
        </div>

        <div class="col-12 col-md-5">
            <label for="inpAbbreviation" class="my-input-label">Аббревиатура</label>
            <input type="text"
                class="form-in form-inp"
                id="inpAbbreviation"
                data-name="type-abbreviation"
                data-type="text"
                data-required="1"
                autocomplete="off">
        </div>

        <div class="col-12 mt-3">
            <div class="form-group-label">Особенности ОПФ</div>
        </div>

        <div class="col-12">
            <div class="form-check form-switch">
                <input class="form-check-input form-inp" type="checkbox" role="switch"
                    id="chckCanHaveBankAccount" data-name="can-have-bank-account" data-type="check">
                <label class="form-check-label" for="chckCanHaveBankAccount">Может использоваться банком</label>
            </div>
        </div>

        <div class="col-12 mt-2">
            <div class="form-check form-switch">
                <input class="form-check-input form-inp" type="checkbox" role="switch"
                    id="chckIsIndividual" data-name="is-individual" data-type="check">
                <label class="form-check-label" for="chckIsIndividual">Физическое лицо</label>
            </div>
        </div>

        <div class="col-12 mt-3">
            <button type="submit" class="btn-action-main" id="btnSave">
                <span id="btnSaveText">Сохранить</span>
                <div class="spinner-border spinner-border-sm d-none" id="divSaveLoading"></div>
            </button>
        </div>

    </div>
</form>
