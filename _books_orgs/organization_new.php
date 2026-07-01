<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$org_type = $_POST['org_type'] ?? 'my';

$result = send_request(array_merge($ses_info, [
    'action'   => 'new_organization_info',
    'org_type' => $org_type,
]), 'orgs');

if (!is_array($result) || isset($result['sccss'])) {
    $result = ['countries' => [], 'types' => []];
}

// Скрытые флаги типа организации
$is_contractor = ($org_type === 'contractor' || $org_type === 'bank') ? 1 : 0;
$is_bank       = ($org_type === 'bank') ? 1 : 0;

// Метка типа для form-context
$type_labels = [
    'my'         => null,
    'contractor' => 'Контрагент',
    'bank'       => 'Банк',
];
$type_label = $type_labels[$org_type] ?? null;
?>

<form id="formNew">
    <div class="row">

        <?php if ($type_label): ?>
        <div class="col-12">
            <div class="form-context">
                <?php echo htmlspecialchars($type_label); ?>
            </div>
        </div>
        <?php endif; ?>

        <input type="hidden" class="form-inp" data-name="org-is-contractor" data-type="check" value="<?php echo $is_contractor; ?>">
        <input type="hidden" class="form-inp" data-name="org-is-bank" data-type="check" value="<?php echo $is_bank; ?>">

        <div class="col-12 col-md-6">
            <label class="my-input-label" for="slctCountry">Страна</label>
            <select class="form-inp" id="slctCountry" data-name="org-country-id" data-type="select" data-required="1">
                <option value="">Выберите страну</option>
                <?php foreach ($result['countries'] as $country): ?>
                    <option value="<?php echo $country['id']; ?>">
                        <?php echo htmlspecialchars($country['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12 col-md-6">
            <label class="my-input-label" for="slctType">ОПФ</label>
            <select class="form-in form-inp" id="slctType" data-name="org-type-id" data-type="select" data-required="1" disabled>
                <option value="0">Выберите ОПФ</option>
                <?php foreach ($result['types'] as $type): ?>
                    <option value="<?php echo $type['id']; ?>"
                        data-abbr="<?php echo htmlspecialchars($type['abbreviation']); ?>"
                        data-country="<?php echo $type['country_id']; ?>">
                        <?php echo htmlspecialchars($type['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12 d-none" id="divNewOrgName">
            <div class="row">
                <div class="col-12">
                    <label for="inpName" class="my-input-label">Название <span class="text-muted" style="font-weight:400;">(без ОПФ)</span></label>
                    <div class="input-group">
                        <span class="input-group-text" id="spnOPF"></span>
                        <input type="text"
                            class="form-in form-inp"
                            id="inpName"
                            data-name="org-name"
                            data-type="text"
                            data-required="1"
                            autocomplete="off">
                    </div>
                </div>
                <div class="col-12" id="divOrgReqs"></div>
            </div>
        </div>

        <div class="col-12 d-none" id="divFormError">
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
