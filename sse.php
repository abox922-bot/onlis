<?php
// Снимаем лимит времени выполнения
set_time_limit(0);

// Отключаем буферизацию
if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', 1);
}
@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);

// Заголовки SSE
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');

// Освобождаем сессию
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

require_once("./app/includes/request.php");

$ses_id  = $_COOKIE["_onlis_id"] ?? '';
$token   = $_GET['token']        ?? '';

$auth_data = [
    "action"    => "usr_info",
    "_onlis_id" => $ses_id,
    "x_token"   => $token,
];

$result = send_request($auth_data, "main");

//==============================================================================
// Пользователь не авторизован — сразу закрываем
//==============================================================================
if (!$result || empty($result["user"])) {
    $data = json_encode(["action" => "logout", "reason" => "wrong_pair"]);
    echo "event: auth_status\n";
    echo "data: {$data}\n\n";
    if (ob_get_level()) ob_flush();
    flush();
    exit;
}

//==============================================================================
// Основной цикл SSE
//==============================================================================
$user_flag_path     = $_SERVER['DOCUMENT_ROOT'] . "/sse_cache/u_" . md5($result["user"]) . ".flag";
$last_flag_time     = file_exists($user_flag_path) ? filemtime($user_flag_path) : time();
$started_at         = time();
$max_lifetime       = 3600; // 1 час — потом фронт переподключится

while (true) {

    // Принудительное переподключение через час
    if (time() - $started_at > $max_lifetime) {
        echo "event: reconnect\n";
        echo "data: {}\n\n";
        if (ob_get_level()) ob_flush();
        flush();
        break;
    }

    // Проверка флага сессии
    clearstatcache();
    if (file_exists($user_flag_path)) {
        $current_flag_time = filemtime($user_flag_path);
        if ($current_flag_time > $last_flag_time) {
            $result = send_request($auth_data, "main");
            if (!$result || empty($result["user"])) {
                $data = json_encode(["action" => "logout", "reason" => "session_expired"]);
                echo "event: auth_status\n";
                echo "data: {$data}\n\n";
                if (ob_get_level()) ob_flush();
                flush();
                break;
            }
            $last_flag_time = $current_flag_time;
        }
    }

    // Heartbeat — время сервера
    $data = json_encode(['time' => date('H:i'), 'status' => 'online']);
    echo "event: server_time\n";
    echo "data: {$data}\n\n";
    if (ob_get_level()) ob_flush();
    flush();

    if (connection_aborted()) break;
    sleep(1); // Раз в 30 секунд достаточно для времени
}
