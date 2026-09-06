<?php
require_once('./includes/fncs.php');
require_once('./includes/request.php');

header('Content-Type: application/json');

$cookie = $_POST['_onlis_id'] ?? '';
$token  = $_POST['x_token']   ?? '';

if (!$cookie || !$token) { echo json_encode(['sccss' => false]); exit; }

$ses_check = fncApiAuth($cookie, $token);

if (!$ses_check || empty($ses_check['sccss'])) {
    echo json_encode(['sccss' => false]);
    exit;
}

$user_id = (int)($ses_check['user'] ?? 0);
$perms   = $ses_check['rules'] ?? [];
$action  = $_POST['action'] ?? '';
$params  = isset($_POST['params']) ? json_decode($_POST['params'], true) : [];
if (!is_array($params)) $params = [];

$result = [];

switch ($action) {

    //==========================================================================
    // Объединяющий action для toolbar раздела Документы: типы документов
    // (для фильтра и решения о кнопке "Добавить") + склады (для фильтра).
    case 'documents_toolbar_data':
        $stmt = fncQuery(
            "SELECT dt.code, dt.name
             FROM document_types dt
             WHERE dt.is_active = 1
             ORDER BY dt.name"
        );
        $document_types = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $stmt = fncQuery(
            "SELECT o.id, o.name
             FROM objects o
             WHERE o.is_stock = 1 AND o.is_active = 1
             ORDER BY o.name"
        );
        $warehouses = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $result = [
            'document_types' => $document_types,
            'warehouses'     => $warehouses,
        ];
        break;

    //==========================================================================
    // Объединяющий action для формы создания документа: типы документов
    // (отфильтрованы по правам конкретного пользователя), организации и
    // склады (все — для полного доступа inventory, иначе только те, где
    // пользователь сейчас числится сотрудником, через organization_staff),
    // контрагенты.
    case 'document_new_form_data':
        $stmt = fncQuery(
            "SELECT dt.code, dt.name, dt.direction, dt.warehouse_count, dt.warehouse_field, dt.numbering_mode
             FROM document_types dt
             WHERE dt.is_active = 1 AND dt.is_manual = 1
             ORDER BY dt.name"
        );
        $all_document_types = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $document_types = [];
        foreach ($all_document_types as $dt) {
            if (fncCan($perms, 'inventory.documents.' . $dt['code'])) {
                $document_types[] = $dt;
            }
        }

        $has_full_access = fncCan($perms, 'inventory');

        if ($has_full_access) {
            $stmt = fncQuery(
                "SELECT o.id, o.name
                 FROM organizations o
                 WHERE o.is_contractor = 0 AND o.is_active = 1
                   AND EXISTS (
                       SELECT 1 FROM objects ob
                       WHERE ob.organization_id = o.id AND ob.is_stock = 1 AND ob.is_active = 1
                   )
                 ORDER BY o.name"
            );
        } else {
            $stmt = fncQuery(
                "SELECT DISTINCT o.id, o.name
                 FROM organizations o
                 INNER JOIN organization_staff os ON os.organization_id = o.id
                 WHERE o.is_contractor = 0 AND o.is_active = 1
                   AND os.user_id = :user_id AND os.date_end IS NULL
                   AND EXISTS (
                       SELECT 1 FROM objects ob
                       WHERE ob.organization_id = o.id AND ob.is_stock = 1 AND ob.is_active = 1
                   )
                 ORDER BY o.name",
                ['user_id' => $user_id]
            );
        }
        $organizations = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        if ($has_full_access) {
            $stmt = fncQuery(
                "SELECT ob.id, ob.name, ob.organization_id
                 FROM objects ob
                 WHERE ob.is_stock = 1 AND ob.is_active = 1
                 ORDER BY ob.name"
            );
        } else {
            $stmt = fncQuery(
                "SELECT DISTINCT ob.id, ob.name, ob.organization_id
                 FROM objects ob
                 INNER JOIN organization_staff os ON os.organization_id = ob.organization_id
                 WHERE ob.is_stock = 1 AND ob.is_active = 1
                   AND os.user_id = :user_id AND os.date_end IS NULL
                 ORDER BY ob.name",
                ['user_id' => $user_id]
            );
        }
        $warehouses = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $stmt = fncQuery(
            "SELECT o.id, o.name
             FROM organizations o
             WHERE o.is_contractor = 1 AND o.is_active = 1
             ORDER BY o.name"
        );
        $counterparties = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $result = [
            'document_types' => $document_types,
            'organizations'  => $organizations,
            'warehouses'     => $warehouses,
            'counterparties' => $counterparties,
        ];
        break;

    //==========================================================================
    default:
        echo json_encode(['sccss' => false, 'msg' => 'Неизвестное действие']);
        exit;
}

echo json_encode($result);
