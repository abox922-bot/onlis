<?php
    require_once('../app/includes/session_guard.php');
    $result = fncRequireSession();

    $ses_info = [
        '_onlis_id' => $_COOKIE['_onlis_id'],
        'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
    ];

    $country = $_POST["country"];
    $region = $_POST["region"];
    $data   = array_merge($ses_info, ["action" => "cities_list", "country" => $country, "region" => $region]);
    $result = send_request($data, "geo");
    ?>
      <div class="col-12">
        <table class="table table-sm table-hover caption-top mt-2">
          <caption>Список городов</caption>
          <tbody>
            <?php
              foreach ($result as $key => $value) {
                ?>
                  <tr class="itemTr" data-id="<?php echo $value["id"]; ?>" style="cursor: pointer;">
                    <td class="itemName" data-id="<?php echo $value["id"]; ?>">
                      <?php echo $value["name"]; ?>
                    </td>
                  </tr>
                <?php
              }
            ?>
          </tbody>
        </table>
      </div>
