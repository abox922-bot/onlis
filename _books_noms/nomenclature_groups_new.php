<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$tree = send_request(array_merge($ses_info, ['action' => 'groups_list', 'type' => 'nomenclature', 'status' => 'active']), 'noms');
if (!is_array($tree) || isset($tree['sccss'])) {
    $tree = [];
}

function fncFlattenGroupOptions($nodes, $level, &$options) {
    foreach ($nodes as $node) {
        $options[] = [
            'id'    => $node['id'],
            'label' => str_repeat('— ', $level) . $node['name'],
        ];
        if (!empty($node['children'])) {
            fncFlattenGroupOptions($node['children'], $level + 1, $options);
        }
    }
}

$parent_options = [];
fncFlattenGroupOptions($tree, 0, $parent_options);
?>
<form id="formNew">
    <div class="row">
        <div class="col-12 mb-3">
            <label for="inpName" class="my-input-label">Название</label>
            <input type="text"
                class="form-in form-inp"
                id="inpName"
                data-name="name"
                data-type="text"
                data-required="1"
                autocomplete="off">
        </div>
        <div class="col-12 mb-3">
            <label for="slctParent" class="my-input-label">Родительская группа</label>
            <select class="form-in form-inp" id="slctParent" data-name="parent_id" data-type="select">
                <option value="0">Без родителя</option>
                <?php foreach ($parent_options as $opt): ?>
                    <option value="<?php echo (int)$opt['id']; ?>"><?php echo htmlspecialchars($opt['label']); ?></option>
                <?php endforeach; ?>
            </select>
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
