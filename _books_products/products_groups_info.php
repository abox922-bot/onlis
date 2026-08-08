<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id = (int)($_POST['id'] ?? 0);

$info = send_request(array_merge($ses_info, ['action' => 'group_info', 'id' => $id]), 'noms');
if (!is_array($info) || empty($info)) {
    echo '<div class="empty-hint"><div class="empty-hint__text">Группа не найдена</div></div>';
    exit;
}

require_once('../modules_fncs.php');

$tree = send_request(array_merge($ses_info, ['action' => 'groups_list', 'type' => 'product', 'status' => 'active']), 'noms');
if (!is_array($tree) || isset($tree['sccss'])) {
    $tree = [];
}

$forbidden_ids = fncCollectForbiddenIds($tree, $id);
$parent_options = [];
fncFlattenGroupOptions($tree, 0, $forbidden_ids, $parent_options);
?>
<input type="hidden" id="inpGroupId" value="<?php echo (int)$info['id']; ?>">
<form id="formInfo">
    <div class="row">
        <div class="col-12 mb-3">
            <label for="inpName" class="my-input-label">Название</label>
            <input type="text"
                class="form-in form-inp"
                id="inpName"
                data-name="name"
                data-type="text"
                data-required="1"
                value="<?php echo htmlspecialchars($info['name']); ?>"
                autocomplete="off">
        </div>
        <div class="col-12 mb-3">
            <label for="slctParent" class="my-input-label">Родительская группа</label>
            <select class="form-in form-inp" id="slctParent" data-name="parent_id">
                <option value="0">Без родителя</option>
                <?php foreach ($parent_options as $opt): ?>
                    <option value="<?php echo (int)$opt['id']; ?>" <?php echo ((int)$opt['id'] === (int)$info['parent_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($opt['label']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if (!$info['is_active']): ?>
            <div class="col-12 mb-3">
                <div class="form-context">Группа в архиве</div>
            </div>
        <?php endif; ?>
        <div class="col-12 mt-2 d-none" id="divFormError">
            <div class="form-error-msg" id="spnFormError"></div>
        </div>
        <div class="col-12 mt-3 d-flex gap-2">
            <button type="submit" class="btn-action-main" id="btnSave">
                <span id="btnSaveText">Сохранить</span>
                <div class="spinner-border spinner-border-sm d-none" id="divSaveLoading"></div>
            </button>
            <?php if ($info['is_active']): ?>
                <button type="button" class="btn-danger-action" id="btnArchive">Архивировать</button>
            <?php else: ?>
                <button type="button" class="btn-action-outline" id="btnRestore">Восстановить</button>
            <?php endif; ?>
        </div>
    </div>
</form>
