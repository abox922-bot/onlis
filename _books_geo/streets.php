<?php
    require_once('../app/includes/session_guard.php');
    $result = fncRequireSession();

    $ses_info = [
        '_onlis_id' => $_COOKIE['_onlis_id'],
        'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
    ];

    $data   = array_merge($ses_info, ["action" => "cities_list_for_streets"]);
    $result = send_request($data, "geo");
    ?>
    <div class="col-12 mt-3">
      <div class="row">
        <div class="col-12 mb-2">
          <div>
            <input class="form-control form-control-sm" type="text" id="inpCity" value="" placeholder="выбери город" style="max-width: 250px; background-color: #fff;">
            <div class="row">
              <div class="ms-2 col-12" style="position: relative; max-width: 220px;" id="divCitiesList">
                <div class="row shadow-sm mb-2 bg-body rounded d-none" id="rowCitiesList" style="position: absolute; top: 0px; background-color: white; width: 100%; max-height: 200px; overflow: auto; z-index: 50;">
                  <div class="col-12 d-grid">
                    <table class="table table-sm table-bordered table-hover" style="font-size: 12px;">
                      <tbody>
                        <?php
                          foreach ($result as $key => $value) {
                            ?>
                              <tr class="trCitySrch" style="cursor: pointer;" data-id="<?php echo $value["id"]; ?>" data-region="<?php echo $value["region"]; ?>" data-country="<?php echo $value["country"]; ?>" data-selected="0">
                                <td style="line-height: 1.1em;">
                                  <span class="tdCitySrch" data-id="<?php echo $value["id"]; ?>"><?php echo $value["name"];?></span>
                                  <div class="text-muted">
                                    <small class="tdCityReg" data-id="<?php echo $value["id"]; ?>"><?php echo $value["reg_name"]; ?></small>
                                  </div>
                                </td>
                              </tr>
                            <?php
                          }
                        ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-12 col-md-6 d-flex">
          <button type="button" class="btn btn-sm btn-success me-1" id="btnFastNew" disabled>
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
    <script src="./_books_geo/js/streets.js?2025032601"></script>
