<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();
?>
<input type="hidden" id="inpUtilityId">
<div class="inline-tabs mb-3">
    <button type="button" class="inline-tab-info active" data-target="main">Основная</button>
    <button type="button" class="inline-tab-info" data-target="hist">История</button>
</div>
<div id="divUtilityInfoContent"></div>
