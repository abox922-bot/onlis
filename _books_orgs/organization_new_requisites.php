<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$type_id  = (int)($_POST['type_id']  ?? 0);
$org_type = $_POST['org_type'] ?? 'my';

$result = send_request(array_merge($ses_info, [
    'action'   => 'organization_type_requisites_new_form',
    'type_id'  => $type_id,
    'org_type' => $org_type,
]), 'orgs');

if (!is_array($result) || isset($result['sccss'])) {
    $result = [];
}

$today = date('Y-m-d');
?>

<?php foreach ($result as $req): ?>
    <?php
        // Определяем тип HTML-инпута и data-type для валидации
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
        $field_id = 'reqInp' . $req['requisite_type_id'];
    ?>
    <div class="col-12 col-md-6 mt-2">
        <label for="<?php echo $field_id; ?>" class="my-input-label">
            <?php echo htmlspecialchars($req['name']); ?>
            <?php if (!$req['is_required']): ?>
                <span class="text-muted" style="font-weight: 400;">(необязательно)</span>
            <?php endif; ?>
        </label>
        <input
            type="<?php echo $input_type; ?>"
            class="form-in req-inp"
            id="<?php echo $field_id; ?>"
            data-req-id="<?php echo $req['requisite_type_id']; ?>"
            data-type="<?php echo $data_type; ?>"
            data-uniq="<?php echo (int)$req['is_unique']; ?>"
            <?php if ($req['is_required']): ?>data-required="1"<?php endif; ?>
            <?php if ($req['exact_length']): ?>data-length="<?php echo (int)$req['exact_length']; ?>"<?php endif; ?>
            value="<?php echo $req['value_type'] === 'date' ? $today : ''; ?>"
        >
    </div>
<?php endforeach; ?>
