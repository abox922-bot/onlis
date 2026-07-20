<?php
require_once(dirname(__FILE__) . '/db.php');

//==============================================================================
// Поиск значения в массиве параметров формата [["name" => ..., "value" => ...]]
// Используется для разбора params пришедших с фронта
//==============================================================================
function fncValFind(string $search_name, array $params)
{
    $key = array_search($search_name, array_column($params, 'name'));
    return ($key !== false) ? $params[$key]['value'] : null;
}

//==============================================================================
// Запись в лог-файл вне публичной директории
//==============================================================================
function fncLog(string $text)
{
    $log_dir  = dirname($_SERVER['DOCUMENT_ROOT']) . '/logs';
    $log_file = $log_dir . '/db_errors.log';

    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0750, true);
    }

    file_put_contents(
        $log_file,
        "[" . date("Y-m-d H:i:s") . "] " . $text . "\n" . str_repeat('-', 60) . "\n",
        FILE_APPEND | LOCK_EX
    );
}

//==============================================================================
// Выполнение PDO-запроса
// Возвращает PDOStatement или false при ошибке
//==============================================================================
function fncQuery(string $sql, array $params = [])
{
    global $pdo;

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;

    } catch (PDOException $e) {
        fncLog(
            "DB Error: "   . $e->getMessage() . "\n" .
            "SQL: "        . $sql             . "\n" .
            "Params: "     . json_encode($params, JSON_UNESCAPED_UNICODE)
        );
        return false;
    }
}
//==============================================================================
// Загрузка прав пользователя
// $obj_id — опционально, если работа в контексте конкретной точки
//==============================================================================
function fncLoadPermissions(int $user_id, ?int $obj_id = null): array {
    $sql = "SELECT DISTINCT p.slug
            FROM users_roles ur
            JOIN roles_permissions rp ON rp.role_id = ur.role_id
            JOIN permissions p ON p.id = rp.permission_id
            WHERE ur.user_id = ? AND (ur.obj_id IS NULL OR ur.obj_id = ?)";
    $result = fncQuery($sql, [$user_id, $obj_id]);
    if (!$result) return [];
    return array_column($result->fetchAll(PDO::FETCH_ASSOC), 'slug');
}
//==============================================================================
// Проверка конкретного права с учётом иерархии slug
//==============================================================================
function fncCan(array $perms, string $slug): bool {
    if (in_array($slug, $perms)) return true;
    // Поднимаемся вверх по иерархии: 'geography.edit' → 'geography'
    $parts = explode('.', $slug);
    for ($i = count($parts) - 1; $i > 0; $i--) {
        $parent = implode('.', array_slice($parts, 0, $i));
        if (in_array($parent, $perms)) return true;
    }
    return false;
}
//==============================================================================
// Проверка авторизации в app_x.php
//==============================================================================
function fncApiAuth(string $session_id, string $cntrl_token): array
{
    $stmt = fncQuery(
        "SELECT s.user AS usr_id, u.is_active, u.actual
         FROM sessions s
         LEFT JOIN users u ON u.id = s.user
         WHERE s.session = :session AND s.cntrl = :cntrl AND s.cntrl IS NOT NULL",
        ['session' => $session_id, 'cntrl' => $cntrl_token]
    );
    $session = $stmt ? $stmt->fetch() : null;

    if (!$session) {
        return ['sccss' => false];
    }

    if (!$session['is_active'] || !$session['actual']) {
        $flag_path = $_SERVER['DOCUMENT_ROOT'] . '/sse_cache/u_' . md5($session['usr_id']) . '.flag';
        touch($flag_path);
        return ['sccss' => false];
    }

    return [
        'sccss' => true,
        'user'  => (int)$session['usr_id'],
        'rules' => fncLoadPermissions((int)$session['usr_id']),
    ];
}
//==============================================================================
