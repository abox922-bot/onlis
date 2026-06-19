<?php
  require_once('../app/includes/session_guard.php');
  $result = fncRequireSession();

  $ses_info = [
      '_onlis_id' => $_COOKIE['_onlis_id'],
      'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
  ];

  $city_name  = $_POST["city_name"];
  $data       = array_merge($ses_info, ["action" => "streets_types_list"]);
  $result     = send_request($data, "geo");
  ?>
    <div class="col-12">
      <form id="formNew">
        <div class="row">
          <div class="col-12 mb-2">
            <label for="inpCity" class="form-label mb-0">Город</label>
            <input type="text" class="form-control form-control-sm" id="inpCity" value="<?php echo $city_name; ?>" disabled>
          </div>
          <div class="col-12 col-md-6 mb-2">
            <label for="inpType" class="form-label mb-0">Тип улицы</label>
            <select class="form-select form-select-sm form-inp" id="inpType" data-name="street-type" data-type="select" data-required="1">
              <option value="0">выбери тип</option>
              <?php
                foreach ($result as $key => $value) {
                  ?>
                    <option value="<?php echo $value["id"]; ?>"><?php echo $value["name"]; ?></option>
                  <?php
                }
              ?>
            </select>
          </div>
          <div class="col-12 col-md-6 mb-2">
            <label for="inpName" class="form-label mb-0">Название</label>
            <input type="text" class="form-control form-control-sm form-inp" id="inpName" data-name="street-name" data-type="text" data-required="1" value="">
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
