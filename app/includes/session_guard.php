<?php
require_once(dirname(__FILE__) . '/request.php');
require_once(dirname(__FILE__) . '/fncs.php');

/**
 * @return array
 */
function fncRequireSession()
{
    if (!isset($_SERVER['HTTP_X_CSRF_TOKEN']) || !isset($_COOKIE['_onlis_id'])) {
        die('Сессия истекла. Обновите страницу.');
    }

    $ses_info = [
        '_onlis_id' => $_COOKIE['_onlis_id'],
        'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
    ];

    $result = send_request(array_merge($ses_info, ['action' => 'in_cntrl']), 'main');

    if (!$result || empty($result['sccss'])) {
        die('Сессия истекла. Обновите страницу.');
    }

    return $result;
}
