<?php
    require_once('../app/includes/session_guard.php');
    $result = fncRequireSession();

    $ses_info = [
        '_onlis_id' => $_COOKIE['_onlis_id'],
        'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
    ];

    $id     = $_POST["id"];
    $data   = array_merge($ses_info, ["action" => "rules_info", "id" => $id]);
    $result = send_request($data, "users");
    ?>
      <div class="col-12">
        <form id="formInfo">
          <div class="row">
            <div class="col-12 mb-2">
              <label for="inpName" class="form-label mb-0">Название</label>
              <input type="text" class="form-control form-control-sm form-inp" id="inpName" data-name="rul-name" data-type="text" data-required="1" value="<?php echo $result["name"]; ?>">
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
