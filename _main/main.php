<?php
  if (isset($_SERVER['HTTP_X_CSRF_TOKEN']) && isset($_COOKIE['_onlis_id'])) {
    require_once("../app/includes/request.php");
    $data = ["action" => "in_cntrl"];
    $ses_info = ["_onlis_id" => $_COOKIE["_onlis_id"], "x_token" => $_SERVER['HTTP_X_CSRF_TOKEN']];
    $data = array_merge($ses_info, $data);
    $result = send_request($data, "main");
    if ($result["sccss"]) {
      ?>
        <div id="divContent" class="pb-2">
            <div class="card-content-wrapper shadow-sm">

              <!-- ДАТЫ -->
              <div class="row">
                <div class="col-12 text-center" id="divDates">
                  <div class="spinner-border spinner-border-sm text-dark my-3" role="status">
        					  <span class="visually-hidden">Loading...</span>
        					</div>
                </div>
              </div>
              <!-- ОСНОВНАЯ СЕТКА -->
              <div class="row g-4" id="schRow">
                <div class="col-12 text-center">
                  <div class="spinner-border spinner-border-sm text-dark my-3" role="status">
        					  <span class="visually-hidden">Loading...</span>
        					</div>
                </div>
              </div>

          </div>
        </div>
        <script src="./_main/js/main.js?2026052410"></script>
      <?php
    } else {
      echo "Oops... something went wrong...";
    }
  } else {
    echo "Oops... something went wrong...";
  }
?>
