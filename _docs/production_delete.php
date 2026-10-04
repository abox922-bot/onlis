<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();
?>
<div class="mb-3 text-muted">Удаление документа безвозвратно. Строки и резервы партий будут очищены.</div>
<button type="button" class="btn-danger-action" id="btnDeleteDocument">Удалить документ</button>
