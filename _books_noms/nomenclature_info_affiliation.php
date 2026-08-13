<?php
require_once('../app/includes/session_guard.php');
$result = fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id = (int)($_POST['id'] ?? 0);

$types = send_request(array_merge($ses_info, ['action' => 'affiliation_info', 'nomenclature_id' => $id]), 'noms');
if (!is_array($types) || isset($types['sccss'])) {
    $types = [];
}

$can_manage = fncCan($result['rules'], 'nomenclature.manage');
?>
<input type="hidden" id="inpAffiliationNomenclatureId" value="<?php echo $id; ?>">

<?php if (empty($types)): ?>
    <div class="empty-hint">
        <i class="bi bi-geo-alt empty-hint__icon"></i>
        <div class="empty-hint__text">Нет операционных типов объектов</div>
    </div>
<?php else: ?>
    <div class="row">
        <?php foreach ($types as $type): ?>
            <div class="col-12 mb-3">
                <label class="my-input-label" for="slctWorkstation<?php echo (int)$type['id']; ?>">
                    <?php echo htmlspecialchars($type['name']); ?>
                    <span class="spinner-border spinner-border-sm d-none" id="spnWorkstationLoading<?php echo (int)$type['id']; ?>" style="width: 0.75rem; height: 0.75rem; vertical-align: middle; margin-left: 4px;"></span>
                </label>
                <div class="text-danger d-none" id="spnWorkstationError<?php echo (int)$type['id']; ?>" style="font-size: 0.8rem;"></div>
                <?php if (empty($type['workstations'])): ?>
                    <div class="form-context">Нет доступных станций</div>
                <?php else: ?>
                    <select class="form-in slctWorkstation" id="slctWorkstation<?php echo (int)$type['id']; ?>" data-type-id="<?php echo (int)$type['id']; ?>" <?php echo $can_manage ? '' : 'disabled'; ?>>
                        <option value="0">Не выбрано</option>
                        <?php foreach ($type['workstations'] as $ws): ?>
                            <option value="<?php echo (int)$ws['id']; ?>" <?php echo ((int)$ws['id'] === (int)$type['selected_workstation']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($ws['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<script src="./_books_noms/js/nomenclature_info_affiliation.js?2026081303"></script>
