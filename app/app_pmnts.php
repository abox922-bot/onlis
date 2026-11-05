<?php
// Модуль «Взаиморасчёты»: взаиморасчёты с контрагентами и платежи.
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

//==============================================================================
// Локальные функции модуля (общие для нескольких action'ов)
//==============================================================================

switch ($action) {

    default:
        echo json_encode(['sccss' => false, 'msg' => 'Неизвестное действие']);
        exit;
}

echo json_encode($result);
