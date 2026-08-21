<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();
require_once('../modules_fncs.php');

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$nomenclature_id = (int)($_POST['nomenclature_id'] ?? 0);

$products = send_request(array_merge($ses_info, ['action' => 'option_products_available', 'nomenclature_id' => $nomenclature_id]), 'noms');
if (!is_array($products) || isset($products['sccss'])) {
    $products = [];
}

$parents_tree = send_request(array_merge($ses_info, ['action' => 'option_parents_available', 'nomenclature_id' => $nomenclature_id]), 'noms');
if (!is_array($parents_tree) || isset($parents_tree['sccss'])) {
    $parents_tree = [];
}
$parent_options = [];
fncFlattenGroupOptions($parents_tree, 0, [], $parent_options);
?>
<input type="hidden" id="inpOptionNomenclatureId" value="<?php echo $nomenclature_id; ?>">
<form id="formOptionNew">
    <div class="row">
        <div class="col-12 mb-3">
            <label for="inpOptionName" class="my-input-label">Название</label>
            <input type="text"
                class="form-in form-inp"
                id="inpOptionName"
                data-name="name"
                data-type="text"
                data-required="1"
                autocomplete="off">
        </div>

        <div class="col-12 mb-3">
            <label for="slctOptionParent" class="my-input-label">Родитель</label>
            <select class="form-in form-inp" id="slctOptionParent" data-name="parent_id">
                <option value="0">Без родителя</option>
                <?php foreach ($parent_options as $opt): ?>
                    <option value="<?php echo (int)$opt['id']; ?>"><?php echo htmlspecialchars($opt['label']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12 mb-3">
            <label for="slctOptionProduct" class="my-input-label">Привязанный товар</label>
            <select class="form-in form-inp" id="slctOptionProduct" data-name="product_id">
                <option value="0">Без товара (раздел с опциями)</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?php echo (int)$product['id']; ?>"><?php echo htmlspecialchars($product['name']); ?></option>
                <?php endforeach; ?>
            </select>
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
