<?php
    require_once('../app/includes/session_guard.php');
    $result = fncRequireSession();

    $ses_info = [
        '_onlis_id' => $_COOKIE['_onlis_id'],
        'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
    ];

    $data   = array_merge($ses_info, ["action" => "countries_regs_list"]);
    $result = send_request($data, "geo");
    ?>
    <div class="col-12 mt-3">
      <div class="row">
        <div class="col-12 d-flex mb-2">
          <select class="form-select form-select-sm mx-1" id="slctCountry" style="max-width: 250px; background-color: #fff;">
            <option value="0">выбери страну</option>
            <?php
              foreach ($result["countries"] as $key => $value) {
                ?>
                  <option value="<?php echo $value["id"]; ?>"><?php echo $value["name"]; ?></option>
                <?php
              }
            ?>
          </select>
          <select class="form-select form-select-sm" id="slctRegion" style="max-width: 250px; background-color: #fff;" disabled>
            <option value="0">выбери регион</option>
            <?php
              foreach ($result["regions"] as $key => $value) {
                ?>
                  <option value="<?php echo $value["id"]; ?>" data-country="<?php echo $value["country"]; ?>"><?php echo $value["name"]; ?></option>
                <?php
              }
            ?>
          </select>
        </div>
        <div class="col-12 d-flex">
          <button type="button" class="btn btn-sm btn-success mx-1" id="btnFastNew" disabled>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus-circle" style="vertical-align: sub;" viewBox="0 0 16 16">
              <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
              <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
            </svg>
          </button>
          <input type="text" class="form-control form-control-sm" id="inpSearchVal" value="" placeholder="поиск" style="max-width: 250px; background-color: #fff;">
        </div>
        <div class="col-12 mt-3 d-none" id="divChptContent">
          <div class="spinner-border spinner-border-sm d-none" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>
      </div>
    </div>
    <script src="./_books_geo/js/cities.js?2025032601"></script>
