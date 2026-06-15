<?php
  if (isset($_SERVER['HTTP_X_CSRF_TOKEN']) && isset($_COOKIE['_onlis_id'])) {
    require_once("../app/includes/request.php");
    $data = ["action" => "in_cntrl"];
    $ses_info = ["_onlis_id" => $_COOKIE["_onlis_id"], "x_token" => $_SERVER['HTTP_X_CSRF_TOKEN']];
    $data = array_merge($ses_info, $data);
    $result = send_request($data, "main");
    if ($result["sccss"]) {
      ?>
        <div class="card-content-wrapper">

          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center">

              <button type="button" class="btn-action-main me-2" id="btnFastNew" disabled>
                <i class="bi bi-plus-lg me-1"></i> Создать
              </button>

              <div class="dropdown">
                <button class="btn-action-outline dropdown-toggle" id="btnActSlct" type="button" data-bs-toggle="dropdown">
                  Группы сотрудников
                </button>
                <ul class="dropdown-menu custom-dropdown-menu">
                  <li class="liAct" data-val="1" style="cursor: pointer;">
                    <span class="dropdown-item liActListName" data-val="1">Актуальные</span>
                  </li>
                  <li class="liAct" data-val="0" style="cursor: pointer;">
                    <span class="dropdown-item liActListName" data-val="0">Архивные</span>
                  </li>
                </ul>
              </div>

            </div>
          </div>

          <div id="divContent" class="content-text-area"></div>
        </div>
        <script src="./_books_users/js/groups.js?2026050702"></script>
      <?php
    } else {
      echo "Oops... something went wrong...";
    }
  } else {
    echo "Oops... something went wrong...";
  }
?>
