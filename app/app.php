<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['sccss' => false]));
}

require_once('./includes/fncs.php');

header('Content-Type: application/json; charset=utf-8');

$ses_id  = mb_substr($_POST['_onlis_id'] ?? '', 0, 40);
$x_token = mb_substr($_POST['x_token']   ?? '', 0, 40);
$action  = mb_substr($_POST['action']    ?? '', 0, 20);
$time_zone = $_POST['user_tz_offset'] ?? '0';

$out_array = [];

//==============================================================================
// Авторизация по логину и паролю
//==============================================================================
if ($action === 'in') {
    $params = json_decode($_POST['params'] ?? '[]', true);

    $qu = "SELECT u.id AS id, CONCAT_WS(' ', u.name, u.last_name) AS full_name, ua.password AS psw
           FROM users_auth ua
           LEFT JOIN users u ON u.id = ua.user
           WHERE ua.login = :login";

    $stmt = fncQuery($qu, ['login' => fncValFind('usr_login', $params)]);
    $user = $stmt ? $stmt->fetch() : null;

    if ($user && password_verify(fncValFind('usr_pass', $params), $user['psw'])) {

        $usr_id   = $user['id'];
        $usr_name = $user['full_name'];

        // Закрываем все активные сессии пользователя
        $qu = "UPDATE sessions SET session = NULL, cntrl = NULL, stop_time = NOW()
               WHERE user = :user AND session IS NOT NULL";
        fncQuery($qu, ['user' => $usr_id]);

        // Создаём новую сессию
        $cntrl = bin2hex(random_bytes(16));
        $qu = "INSERT INTO sessions (user, start_time, session, cntrl, time_zone)
               VALUES (:user, NOW(), :session, :cntrl, :time_zone)";
        fncQuery($qu, [
            'user'      => $usr_id,
            'session'   => $ses_id,
            'cntrl'     => $cntrl,
            'time_zone' => $time_zone,
        ]);

        // Триггер SSE — уведомляем об изменении сессии
        $flag_path = $_SERVER['DOCUMENT_ROOT'] . '/sse_cache/u_' . md5($usr_id) . '.flag';
        touch($flag_path);

        $out_array = [
            'sccss'     => true,
            'user'      => $usr_id,
            'user_name' => $usr_name,
            'path'      => '_main.php',
            'cntrl'     => $cntrl,
        ];

    } else {
        $out_array = ['sccss' => false];
    }

//==============================================================================
// Проверка сессии по session + cntrl (вызывается при каждом открытии SPA)
//==============================================================================
} elseif ($action === 'in_cntrl') {

  $qu = "SELECT s.id AS ses_id, s.user AS usr_id,
                CONCAT_WS(' ', u.name, u.last_name) AS usr_name,
                CURDATE() AS today,
                r.id AS rules
         FROM sessions s
         LEFT JOIN users u ON u.id = s.user
         LEFT JOIN rules r ON r.id = u.rules
         WHERE s.session = :session AND s.cntrl = :cntrl AND s.cntrl IS NOT NULL";

  $stmt    = fncQuery($qu, ['session' => $ses_id, 'cntrl' => $x_token]);
  $session = $stmt ? $stmt->fetch() : null;

  if ($session) {
      $flag_path = $_SERVER['DOCUMENT_ROOT'] . '/sse_cache/u_' . md5($session['usr_id']) . '.flag';

      $out_array = [
          'sccss'     => true,
          'rules'     => $session['rules'],
          'user'      => $session['usr_id'],
          'user_name' => $session['usr_name'],
          'path'      => '_main.php',
          'today'     => $session['today'],
      ];

  } else {

      $qu = "UPDATE sessions SET session = NULL, cntrl = NULL, stop_time = NOW()
             WHERE session = :session";
      $stmt_user = fncQuery("SELECT user AS usr_id FROM sessions WHERE session = :session", ['session' => $ses_id]);
      $ses_user  = $stmt_user ? $stmt_user->fetch() : null;
      fncQuery($qu, ['session' => $ses_id]);

      if ($ses_user) {
          $flag_path = $_SERVER['DOCUMENT_ROOT'] . '/sse_cache/u_' . md5($ses_user['usr_id']) . '.flag';
          touch($flag_path);
      }

      $out_array = ['sccss' => false];
  }
//==============================================================================
// Получение cntrl по session_id (вызывается при загрузке index.php)
//==============================================================================
} elseif ($action === 'info') {

    $qu = "SELECT cntrl FROM sessions WHERE session = :session AND cntrl IS NOT NULL";
    $stmt    = fncQuery($qu, ['session' => $ses_id]);
    $session = $stmt ? $stmt->fetch() : null;

    if ($session) {
        $out_array = ['sccss' => true, 'cntrl' => $session['cntrl']];
    } else {
        $out_array = ['sccss' => false, 'cntrl' => null];
    }

//==============================================================================
// Получение user_id по session + cntrl
//==============================================================================
} elseif ($action === 'usr_info') {

    $qu = "SELECT user AS usr_id FROM sessions
           WHERE session = :session AND cntrl IS NOT NULL";
    $stmt = fncQuery($qu, ['session' => $ses_id]);
    $user = $stmt ? $stmt->fetch() : null;

    if ($user) {
        $out_array = ['sccss' => true, 'user' => $user['usr_id']];
    } else {
        $out_array = ['sccss' => false, 'user' => null];
    }

//==============================================================================
// Закрытие сессии (выход пользователя)
//==============================================================================
} elseif ($action === 'close_ses') {

    $qu = "SELECT user AS usr_id FROM sessions
           WHERE session = :session AND cntrl = :cntrl AND cntrl IS NOT NULL";
    $stmt = fncQuery($qu, ['session' => $ses_id, 'cntrl' => $x_token]);
    $user = $stmt ? $stmt->fetch() : null;

    $qu = "UPDATE sessions SET session = NULL, cntrl = NULL, stop_time = NOW()
           WHERE session = :session";
    fncQuery($qu, ['session' => $ses_id]);

    if ($user) {
        $flag_path = $_SERVER['DOCUMENT_ROOT'] . '/sse_cache/u_' . md5($user['usr_id']) . '.flag';
        touch($flag_path);
        $out_array = ['sccss' => true];
    } else {
        $out_array = ['sccss' => false];
    }

} else {
    $out_array = ['sccss' => false, 'error' => 'Unknown action'];
}

echo json_encode($out_array);
