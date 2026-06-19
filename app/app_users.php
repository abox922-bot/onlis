<?php
//==============================================================================
  $action = $_POST["action"];
  require_once('./includes/fncs.php');
  $out_array = [];
  if ($action == "new_rules") {
    $ses_id = $_POST["_onlis_id"];
    $params = json_decode($_POST["params"], true);
    $qu = " SELECT `user`, NOW() FROM `sessions` WHERE `session` = ?";
      if ($stmt = $mysqli->prepare($qu)) {
        $stmt->bind_param("s", $ses_id);
        $stmt->execute();
        $stmt->bind_result($usr_id, $now);
        $stmt->fetch();
        $stmt->close();
      }
    $fields_list = [];
    $fields_list[] = ["name" => "name", "type" => "s", "value" => fncValFind("rul-name", $params)];
    $fields_list[] = ["name" => "crt_usr", "type" => "i", "value" => $usr_id];
    $fields_list[] = ["name" => "crt_time", "type" => "s", "value" => $now];
    fncInsCrt($fields_list, "rules");
  } elseif ($action == "rules_list") {
    $qu = "SELECT `id`, `name` FROM `rules` ORDER BY `name`";
      if ($stmt = $mysqli->prepare($qu)) {
        //$stmt->bind_param("s", $ses_id);
        $stmt->execute();
        $stmt->bind_result($id, $name);
        while ($stmt->fetch()) {
          $out_array[] = ["id" => $id, "name" => $name];
        }
        $stmt->close();
      }
    echo json_encode($out_array);
  } elseif ($action == "rules_info") {
    $id = $_POST["id"];
    $qu = "SELECT `name` FROM `rules` WHERE `id` = ?";
      if ($stmt = $mysqli->prepare($qu)) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($name);
        $stmt->fetch();
        $stmt->close();
      }
    $out_array = ["id" => $id, "name" => $name];
    echo json_encode($out_array);
  } elseif ($action == "upd_rules") {
    $ses_id = $_POST["_onlis_id"];
    $params = json_decode($_POST["params"], true);
    $fields_list = [];
    $fields_list[] = ["name" => "name", "type" => "s", "value" => fncValFind("rul-name", $params)];
    fncUpdCrt($fields_list, "rules", fncValFind("item-id", $params));
  } elseif ($action == "new_status") {
    $ses_id = $_POST["_onlis_id"];
    $params = json_decode($_POST["params"], true);
    $qu = " SELECT `user`, NOW() FROM `sessions` WHERE `session` = ?";
      if ($stmt = $mysqli->prepare($qu)) {
        $stmt->bind_param("s", $ses_id);
        $stmt->execute();
        $stmt->bind_result($usr_id, $now);
        $stmt->fetch();
        $stmt->close();
      }
    $fields_list = [];
    $fields_list[] = ["name" => "name", "type" => "s", "value" => fncValFind("stat-name", $params)];
    $fields_list[] = ["name" => "rules", "type" => "i", "value" => fncValFind("stat-rules", $params)];
    $fields_list[] = ["name" => "crt_usr", "type" => "i", "value" => $usr_id];
    $fields_list[] = ["name" => "crt_time", "type" => "s", "value" => $now];
    fncInsCrt($fields_list, "users_statuses");
  } elseif ($action == "statuses_list") {
    $qu = "SELECT `id`, `name` FROM `users_statuses` ORDER BY `name`";
      if ($stmt = $mysqli->prepare($qu)) {
        $stmt->execute();
        $stmt->bind_result($id, $name);
        while ($stmt->fetch()) {
          $out_array[] = ["id" => $id, "name" => $name];
        }
        $stmt->close();
      }
    echo json_encode($out_array);
  } elseif ($action == "status_info") {
    $id = $_POST["id"];
    $rul_arr = [];
    $qu = "SELECT `name`, `rules` FROM `users_statuses` WHERE `id` = ?";
      if ($stmt = $mysqli->prepare($qu)) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($name, $rules);
        $stmt->fetch();
        $stmt->close();
      }
    $qu = "SELECT `id`, `name` FROM `rules` ORDER BY `name`";
      if ($stmt = $mysqli->prepare($qu)) {
        $stmt->execute();
        $stmt->bind_result($rul_id, $rul_name);
        while ($stmt->fetch()) {
          $rul_arr[] = ["id" => $rul_id, "name" => $rul_name];
        }
        $stmt->close();
      }
    $out_array = ["id" => $id, "name" => $name, "rules" => $rules, "rules_list" => $rul_arr];
    echo json_encode($out_array);
  } elseif ($action == "upd_status") {
    $ses_id = $_POST["_onlis_id"];
    $params = json_decode($_POST["params"], true);
    $fields_list = [];
    $fields_list[] = ["name" => "name", "type" => "s", "value" => fncValFind("stat-name", $params)];
    $fields_list[] = ["name" => "rules", "type" => "i", "value" => fncValFind("stat-rules", $params)];
    fncUpdCrt($fields_list, "users_statuses", fncValFind("item-id", $params));
  } elseif ($action == "users_list") {
    $qu = "SELECT `id`, CONCAT_WS(' ', `last_name`, `name`) FROM `users` ORDER BY `last_name`, `name`";
      if ($stmt = $mysqli->prepare($qu)) {
        $stmt->execute();
        $stmt->bind_result($id, $name);
        while ($stmt->fetch()) {
          $out_array[] = ["id" => $id, "name" => $name];
        }
        $stmt->close();
      }
    echo json_encode($out_array);
  }
  $mysqli->close();
//==============================================================================
?>
