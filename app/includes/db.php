<?php
  require_once(dirname(__FILE__) . '/config.php');

  // Timezone: берём из POST или COOKIE, изолированно от внешней области
  $tz_raw  = $_POST['user_tz_offset'] ?? $_COOKIE['user_tz_offset'] ?? '0';
  $tz_hours = max(-12, min(14, (int) $tz_raw));
  $tz_sign  = $tz_hours >= 0 ? '+' : '-';
  $tz_mysql = sprintf("%s%02d:00", $tz_sign, abs($tz_hours));

  define('DB_TIMEZONE', $tz_mysql);

  $dsn = "mysql:host="    . DB_HOST
       . ";port="         . DB_PORT
       . ";dbname="       . DB_NAME
       . ";charset="      . DB_CHARSET;

  $options = [
      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES   => false,
      PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci,
                                           time_zone = '" . DB_TIMEZONE . "'",
  ];

  try {
      $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, $options);
  } catch (PDOException $e) {
      error_log("[" . date("Y-m-d H:i:s") . "] DB connection failed: " . $e->getMessage());
      http_response_code(503);
      die(json_encode(["sccss" => false, "error" => "Сервис временно недоступен"]));
  }
