<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$id = (int)($_POST['id'] ?? 0);
?>
<input type="hidden" id="inpNomenclatureId" value="<?php echo $id; ?>">
<div class="inline-tabs mb-3">
    <button type="button" class="inline-tab" data-target="prices">Остатки и цены</button>
    <button type="button" class="inline-tab" data-target="general">Общая информация</button>
    <button type="button" class="inline-tab" data-target="affiliation">Принадлежность</button>
    <button type="button" class="inline-tab" data-target="usage">Применимость</button>
    <button type="button" class="inline-tab" data-target="stock">Складские операции</button>
    <button type="button" class="inline-tab" data-target="packages">Тип упаковки</button>
    <button type="button" class="inline-tab" data-target="nutrition">КБЖУ</button>
</div>
<div id="divNomenclatureInfoContent"></div>
