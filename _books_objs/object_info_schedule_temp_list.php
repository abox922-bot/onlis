<?php
require_once('../app/includes/session_guard.php');
$result = fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id = (int)($_POST['id'] ?? 0);

$periods = send_request(array_merge($ses_info, ['action' => 'object_schedule_temporary_list', 'id' => $id]), 'objs');
if (!is_array($periods) || isset($periods['sccss'])) {
    $periods = [];
}

$can_edit = fncCan($result['rules'], 'objects.manage');
?>
<?php if (empty($periods)): ?>
    <div class="empty-hint">
        <i class="bi bi-calendar-week empty-hint__icon"></i>
        <div class="empty-hint__text">Изменений графика не добавлено</div>
    </div>
<?php else: ?>
    <?php foreach ($periods as $period): ?>
        <?php
            $from = date('d.m.Y', strtotime($period['valid_from']));
            $to   = date('d.m.Y', strtotime($period['valid_to']));
            if (!empty($period['is_day_off'])) {
                $desc = 'Выходной';
            } elseif (!empty($period['is_all_day'])) {
                $desc = 'Круглосуточно';
            } else {
                $desc = date('H:i', strtotime($period['start_time'])) . ' – ' . date('H:i', strtotime($period['end_time']));
            }
        ?>
        <div class="tempPeriodCard mb-3 p-3" data-id="<?php echo (int)$period['id']; ?>" style="background: rgba(0,0,0,0.03); border-radius: 8px;">

            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="fw-bold"><?php echo $from === $to ? htmlspecialchars($from) : htmlspecialchars($from . ' – ' . $to); ?></div>
                    <div class="text-muted" style="font-size: 0.9rem;"><?php echo htmlspecialchars($desc); ?></div>
                </div>
                <?php if ($can_edit): ?>
                    <button type="button" class="btn-danger-action btn-sm periodDeleteBtn">Удалить</button>
                <?php endif; ?>
            </div>

            <hr class="my-3">

            <div class="p-2" style="background: #fff; border-radius: 6px;">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="form-group-label mb-0">Перерывы</div>
                    <?php if ($can_edit): ?>
                        <button type="button" class="btn-action-outline btn-sm periodNewBreakBtn">
                            <i class="bi bi-plus-lg"></i> Добавить
                        </button>
                    <?php endif; ?>
                </div>
                <?php if (empty($period['breaks'])): ?>
                    <div class="text-muted" style="font-size: 0.85rem;">Перерывов нет</div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($period['breaks'] as $break): ?>
                            <li class="list-group-item periodBreakItem" data-id="<?php echo (int)$break['id']; ?>" style="cursor: pointer;">
                                <?php echo date('H:i', strtotime($break['start_time'])) . ' – ' . date('H:i', strtotime($break['end_time'])); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

        </div>
    <?php endforeach; ?>
<?php endif; ?>
