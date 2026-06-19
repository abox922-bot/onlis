<?php
    require_once('../app/includes/session_guard.php');
    $result = fncRequireSession();

    $ses_info = [
        '_onlis_id' => $_COOKIE['_onlis_id'],
        'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
    ];

    $data   = array_merge($ses_info, ["action" => "countries_list"]);
    $result = send_request($data, "geo");
    ?>
      <div class="col-12">
        <table class="table table-sm table-hover caption-top mt-2">
          <caption>Список стран</caption>
          <tbody>
            <?php
              foreach ($result as $key => $value) {
                ?>
                  <tr class="itemTr" data-id="<?php echo $value["id"]; ?>" style="cursor: pointer;">
                    <td class="py-2" style="line-height: 1.1em;">
                      <?php echo $value["name"]; ?>
                      <div>
                        <small class="text-muted itemName" data-id="<?php echo $value["id"]; ?>"><?php echo $value["full_name"]; ?></small>
                      </div>
                    </td>
                  </tr>
                <?php
              }
            ?>
          </tbody>
        </table>
      </div>
