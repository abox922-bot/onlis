<?php
date_default_timezone_set('Asia/Yekaterinburg');

require_once(dirname(__FILE__) . '/config.php');
require_once(dirname(__FILE__) . '/paths.php');

//==============================================================================
// Отправка внутреннего запроса к API-модулю
// $data   — массив параметров запроса
// $module — ключ из $urls (main, booking, users ...)
// Возвращает массив ответа или false при ошибке
//==============================================================================
function send_request(array $data, string $module)
{
    global $urls;

    if (!isset($urls[$module])) {
        error_log("[" . date("Y-m-d H:i:s") . "] send_request: неизвестный модуль '{$module}'");
        return false;
    }

    $data['user_tz_offset'] = $_COOKIE['user_tz_offset'] ?? '0';

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL            => $urls[$module],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($data),
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_CONNECTTIMEOUT => 3,
    ]);

    $raw = curl_exec($curl);

    if ($raw === false) {
        error_log("[" . date("Y-m-d H:i:s") . "] cURL Error [{$module}]: " . curl_error($curl));
        curl_close($curl);
        return false;
    }

    curl_close($curl);

    $response = json_decode($raw, true);

    if ($response === null) {
        error_log("[" . date("Y-m-d H:i:s") . "] JSON decode failed [{$module}]: " . $raw);
        return false;
    }

    return $response;
}
