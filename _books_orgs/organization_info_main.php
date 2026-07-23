<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id       = (int)($_POST['id']       ?? 0);

$result = send_request(array_merge($ses_info, [
    'action' => 'organization_info',
    'id'     => $id,
]), 'orgs');

if (!is_array($result) || isset($result['sccss'])) {
    $result = [];
}
?>

<form id="formInfo">
    <div class="row">

        <div class="col-12">
            <div class="form-context">
                <?php echo htmlspecialchars($result['country_name'] ?? ''); ?>
                <span style="opacity:0.35; margin: 0 6px">›</span>
                <?php echo htmlspecialchars($result['type_name'] ?? ''); ?>
            </div>
        </div>

        <div class="col-12 my-2">
            <div class="form-group-label">Наименование</div>
        </div>

        <div class="col-12 mb-3">
            <div class="input-group w-100">
                <span class="input-group-text" style="font-weight: 600; background: var(--bg-hover); border-color: var(--border-color); color: var(--text-main);">
                    <?php echo htmlspecialchars($result['abbreviation'] ?? ''); ?>
                </span>
                <input type="text"
                    class="form-in form-inp"
                    id="inpName"
                    data-name="org-name"
                    data-type="text"
                    data-required="1"
                    value="<?php echo htmlspecialchars($result['name'] ?? ''); ?>"
                    autocomplete="off">
            </div>
        </div>

        <div class="col-12 mt-2">
            <div class="form-group-label">Контакты</div>
        </div>

        <div class="col-12 col-md-6 mb-3">
            <label for="inpPhone" class="my-input-label">Телефон</label>
            <input type="text"
                class="form-in form-inp"
                id="inpPhone"
                data-name="org-phone"
                data-type="phone"
                data-phone-code="<?php echo htmlspecialchars($result['phone_code'] ?? ''); ?>"
                data-phone-mask="<?php echo htmlspecialchars($result['phone_mask'] ?? ''); ?>"
                value="<?php echo htmlspecialchars($result['phone'] ?? ''); ?>"
                autocomplete="off">
        </div>

        <div class="col-12 col-md-6 mb-3">
            <label for="inpEmail" class="my-input-label">E-mail</label>
            <input type="text"
                class="form-in form-inp"
                id="inpEmail"
                data-name="org-email"
                data-type="email"
                value="<?php echo htmlspecialchars($result['email'] ?? ''); ?>"
                autocomplete="off">
        </div>

        <div class="col-12 mb-3">
            <label for="inpWebsite" class="my-input-label">Сайт</label>
            <input type="text"
                class="form-in form-inp"
                id="inpWebsite"
                data-name="org-website"
                data-type="text"
                value="<?php echo htmlspecialchars($result['website'] ?? ''); ?>"
                autocomplete="off"
                placeholder="https://example.com">
        </div>

        <?php if (!empty($result['reqs'])): ?>
        <div class="col-12 mt-2">
            <div class="form-group-label">Реквизиты</div>
        </div>

        <?php foreach ($result['reqs'] as $req):
            switch ($req['value_type']) {
                case 'digits':
                    $input_type = 'text';
                    $data_type  = 'digits_only';
                    break;
                case 'date':
                    $input_type = 'date';
                    $data_type  = 'text';
                    break;
                default:
                    $input_type = 'text';
                    $data_type  = 'text';
            }
            $field_id = 'reqInp' . $req['id'];
        ?>
        <div class="col-12 col-md-6 mb-3">
            <label for="<?php echo $field_id; ?>" class="my-input-label">
                <?php echo htmlspecialchars($req['name']); ?>
                <?php if (empty($req['is_required'])): ?>
                    <span class="text-muted" style="font-weight: 400;">(необязательно)</span>
                <?php endif; ?>
            </label>
            <input
                type="<?php echo $input_type; ?>"
                class="form-in req-inp"
                id="<?php echo $field_id; ?>"
                data-req-id="<?php echo $req['id']; ?>"
                data-type="<?php echo $data_type; ?>"
                data-uniq="<?php echo (int)$req['is_unique']; ?>"
                <?php if (!empty($req['is_required'])): ?>data-required="1"<?php endif; ?>
                <?php if (!empty($req['exact_length'])): ?>data-length="<?php echo (int)$req['exact_length']; ?>"<?php endif; ?>
                value="<?php echo htmlspecialchars($req['value'] ?? ''); ?>">
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

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
<script src="./_books_orgs/js/organization_info_main.js?2026072100"></script>
