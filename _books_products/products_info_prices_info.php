<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id = (int)($_POST['id'] ?? 0);

$info = send_request(array_merge($ses_info, ['action' => 'price_variation_info', 'id' => $id]), 'noms');
if (!is_array($info) || empty($info)) {
    echo '<div class="empty-hint"><div class="empty-hint__text">Цена не найдена</div></div>';
    exit;
}

$context_label = $info['object_type_id']
    ? htmlspecialchars($info['object_type_name']) . ' <small class="text-muted">(тип объекта)</small>'
    : htmlspecialchars($info['object_name']) . ' <small class="text-muted">(объект)</small>';
?>
<form id="formPriceInfo">
    <div class="row">
        <div class="col-12 mb-3">
            <div class="form-context"><?php echo $context_label; ?></div>
        </div>

        <div class="col-12 mb-3">
            <label for="inpVariationPrice" class="my-input-label">Цена</label>
            <input type="text"
                class="form-in form-inp"
                id="inpVariationPrice"
                data-name="price"
                data-type="digits_double"
                data-required="1"
                value="<?php echo htmlspecialchars($info['price']); ?>"
                autocomplete="off">
        </div>

        <div class="col-12 mt-2 d-none" id="divFormError">
            <div class="form-error-msg" id="spnFormError"></div>
        </div>
        <div class="col-12 mt-3 d-flex gap-2">
            <button type="submit" class="btn-action-main" id="btnSave">
                <span id="btnSaveText">Сохранить</span>
                <div class="spinner-border spinner-border-sm d-none" id="divSaveLoading"></div>
            </button>
            <button type="button" class="btn-danger-action" id="btnDeletePriceVariation">Удалить</button>
        </div>
    </div>
</form>
