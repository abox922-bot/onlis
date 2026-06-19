<?php
  if (isset($_COOKIE['_cntrl_nmb']) && isset($_COOKIE['_onlis_id'])) {
    $data = ["action" => "in_cntrl"];
    $data = array_merge($_COOKIE, $data);
    $result = send_request($data, "main");
    if ($result["sccss"]) {
      ?>
      <div class="col-12 mt-3">
        <div class="row">
          <div class="col-12 d-flex">
            <button type="button" class="btn btn-sm btn-success mx-1" id="btnFastNew">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus-circle" style="vertical-align: sub;" viewBox="0 0 16 16">
                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
              </svg>
            </button>
            <input type="text" class="form-control form-control-sm" id="inpSearchVal" value="" placeholder="поиск" style="max-width: 250px; background-color: #fff;">
          </div>
          <div class="col-12 mt-3" id="divChptContent">
            <div class="spinner-border spinner-border-sm d-none" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
          </div>
        </div>
      </div>
      <script src="./_books_users/js/rules.js?2025033000"></script>
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
