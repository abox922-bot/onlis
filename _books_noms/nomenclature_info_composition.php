<?php
require_once('../app/includes/session_guard.php');
$result = fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id = (int)($_POST['id'] ?? 0);

$available = send_request(array_merge($ses_info, ['action' => 'composition_available', 'nomenclature_id' => $id]), 'noms');
if (!is_array($available) || isset($available['sccss'])) {
    $available = [];
}

$can_manage = fncCan($result['rules'], 'nomenclature.manage');
?>
<input type="hidden" id="inpCompositionNomenclatureId" value="<?php echo $id; ?>">

<div class="row">
    <?php if ($can_manage): ?>
        <div class="col-12 mb-3">
            <label for="slctIngredient" class="my-input-label">Добавить ингредиент</label>
            <select id="slctIngredient">
                <option value="">Выберите позицию</option>
                <?php foreach ($available as $item): ?>
                    <option value="<?php echo (int)$item['id']; ?>"><?php echo htmlspecialchars($item['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>
    <div class="col-12 mt-2 mb-2">
        <div class="form-group-label">Состав</div>
    </div>
    <div class="col-12" id="divCompositionList"></div>
</div>
<script src="./_books_noms/js/nomenclature_info_composition.js?2026081303"></script>
