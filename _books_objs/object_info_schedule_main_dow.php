<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id  = (int)($_POST['id'] ?? 0);
$dow = (int)($_POST['dow'] ?? 0);

$day = send_request(array_merge($ses_info, ['action' => 'object_schedule_day_info', 'id' => $id, 'dow' => $dow]), 'objs');
if (!is_array($day) || isset($day['sccss'])) {
    $day = [];
}

$schedule_id = (int)($day['id'] ?? 0);
?>
<form id="formDow">
    <div class="row">
        <div class="col-6 mb-3">
            <label class="my-input-label" for="inpStartTime">Начало</label>
            <input type="time" class="form-in form-inp" data-name="start_time" data-type="text" id="inpStartTime" value="<?php echo htmlspecialchars($day['start_time'] ?? ''); ?>">
        </div>
        <div class="col-6 mb-3">
            <label class="my-input-label" for="inpEndTime">Окончание</label>
            <input type="time" class="form-in form-inp" data-name="end_time" data-type="text" id="inpEndTime" value="<?php echo htmlspecialchars($day['end_time'] ?? ''); ?>">
        </div>

        <div class="col-12 mb-3">
            <div class="d-flex align-items-center justify-content-between">
                <div class="form-group-label mb-0">Перерывы</div>
                <?php if ($schedule_id): ?>
                  <button type="button" class="btn-action-outline btn-sm" id="btnNewBreak" data-schedule-id="<?php echo $schedule_id; ?>">
                      <i class="bi bi-plus-lg"></i> Добавить
                  </button>
                <?php endif; ?>
            </div>

            <?php if (!$schedule_id): ?>
                <div class="text-muted mt-2" style="font-size: 0.85rem;">
                    Сначала сохраните время работы — тогда можно будет добавить перерывы.
                </div>
            <?php elseif (empty($day['breaks'])): ?>
                <div class="text-muted mt-2" style="font-size: 0.85rem;">Перерывов нет</div>
            <?php else: ?>
                <ul class="list-group list-group-flush mt-2">
                    <?php foreach ($day['breaks'] as $break): ?>
                        <li class="list-group-item breakItem" data-id="<?php echo (int)$break['id']; ?>" style="cursor: pointer;">
                            <?php echo date('H:i', strtotime($break['start_time'])) . ' – ' . date('H:i', strtotime($break['end_time'])); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="col-12 mb-3">
            <div class="form-check form-switch">
                <input class="form-check-input form-inp" type="checkbox" role="switch" id="chckAllDay" data-name="is_all_day" data-type="check" <?php echo !empty($day['is_all_day']) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="chckAllDay">Круглосуточно</label>
            </div>
        </div>
        <div class="col-12 mb-3">
            <div class="form-check form-switch">
                <input class="form-check-input form-inp" type="checkbox" role="switch" id="chckDayOff" data-name="is_day_off" data-type="check" <?php echo !empty($day['is_day_off']) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="chckDayOff">Выходной</label>
            </div>
        </div>

        <div class="col-12 mt-2 d-none" id="divFormError">
            <div class="form-error-msg" id="spnFormError"></div>
        </div>
        <div class="col-12 mt-3">
            <button type="submit" class="btn-action-main" id="btnSave">
                <span id="btnSaveText">Сохранить</span>
                <div class="spinner-border spinner-border-sm d-none" id="divSaveLoading"></div>
            </button>
        </div>
    </div>
</form>
