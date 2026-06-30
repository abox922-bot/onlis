<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id = (int)($_POST['id'] ?? 0);

$result = send_request(array_merge($ses_info, [
    'action' => 'requisite_type_info',
    'id'     => $id,
]), 'orgs');

if (!is_array($result) || isset($result['sccss'])) {
    $result = [];
}
?>

<form id="formInfo">
    <div class="row">

        <div class="col-12">
            <div class="form-context">
                <?php echo htmlspecialchars($result['country_name'] ?? ''); ?>
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
                value="<?php echo htmlspecialchars($result['name'] ?? ''); ?>"
                autocomplete="off">
        </div>

        <div class="col-12 col-md-6">
            <label for="slctValueType" class="my-input-label">Тип значения</label>
            <select class="form-in form-inp" id="slctValueType" data-name="req-value-type" data-type="select" data-required="1">
                <option value="0">Выберите</option>
                <option value="text"   <?php echo ($result['value_type'] ?? '') === 'text'   ? 'selected' : ''; ?>>Текст</option>
                <option value="digits" <?php echo ($result['value_type'] ?? '') === 'digits' ? 'selected' : ''; ?>>Цифры</option>
                <option value="date"   <?php echo ($result['value_type'] ?? '') === 'date'   ? 'selected' : ''; ?>>Дата</option>
            </select>
        </div>

        <div class="col-12 mt-3">
            <div class="form-group-label">Правила значения</div>
        </div>

        <div class="col-12 <?php echo ($result['value_type'] ?? '') === 'date' ? 'd-none' : ''; ?>" id="rowLengthControl">
            <div class="form-check form-switch">
                <input class="form-check-input form-inp" type="checkbox" role="switch"
                    id="chckLengthControl" data-name="has-length-control" data-type="check"
                    <?php echo !empty($result['has_length_control']) ? 'checked' : ''; ?>
                    <?php echo ($result['value_type'] ?? '') === 'date' ? 'disabled' : ''; ?>>
                <label class="form-check-label" for="chckLengthControl">Контроль длины значения</label>
            </div>
            <div class="text-muted" style="font-size: 0.8rem; margin-left: 2.5rem;">
                Точная длина задаётся отдельно для каждой ОПФ
            </div>
        </div>
        
        <div class="col-12 mt-2">
            <div class="form-check form-switch">
                <input class="form-check-input form-inp" type="checkbox" role="switch"
                    id="chckUnique" data-name="is-unique" data-type="check"
                    <?php echo !empty($result['is_unique']) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="chckUnique">Только уникальные значения</label>
            </div>
        </div>

        <div class="col-12 mt-3">
            <div class="form-check form-switch">
                <input class="form-check-input form-inp" type="checkbox" role="switch"
                    id="chckBankOnly" data-name="is-bank-only" data-type="check"
                    <?php echo !empty($result['is_bank_only']) ? 'checked' : ''; ?>>
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
