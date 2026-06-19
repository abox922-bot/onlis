<?php
    require_once('../app/includes/session_guard.php');
    $result = fncRequireSession();

    $ses_info = [
        '_onlis_id' => $_COOKIE['_onlis_id'],
        'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
    ];

    $id = $_POST["id"];
    $data   = array_merge($ses_info, ["action" => "country_info", "id" => $id]);
    $result = send_request($data, "geo");
    ?>
      <div class="col-12">
        <form id="formInfo">
          <div class="row">
            <div class="col-12 col-md-6 mb-2">
              <label for="inpFName" class="form-label mb-0">Полное название</label>
              <input type="text" class="form-control form-control-sm form-inp" id="inpFName" data-name="cntry-fname" data-type="text" data-required="1" value="<?php echo $result["full_name"]; ?>">
            </div>
            <div class="col-12 col-md-6 mb-2">
              <label for="inpName" class="form-label mb-0">Название</label>
              <input type="text" class="form-control form-control-sm form-inp" id="inpName" data-name="cntry-name" data-type="text" data-required="1" value="<?php echo $result["name"]; ?>">
            </div>
            <div class="col-12 col-md-6 mb-2">
              <label for="inpCode" class="form-label mb-0">Телефоннай код</label>
              <input type="text" class="form-control form-control-sm form-inp" id="inpCode" data-name="cntry-code" data-type="digits_only" data-required="1" value="<?php echo $result["phone_code"]; ?>">
            </div>
            <div class="col-12 col-md-6 mb-2">
              <label for="inpMask" class="form-label mb-0">Маска ввода номера</label>
              <input type="text" class="form-control form-control-sm form-inp" id="inpMask" data-name="cntry-mask" data-type="without_letters" data-required="1" value="<?php echo $result["phone_mask"]; ?>">
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
