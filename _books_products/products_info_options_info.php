<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id = (int)($_POST['id'] ?? 0);

$info = send_request(array_merge($ses_info, ['action' => 'option_info', 'id' => $id]), 'noms');
if (!is_array($info) || empty($info)) {
    echo '<div class="empty-hint"><div class="empty-hint__text">Опция не найдена</div></div>';
    exit;
}

$products = send_request(array_merge($ses_info, ['action' => 'option_products_available', 'exclude_option_id' => $id, 'nomenclature_id' => $info['nomenclature_id']]), 'noms');
if (!is_array($products) || isset($products['sccss'])) {
    $products = [];
}
?>
<input type="hidden" id="inpOptionId" value="<?php echo (int)$info['id']; ?>">
<form id="formOptionInfo">
    <div class="row">
        <div class="col-12 mb-3">
            <label for="inpOptionName" class="my-input-label">Название</label>
            <input type="text"
                class="form-in form-inp"
                id="inpOptionName"
                data-name="name"
                data-type="text"
                data-required="1"
                value="<?php echo htmlspecialchars($info['name']); ?>"
                autocomplete="off">
        </div>

        <div class="col-12 mb-3">
            <label for="slctOptionProduct" class="my-input-label">Привязанный товар</label>
            <select class="form-in form-inp" id="slctOptionProduct" data-name="product_id">
                <option value="0">Без товара (раздел с опциями)</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?php echo (int)$product['id']; ?>" <?php echo ((int)$product['id'] === (int)$info['product_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($product['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12 mt-2 d-none" id="divFormError">
            <div class="form-error-msg" id="spnFormError"></div>
        </div>
        <div class="col-12 mt-3 d-flex gap-2">
            <button type="submit" class="btn-action-main" id="btnSave">
                <span id="btnSaveText">Сохранить</span>
                <div class="spinner-border spinner-border-sm d-none" id="divSaveLoading"></div>
            </button>
            <button type="button" class="btn-danger-action" id="btnDeleteOption">Удалить</button>
        </div>
    </div>
</form>
