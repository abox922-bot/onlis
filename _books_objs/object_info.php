<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$id = (int)($_POST['id'] ?? 0);
?>
<div class="col-12">
    <input type="hidden" id="inpObjectId" value="<?php echo $id; ?>">
    <div class="inline-tabs mb-3">
        <button type="button" class="inline-tab active" data-target="main">Основная</button>
        <button type="button" class="inline-tab" data-target="address">Адрес</button>
        <button type="button" class="inline-tab" data-target="rent">Аренда</button>
        <button type="button" class="inline-tab" data-target="schedule">График</button>
        <button type="button" class="inline-tab" data-target="staff">Сотрудники</button>
        <button type="button" class="inline-tab" data-target="pay">Типы оплат</button>
        <button type="button" class="inline-tab" data-target="rooms">Помещения</button>
        <button type="button" class="inline-tab" data-target="eqp">Оборудование</button>
    </div>
    <div id="divObjectInfoContent"></div>
</div>
