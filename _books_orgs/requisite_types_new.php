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

        <div class="col-12 col-md-6">
            <label for="inpName" class="my-input-label">Название</label>
            <input type="text"
                class="form-in form-inp"
                id="inpName"
                data-name="req-name"
                data-type="text"
                data-required="1"
                autocomplete="off">
        </div>

        <div class="col-12 col-md-6">
            <label for="slctValueType" class="my-input-label">Тип значения</label>
            <select class="form-in form-inp" id="slctValueType" data-name="req-value-type" data-type="select" data-required="1">
                <option value="0">Выберите</option>
                <option value="text">Текст</option>
                <option value="digits">Цифры</option>
                <option value="date">Дата</option>
            </select>
        </div>

        <div class="col-12 mt-3">
            <div class="form-group-label">Правила значения</div>
        </div>

        <div class="col-12" id="rowLengthControl">
            <div class="form-check form-switch">
                <input class="form-check-input form-inp" type="checkbox" role="switch"
                    id="chckLengthControl" data-name="has-length-control" data-type="check">
                <label class="form-check-label" for="chckLengthControl">Контроль длины значения</label>
            </div>
            <div class="text-muted" style="font-size: 0.8rem; margin-left: 2.5rem;">
                Точная длина задаётся отдельно для каждой ОПФ
            </div>
        </div>

        <div class="col-12 mt-2">
            <div class="form-check form-switch">
                <input class="form-check-input form-inp" type="checkbox" role="switch"
                    id="chckUnique" data-name="is-unique" data-type="check">
                <label class="form-check-label" for="chckUnique">Только уникальные значения</label>
            </div>
        </div>

        <div class="col-12 mt-3">
            <div class="form-check form-switch">
                <input class="form-check-input form-inp" type="checkbox" role="switch"
                    id="chckBankOnly" data-name="is-bank-only" data-type="check">
                <label class="form-check-label" for="chckBankOnly">Только для банков</label>
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
