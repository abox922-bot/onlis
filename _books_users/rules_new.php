<?php
  if (isset($_COOKIE['_cntrl_nmb']) && isset($_COOKIE['_onlis_id'])) {
    $data = ["action" => "in_cntrl"];
    $data = array_merge($_COOKIE, $data);
    $result = send_request($data, "main");
    $today = $result["today"];
    if ($result["sccss"]) {
      ?>
        <div class="col-12">
          <form id="formNew">
            <div class="row">
              <div class="col-12 mb-2">
                <label for="inpName" class="form-label mb-0">Название</label>
                <input type="text" class="form-control form-control-sm form-inp" id="inpName" data-name="rul-name" data-type="text" data-required="1" value="">
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
