<?php
  if (isset($_COOKIE['_cntrl_nmb']) && isset($_COOKIE['_onlis_id'])) {
    $data = ["action" => "in_cntrl"];
    $data = array_merge($_COOKIE, $data);
    $result = send_request($data, "main");
    $today = $result["today"];
    if ($result["sccss"]) {
      $id = $_POST["id"];
      $data = ["action" => "status_info", "id" => $id];
      $data = array_merge($_COOKIE, $data);
      $result = send_request($data, "users");
      ?>
        <div class="col-12">
          <form id="formInfo">
            <div class="row">
              <div class="col-12 col-md-6 mb-2">
                <label for="inpName" class="form-label mb-0">Название</label>
                <input type="text" class="form-control form-control-sm form-inp" id="inpName" data-name="stat-name" data-type="text" data-required="1" value="<?php echo $result["name"]; ?>">
              </div>
              <div class="col-12 col-md-6 mb-2">
                <label for="inpRules" class="form-label mb-0">Права доступа</label>
                <select class="form-select form-select-sm form-inp" id="inpRules" data-name="stat-rules" data-type="select" data-required="1">
                  <option value="0">выбери</option>
                  <?php
                    foreach ($result["rules_list"] as $key => $value) {
                      ?>
                        <option value="<?php echo $value["id"]; ?>" <?php if($value["id"] == $result["rules"]){echo "selected";} ?>><?php echo $value["name"]; ?></option>
                      <?php
                    }
                  ?>
                </select>
              </div>
              <div class="col-12 mt-3">
                <button type="submit" class="btn btn-sm btn-outline-success" id="btnSave">сохранить</button>
                <div class="spinner-border spinner-border-sm d-none" role="status" id="divSaveLoading">
                  <span class="visually-hidden">Loading...</span>
                </div>
              </div>
            </div>
          </form>
        </div>
      <?php
    } else {
      echo "Oops... something went wrong...";
    }
  } else {
    echo "Oops... something went wrong...";
  }
  //============================================================================
  function send_request($data, $module){
    require('../includes/paths.php');

    $my_сurl = curl_init();
    $curl_data = [CURLOPT_URL => $urls[$module],
                  CURLOPT_RETURNTRANSFER => true,
                  CURLOPT_POST => true,
                  CURLOPT_POSTFIELDS => http_build_query($data)];
    curl_setopt_array($my_сurl, $curl_data);
    $response = json_decode(curl_exec($my_сurl), true);
    curl_close($my_сurl);
    return $response;
  }
  //============================================================================
?>
