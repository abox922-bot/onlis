<?php
//==============================================================================
  $action = $_POST["action"];
  require_once('./includes/fncs.php');
  $out_array = [];
  if ($action == "new_country") {
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
    $fields_list[] = ["name" => "name", "type" => "s", "value" => fncValFind("cntr-name", $params)];
    $result = fncInsCrt($fields_list, "countries");
    echo $result;
  } elseif ($action == "countries_list") {
    $qu = "SELECT `id`, `name`, `full_name` FROM `countries` ORDER BY `name`";
      if ($stmt = $mysqli->prepare($qu)) {
        $stmt->execute();
        $stmt->bind_result($id, $name, $full_name);
        while ($stmt->fetch()) {
          $out_array[] = ["id" => $id, "name" => $name, "full_name" => $full_name];
        }
        $stmt->close();
      }
    echo json_encode($out_array);
  } elseif ($action == "country_info") {
    $id = $_POST["id"];
    $qu = "SELECT `name`, `full_name`, `phone_code`, `phone_mask` FROM `countries` WHERE `id` = ?";
      if ($stmt = $mysqli->prepare($qu)) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($name, $full_name, $phone_code, $phone_mask);
        $stmt->fetch();
        $stmt->close();
      }
    $out_array = ["id" => $id, "name" => $name, "full_name" => $full_name, "phone_code" => $phone_code, "phone_mask" => $phone_mask];
    echo json_encode($out_array);
  } elseif ($action == "upd_country") {
    $params = json_decode($_POST["params"], true);
    $fields_list = [];
    $fields_list[] = ["name" => "name", "type" => "s", "value" => fncValFind("cntry-name", $params)];
    $fields_list[] = ["name" => "full_name", "type" => "s", "value" => fncValFind("cntry-fname", $params)];
    $fields_list[] = ["name" => "phone_code", "type" => "i", "value" => fncValFind("cntry-code", $params)];
    $fields_list[] = ["name" => "phone_mask", "type" => "s", "value" => fncValFind("cntry-mask", $params)];
    fncUpdCrt($fields_list, "countries", fncValFind("item-id", $params));
  } elseif ($action == "new_region") {
    $params = json_decode($_POST["params"], true);
    $fields_list = [];
    $fields_list[] = ["name" => "country", "type" => "i", "value" => fncValFind("country", $params)];
    $fields_list[] = ["name" => "name", "type" => "s", "value" => fncValFind("reg-name", $params)];
    $result = fncInsCrt($fields_list, "regions");
    echo $result;
  } elseif ($action == "regions_list") {
    $country = $_POST["country"];
    $qu = "SELECT `id`, `name` FROM `regions` WHERE `country` = ? ORDER BY `name`";
      if ($stmt = $mysqli->prepare($qu)) {
        $stmt->bind_param("i", $country);
        $stmt->execute();
        $stmt->bind_result($id, $name);
        while ($stmt->fetch()) {
          $out_array[] = ["id" => $id, "name" => $name];
        }
        $stmt->close();
      }
    echo json_encode($out_array);
  } elseif ($action == "region_info") {
    $id = $_POST["id"];
    $qu = " SELECT `regions`.`name`, `regions`.`region_code`, `regions`.`timezone`, `countries`.`name`
            FROM `regions`
            LEFT JOIN `countries` ON `countries`.`id` = `regions`.`country`
            WHERE `regions`.`id` = ?";
      if ($stmt = $mysqli->prepare($qu)) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($name, $region_code, $timezone, $country_name);
        $stmt->fetch();
        $stmt->close();
      }
    $out_array = ["id" => $id, "name" => $name, "reg_code" => $region_code, "timezone" => $timezone, "country_name" => $country_name];
    echo json_encode($out_array);
  } elseif ($action == "upd_region") {
    $params = json_decode($_POST["params"], true);
    $fields_list = [];
    $fields_list[] = ["name" => "name", "type" => "s", "value" => fncValFind("reg-name", $params)];
    $fields_list[] = ["name" => "region_code", "type" => "i", "value" => fncValFind("reg-code", $params)];
    $fields_list[] = ["name" => "timezone", "type" => "d", "value" => fncValFind("timezone", $params)];
    fncUpdCrt($fields_list, "regions", fncValFind("item-id", $params));
  } elseif ($action == "countries_regs_list") {
    $countries = [];
    $regions = [];
    $qu = "SELECT `id`, `name` FROM `countries` ORDER BY `name`";
      if ($stmt = $mysqli->prepare($qu)) {
        $stmt->execute();
        $stmt->bind_result($id, $name);
        while ($stmt->fetch()) {
          $countries[] = ["id" => $id, "name" => $name];
        }
        $stmt->close();
      }
    $qu = "SELECT `id`, `name`, `country` FROM `regions` ORDER BY `name`";
      if ($stmt = $mysqli->prepare($qu)) {
        $stmt->execute();
        $stmt->bind_result($id, $name, $country);
        while ($stmt->fetch()) {
          $regions[] = ["id" => $id, "name" => $name, "country" => $country];
        }
        $stmt->close();
      }
    $out_array = ["regions" => $regions, "countries" => $countries];
    echo json_encode($out_array);
  } elseif ($action == "new_city") {
    $params = json_decode($_POST["params"], true);
    $fields_list = [];
    $fields_list[] = ["name" => "country", "type" => "i", "value" => fncValFind("country", $params)];
    $fields_list[] = ["name" => "region", "type" => "i", "value" => fncValFind("region", $params)];
    $fields_list[] = ["name" => "name", "type" => "s", "value" => fncValFind("city-name", $params)];
    $result = fncInsCrt($fields_list, "cities");
    echo $result;
  } elseif ($action == "cities_list") {
    $country = $_POST["country"];
    $region = $_POST["region"];
    $qu = "SELECT `id`, `name` FROM `cities` WHERE `country` = ? AND `region` = ? ORDER BY `name`";
      if ($stmt = $mysqli->prepare($qu)) {
        $stmt->bind_param("ii", $country, $region);
        $stmt->execute();
        $stmt->bind_result($id, $name);
        while ($stmt->fetch()) {
          $out_array[] = ["id" => $id, "name" => $name];
        }
        $stmt->close();
      }
    echo json_encode($out_array);
  } elseif ($action == "city_info") {
    $id = $_POST["id"];
    $qu = " SELECT `cities`.`name`, `countries`.`name`, `regions`.`name`
            FROM `cities`
            LEFT JOIN `countries` ON `countries`.`id` = `cities`.`country`
            LEFT JOIN `regions` ON `regions`.`id` = `cities`.`region`
            WHERE `cities`.`id` = ?";
      if ($stmt = $mysqli->prepare($qu)) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($name, $country_name, $region_name);
        $stmt->fetch();
        $stmt->close();
      }
    $out_array = ["id" => $id, "name" => $name, "country_name" => $country_name, "region_name" => $region_name];
    echo json_encode($out_array);
  } elseif ($action == "upd_city") {
    $params = json_decode($_POST["params"], true);
    $fields_list = [];
    $fields_list[] = ["name" => "name", "type" => "s", "value" => fncValFind("city-name", $params)];
    fncUpdCrt($fields_list, "cities", fncValFind("item-id", $params));
  } elseif ($action == "cities_list_for_streets") {
    $qu = " SELECT `cities`.`id`, `cities`.`name`, CONCAT_WS(', ', `countries`.`name`, `regions`.`name`), `cities`.`country`, `cities`.`region`
            FROM `cities`
            LEFT JOIN `countries` ON `countries`.`id` = `cities`.`country`
            LEFT JOIN `regions` ON `regions`.`id` = `cities`.`region`
            ORDER BY `cities`.`name`";
      if ($stmt = $mysqli->prepare($qu)) {
        $stmt->execute();
        $stmt->bind_result($id, $name, $reg_name, $country, $region);
        while ($stmt->fetch()) {
          $out_array[] = ["id" => $id, "name" => $name, "reg_name" => $reg_name, "country" => $country, "region" => $region];
        }
        $stmt->close();
      }
    echo json_encode($out_array);
  } elseif ($action == "streets_types_list") {
    $qu = "SELECT `id`, `name` FROM `streets_types` ORDER BY `name`";
      if ($stmt = $mysqli->prepare($qu)) {
        $stmt->execute();
        $stmt->bind_result($id, $name);
        while ($stmt->fetch()) {
          $out_array[] = ["id" => $id, "name" => $name];
        }
        $stmt->close();
      }
    echo json_encode($out_array);
  } elseif ($action == "new_street") {
    $params = json_decode($_POST["params"], true);
    $fields_list = [];
    $fields_list[] = ["name" => "country", "type" => "i", "value" => fncValFind("country", $params)];
    $fields_list[] = ["name" => "region", "type" => "i", "value" => fncValFind("region", $params)];
    $fields_list[] = ["name" => "city", "type" => "i", "value" => fncValFind("city", $params)];
    $fields_list[] = ["name" => "type", "type" => "i", "value" => fncValFind("street-type", $params)];
    $fields_list[] = ["name" => "name", "type" => "s", "value" => fncValFind("street-name", $params)];
    $result = fncInsCrt($fields_list, "streets");
    echo $result;
  } elseif ($action == "streets_list") {
    $city = $_POST["city"];
    $region = $_POST["region"];
    $qu = " SELECT `streets`.`id`, CONCAT_WS(', ', `streets`.`name`, `streets_types`.`name`)
            FROM `streets`
            LEFT JOIN `streets_types` ON `streets_types`.`id` = `streets`.`type`
            WHERE `streets`.`city` = ?
            ORDER BY `streets`.`name`";
      if ($stmt = $mysqli->prepare($qu)) {
        $stmt->bind_param("i", $city);
        $stmt->execute();
        $stmt->bind_result($id, $name);
        while ($stmt->fetch()) {
          $out_array[] = ["id" => $id, "name" => $name];
        }
        $stmt->close();
      }
    echo json_encode($out_array);
  } elseif ($action == "street_info") {
    $types = [];
    $id = $_POST["id"];
    $qu = "SELECT `id`, `name` FROM `streets_types` ORDER BY `name`";
      if ($stmt = $mysqli->prepare($qu)) {
        $stmt->execute();
        $stmt->bind_result($type_id, $name);
        while ($stmt->fetch()) {
          $types[] = ["id" => $type_id, "name" => $name];
        }
        $stmt->close();
      }
    $qu = "SELECT `name`, `type` FROM `streets` WHERE `id` = ?";
      if ($stmt = $mysqli->prepare($qu)) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($name, $type);
        $stmt->fetch();
        $stmt->close();
      }
    $out_array = ["id" => $id, "name" => $name, "type" => $type, "types" => $types];
    echo json_encode($out_array);
  } elseif ($action == "upd_street") {
    $params = json_decode($_POST["params"], true);
    $fields_list = [];
    $fields_list[] = ["name" => "name", "type" => "s", "value" => fncValFind("street-name", $params)];
    $fields_list[] = ["name" => "type", "type" => "s", "value" => fncValFind("street-type", $params)];
    fncUpdCrt($fields_list, "streets", fncValFind("item-id", $params));
  }
  $mysqli->close();
//==============================================================================
?>
