<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id = (int)($_POST['id'] ?? 0);

$info = send_request(array_merge($ses_info, ['action' => 'nomenclature_info', 'id' => $id]), 'noms');
$is_produced = is_array($info) && !empty($info) && !empty($info['is_produced']);
?>
<input type="hidden" id="inpNomenclatureId" value="<?php echo $id; ?>">
<div class="inline-tabs mb-3">
    <button type="button" class="inline-tab" data-target="prices">Остатки и цены</button>
    <button type="button" class="inline-tab" data-target="general">Общая информация</button>
    <?php if ($is_produced): ?>
        <button type="button" class="inline-tab" data-target="composition">Состав</button>
    <?php endif; ?>
    <button type="button" class="inline-tab" data-target="affiliation">Принадлежность</button>
    <button type="button" class="inline-tab" data-target="usage">Применимость</button>
    <button type="button" class="inline-tab" data-target="stock">Складские операции</button>
    <?php if (!$is_produced): ?>
        <button type="button" class="inline-tab" data-target="packages">Тип упаковки</button>
    <?php endif; ?>
    <button type="button" class="inline-tab" data-target="nutrition">КБЖУ</button>
</div>
<div id="divNomenclatureInfoContent"></div>
