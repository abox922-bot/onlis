<?php
  if (isset($_SERVER['HTTP_X_CSRF_TOKEN']) && isset($_COOKIE['_onlis_id'])) {
    require_once("./app/includes/request.php");
    $data = ["action" => "in_cntrl"];
    $ses_info = ["_onlis_id" => $_COOKIE["_onlis_id"], "x_token" => $_SERVER['HTTP_X_CSRF_TOKEN']];
    $data = array_merge($ses_info, $data);
    $result = send_request($data, "main");
    if ($result["sccss"]) {
      ?>
        <div class="col-12 py-2" style="border-radius: 5px; background-color: #fff;">
          <div class="dropdown div-chpt-control">
            <button class="btn-action dropdown-toggle mt-2" id="btnSlct" type="button" data-bs-toggle="dropdown" aria-expanded="false">
              Пользователи
            </button>
            <ul class="dropdown-menu custom-dropdown-menu">
              <li class="list-group-item liSlct" data-target="users" style="cursor: pointer;">
                <div class="dropdown-item">
                  <span class="liSlctItem" data-target="users">Пользователи</span>
                </div>
              </li>
              <li class="list-group-item liSlct" data-target="groups" style="cursor: pointer;">
                <div class="dropdown-item">
                  <span class="liSlctItem" data-target="groups">Роли пользователей</span>
                </div>
              </li>
            </ul>
          </div>
          <div class="row mt-2" id="rowContent">
            <div class="col-12">
              <div class="spinner-border spinner-border-sm" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
            </div>
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
