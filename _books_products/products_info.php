<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id = (int)($_POST['id'] ?? 0);

$role_data = send_request(array_merge($ses_info, ['action' => 'product_role', 'id' => $id]), 'noms');
$role = is_array($role_data) && !empty($role_data['role']) ? $role_data['role'] : 'standalone';
?>
<input type="hidden" id="inpProductId" value="<?php echo $id; ?>">
<div class="inline-tabs mb-3">
    <button type="button" class="inline-tab" data-target="general">Общая информация</button>
    <?php if ($role !== 'parent'): ?>
        <button type="button" class="inline-tab" data-target="composition">Состав товара</button>
    <?php endif; ?>
    <button type="button" class="inline-tab" data-target="affiliation">Принадлежность</button>
    <?php if ($role !== 'parent'): ?>
        <button type="button" class="inline-tab" data-target="prices">Цены</button>
    <?php endif; ?>
    <button type="button" class="inline-tab" data-target="additions">Дополнения</button>
    <?php if ($role !== 'option'): ?>
        <button type="button" class="inline-tab" data-target="options">Опции</button>
    <?php endif; ?>
    <?php if ($role !== 'parent'): ?>
        <button type="button" class="inline-tab" data-target="nutrition">КБЖУ</button>
    <?php endif; ?>
</div>
<div id="divProductInfoContent"></div>
