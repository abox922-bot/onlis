<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$id = (int)($_POST['id'] ?? 0);
?>
<input type="hidden" id="inpProductId" value="<?php echo $id; ?>">
<div class="inline-tabs mb-3">
    <button type="button" class="inline-tab" data-target="general">Общая информация</button>
    <button type="button" class="inline-tab" data-target="composition">Состав товара</button>
    <button type="button" class="inline-tab" data-target="affiliation">Принадлежность</button>
    <button type="button" class="inline-tab" data-target="prices">Цены</button>
    <button type="button" class="inline-tab" data-target="additions">Дополнения</button>
    <button type="button" class="inline-tab" data-target="options">Опции</button>
    <button type="button" class="inline-tab" data-target="nutrition">КБЖУ</button>
</div>
<div id="divProductInfoContent"></div>
