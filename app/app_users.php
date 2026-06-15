<?php
  $action = $_POST["action"];
  $ses_id = $_POST["_onlis_id"];
  $x_token = $_POST["x_token"];
  $time_zone = $_POST["user_tz_offset"];
  require_once('./includes/fncs.php');
  $out_array = [];

  if ($action == "new_group") {

    $params = json_decode($_POST["params"], true);

    $qu = "INSERT INTO users_groups (name) VALUES (:group)";
    $qu_prm = ["group" => fncValFind("group-name", $params)];
    fncQuery($qu, $qu_prm);
    //$new_session_id = $pdo->lastInsertId();

  } elseif ($action == "groups_list") {

    $qu = "SELECT id, name, actual FROM users_groups ORDER BY name";
    $stmt = fncQuery($qu);
    while ($result = $stmt->fetch()) {
      $out_array[] = ["id" => $result["id"], "name" => $result["name"], "actual" => $result["actual"]];
    }
    echo json_encode($out_array);

  } elseif ($action == "group_info") {

    $id = $_POST["id"];
    $qu = "SELECT name, actual FROM users_groups WHERE id = :id";
    $qu_prm = ["id" => $id];
    $result = fncQuery($qu, $qu_prm)->fetch();
    $out_array = ["id" => $id, "name" => $result["name"], "actual" => $result["actual"]];
    echo json_encode($out_array);

  } elseif ($action == "group_upd") {

    $params = json_decode($_POST["params"], true);
    $actual = fncValFind("actual", $params) ?: null;

    $qu = "UPDATE users_groups SET name = :name, actual = :actual WHERE id = :id";
    $qu_prm = ["id" => fncValFind("item-id", $params), "name" => fncValFind("group-name", $params), "actual" => $actual];
    fncQuery($qu, $qu_prm);

  } elseif ($action == "new") {

    $params = json_decode($_POST["params"], true);

    $qu = "INSERT INTO users (users_group, last_name, name, md_name, phone, email, is_coach) VALUES (:group, :last_name, :name, :md_name, :phone, :email, :is_coach)";
    $qu_prm = ["group" => fncValFind("usr-group", $params), "last_name" => fncValFind("usr-last-name", $params), "name" => fncValFind("usr-name", $params), "md_name" => fncValFind("usr-md-name", $params) ?: null, "phone" => fncValFind("usr-phone", $params) ?: null, "email" => fncValFind("usr-email", $params) ?: null, "is_coach" => fncValFind("usr-coach", $params) ?: null];
    fncQuery($qu, $qu_prm);
    //$new_session_id = $pdo->lastInsertId();

  } elseif ($action == "list") {

    $qu = "SELECT id, CONCAT_WS(' ', last_name, name) AS name, actual, is_coach FROM users WHERE id != 1 ORDER BY last_name, name";
    $stmt = fncQuery($qu);
    while ($result = $stmt->fetch()) {
      $out_array[] = ["id" => $result["id"], "name" => $result["name"], "is_coach" => $result["is_coach"], "actual" => $result["actual"]];
    }
    echo json_encode($out_array);

  } elseif ($action == "info") {

    $id = $_POST["id"];
    $qu = " SELECT u.is_coach AS is_coach, u.last_name AS last_name, u.name AS name, u.md_name AS md_name, u.phone AS phone, u.email AS email,
            u.users_group AS users_group, u.actual AS actual, ug.name AS group_name, u.login AS login, u.pswd AS pswd
            FROM users AS u
            LEFT JOIN users_groups AS ug ON ug.id = u.users_group
            WHERE u.id = :id";
    $qu_prm = ["id" => $id];
    $result = fncQuery($qu, $qu_prm)->fetch();
    if (isset($result["login"])) {
      $login = "*****";
    } else {
      $login = $result["login"];
    }
    if (isset($result["pswd"])) {
      $pswd = "****";
    } else {
      $pswd = $result["pswd"];
    }
    $out_array = ["id" => $id, "is_coach" => $result["is_coach"], "group_id" => $result["users_group"], "group_name" => $result["group_name"], "last_name" => $result["last_name"], "name" => $result["name"], "md_name" => $result["md_name"], "phone" => $result["phone"], "email" => $result["email"], "actual" => $result["actual"], "login" => $login, "pin" => $pswd];
    echo json_encode($out_array);

  } elseif ($action == "upd") {

    $params = json_decode($_POST["params"], true);
    $actual = fncValFind("actual", $params) ?: null;
    $login = fncValFind("usr-login", $params);
    $pswd = fncValFind("usr-pin", $params);

    $qu = "UPDATE users SET is_coach = :is_coach, last_name = :last_name, name = :name, md_name = :md_name, phone = :phone, email = :email, actual = :actual";
    $qu_prm = [
        "id" => fncValFind("item-id", $params),
        "last_name" => fncValFind("usr-last-name", $params),
        "name" => fncValFind("usr-name", $params),
        "md_name" => fncValFind("usr-md-name", $params) ?: null,
        "phone" => fncValFind("usr-phone", $params) ?: null,
        "email" => fncValFind("usr-email", $params) ?: null,
        "is_coach" => fncValFind("usr-coach", $params) ?: null,
        "actual" => $actual
    ];

    if (is_numeric($login)) {
        $qu .= ", login = :login";
        $qu_prm["login"] = $login;
    }

    if (is_numeric($pswd)) {
        $qu .= ", pswd = :pswd";
        $qu_prm["pswd"] = password_hash($pswd, PASSWORD_DEFAULT);
    }

    $qu .= " WHERE id = :id";

    fncQuery($qu, $qu_prm);

    if ($actual === null) {
      $qu = "UPDATE sessions SET session = NULL, cntrl = NULL, stop_time = NOW() WHERE user = :user AND session IS NOT NULL";
      $qu_prm = ["user" => fncValFind("item-id", $params)];
      fncQuery($qu, $qu_prm);
      $flag_path = $_SERVER['DOCUMENT_ROOT'] . "/sse_cache/u_" . md5(fncValFind("item-id", $params)) . ".flag";
      touch($flag_path);
    }
  } elseif ($action == "profile_info") {
    $qu = " SELECT s.user AS usr_id
            FROM sessions s
            WHERE s.session = :session AND s.cntrl = :cntrl";
    $qu_prm = ['session' => $ses_id, "cntrl" => $x_token];
    $session = fncQuery($qu, $qu_prm)->fetch();

    if ($session) {
      $usr_id = $session['usr_id'];
    }

    $id = $_POST["id"];
    $qu = " SELECT u.is_coach AS is_coach, u.last_name AS last_name, u.name AS name, u.md_name AS md_name, u.phone AS phone, u.email AS email,
            u.users_group AS users_group, u.actual AS actual, ug.name AS group_name, u.login AS login, u.pswd AS pswd
            FROM users AS u
            LEFT JOIN users_groups AS ug ON ug.id = u.users_group
            WHERE u.id = :id";
    $qu_prm = ["id" => $usr_id];
    $result = fncQuery($qu, $qu_prm)->fetch();
    if (isset($result["login"])) {
      $login = "*****";
    } else {
      $login = $result["login"];
    }
    if (isset($result["pswd"])) {
      $pswd = "****";
    } else {
      $pswd = $result["pswd"];
    }
    $out_array = ["id" => $usr_id, "last_name" => $result["last_name"], "name" => $result["name"], "md_name" => $result["md_name"], "phone" => $result["phone"], "email" => $result["email"], "actual" => $result["actual"], "login" => $login, "pin" => $pswd];
    echo json_encode($out_array);

  }
//==============================================================================
?>
