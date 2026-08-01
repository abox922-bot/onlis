<?php
require_once('../app/includes/session_guard.php');
$result = fncRequireSession();
$can_edit = fncCan($result['rules'], 'objects.manage');
?>
<div class="row">
    <div class="col-12 mb-3">
        <div class="text-muted" style="font-size: 0.85rem;">
            Раздел в разработке — здесь появятся настройки сотрудника в контексте объекта.
        </div>
    </div>

    <div class="col-12 col-md-6 mb-3">
        <label class="my-input-label">Доступные рабочие базы</label>
        <input type="text" class="form-in" value="" disabled placeholder="Скоро">
    </div>
    <div class="col-12 col-md-6 mb-3">
        <label class="my-input-label">Алгоритм расчёта ЗП</label>
        <input type="text" class="form-in" value="" disabled placeholder="Скоро">
    </div>
    <div class="col-12 mb-3">
        <label class="my-input-label">Роль на объекте</label>
        <input type="text" class="form-in" value="" disabled placeholder="Скоро">
    </div>

    <?php if ($can_edit): ?>
        <div class="col-12 mt-2">
            <button type="button" class="btn-danger-action" id="btnDismissObjectStaff">
                Снять с объекта
            </button>
        </div>
    <?php endif; ?>
</div>
