<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$id       = (int)($_POST['id']       ?? 0);
$org_type = $_POST['org_type'] ?? 'my';
?>

<div class="inline-tabs mb-3">
    <button type="button" class="inline-tab active" data-target="main">Основная</button>
    <button type="button" class="inline-tab" data-target="address">Адрес</button>
    <button type="button" class="inline-tab" data-target="accs">Счета</button>
    <button type="button" class="inline-tab" data-target="staff">Сотрудники</button>
    <button type="button" class="inline-tab" data-target="positions">Должности</button>
    <button type="button" class="inline-tab" data-target="departments">Отделы</button>
    <button type="button" class="inline-tab" data-target="objects">Объекты</button>
</div>

<div id="divOrgInfoContent"></div>
