<?php
require_once('../app/includes/session_guard.php');
$result = fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id = (int)($_POST['id'] ?? 0);

$packages = send_request(array_merge($ses_info, ['action' => 'package_list', 'nomenclature_id' => $id]), 'noms');
if (!is_array($packages) || isset($packages['sccss'])) {
    $packages = [];
}
?>
<input type="hidden" id="inpPackageNomenclatureId" value="<?php echo $id; ?>">
<div class="row">
    <?php if (fncCan($result['rules'], 'nomenclature.manage')): ?>
        <div class="col-12 mb-2">
            <button type="button" class="btn-action-main" id="btnAddPackage">
                <i class="bi bi-plus-lg"></i>
                <span class="btn-label">Добавить</span>
            </button>
        </div>
    <?php endif; ?>
    <div class="col-12">
        <?php if (empty($packages)): ?>
            <div class="empty-hint">
                <i class="bi bi-box empty-hint__icon"></i>
                <div class="empty-hint__text">Упаковки не добавлены</div>
            </div>
        <?php else: ?>
            <table class="table table-sm table-hover mt-2">
                <tbody>
                    <?php foreach ($packages as $package): ?>
                        <tr class="listTr packageTr" data-id="<?php echo (int)$package['id']; ?>">
                            <td class="py-2" style="line-height: 1.2em;">
                                <span class="packageName" data-id="<?php echo (int)$package['id']; ?>">
                                    <?php echo htmlspecialchars($package['name']); ?> <?php echo htmlspecialchars($package['quantity']); ?> <?php echo htmlspecialchars($package['unit_short_name'] ?? ''); ?>
                                </span>
                                <?php if ($package['is_default']): ?>
                                    <div class="mt-1">
                                        <span class="badge-default">По умолчанию</span>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
<script src="./_books_noms/js/nomenclature_info_packages.js?2026081303"></script>
