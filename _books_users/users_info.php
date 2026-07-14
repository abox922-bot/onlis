<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();
$user_id = (int)($_POST['user_id'] ?? 0);
?>
<input type="hidden" id="hdnUserId" value="<?php echo $user_id; ?>">
<div class="inline-tabs mb-3">
    <button type="button" class="inline-tab active" data-target="person">Личная</button>
    <button type="button" class="inline-tab" data-target="access">Доступ</button>
    <button type="button" class="inline-tab" data-target="organizations">Организации</button>
    <button type="button" class="inline-tab" data-target="history">История</button>
</div>
<div id="divUserInfoContent"></div>
