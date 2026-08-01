<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();
$id = (int)($_POST['id'] ?? 0);
?>
<input type="hidden" id="inpObjectId" value="<?php echo $id; ?>">
<div id="divObjectInfoContent"></div>
