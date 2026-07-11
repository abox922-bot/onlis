<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();
?>
<div class="row">
    <div class="col-12 mb-3">
        <label class="my-input-label" for="slctDow">День недели</label>
        <select class="form-in" id="slctDow">
            <option value="1" selected>Понедельник</option>
            <option value="2">Вторник</option>
            <option value="3">Среда</option>
            <option value="4">Четверг</option>
            <option value="5">Пятница</option>
            <option value="6">Суббота</option>
            <option value="7">Воскресенье</option>
        </select>
    </div>
    <div class="col-12" id="divDowContent"></div>
</div>
<script src="./_books_objs/js/object_info_schedule_main.js?2026071004"></script>
