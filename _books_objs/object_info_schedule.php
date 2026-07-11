<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();
?>
<div class="inline-tab-sub-nav mb-3">
    <button type="button" class="inline-tab-sub active" data-target="main">Основной</button>
    <button type="button" class="inline-tab-sub" data-target="temp">Изменения</button>
</div>
<div id="divObjectGraphContent"></div>
<script src="./_books_objs/js/object_info_schedule.js?2026071000"></script>
