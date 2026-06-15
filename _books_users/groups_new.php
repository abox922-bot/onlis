<?php
  if (isset($_SERVER['HTTP_X_CSRF_TOKEN']) && isset($_COOKIE['_onlis_id'])) {
    require_once("../app/includes/request.php");
    $data = ["action" => "in_cntrl"];
    $ses_info = ["_onlis_id" => $_COOKIE["_onlis_id"], "x_token" => $_SERVER['HTTP_X_CSRF_TOKEN']];
    $data = array_merge($ses_info, $data);
    $result = send_request($data, "main");
    if ($result["sccss"]) {
      ?>
        <div class="row">
          <div class="col-12">
            <form id="formNew">
              <div class="row">
                <div class="col-12 mb-2">
                  <label for="inpName" class="form-label">Название</label>
                  <input type="text" class="form-in form-inp" id="inpName" data-name="group-name" data-type="text" data-required="1" value="" autocomplete="off" placeholder="введите название">
                </div>
                <div class="col-12 mt-3">
                  <button type="submit" class="btn-action-main" id="btnSave">
                    <span id="btnText">сохранить</span>
                    <div class="spinner-border spinner-border-sm d-none" id="divSaveLoading"></div>
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      <?php
    } else {
      echo "Oops... something went wrong...";
    }
  } else {
    echo "Oops... something went wrong...";
  }
?>
