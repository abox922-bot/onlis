<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();
$id = (int)($_POST['id'] ?? 0);
?>
<input type="hidden" id="inpTypeId" value="<?php echo $id; ?>">
<div class="inline-tabs mb-3">
    <button type="button" class="inline-tab active" data-target="main">Основная</button>
    <button type="button" class="inline-tab" data-target="workstations">Рабочие станции</button>
</div>
<div id="divTypeInfoContent"></div>
