<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();
?>
<div class="inline-tab-sub-nav mb-3">
    <button type="button" class="inline-tab-sub active" data-target="lease">Аренда</button>
    <button type="button" class="inline-tab-sub" data-target="utilities">Счётчики</button>
</div>
<div id="divObjectRentContent"></div>
<script src="./_books_objs/js/object_info_rent.js?2026070800"></script>
