<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$nomenclature_id = (int)($_POST['nomenclature_id'] ?? 0);

$products = send_request(array_merge($ses_info, ['action' => 'product_additions_available', 'nomenclature_id' => $nomenclature_id]), 'noms');
if (!is_array($products) || isset($products['sccss'])) {
    $products = [];
}
?>
<form id="formAdditionNew">
    <div class="row">
        <div class="col-12 mb-3">
            <label for="slctAddition" class="my-input-label">Дополнение</label>
            <select class="form-in form-inp" id="slctAddition" data-name="addition_id" data-type="select" data-required="1">
                <option value="0">Выберите товар</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?php echo (int)$product['id']; ?>"><?php echo htmlspecialchars($product['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12 mb-3">
            <label for="inpAdditionMaxQuantity" class="my-input-label">Максимальное количество</label>
            <input type="text"
                class="form-in form-inp"
                id="inpAdditionMaxQuantity"
                data-name="max_quantity"
                data-type="digits_double"
                placeholder="Без ограничения"
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
