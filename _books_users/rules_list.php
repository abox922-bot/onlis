<?php
    require_once('../app/includes/session_guard.php');
    $result = fncRequireSession();

    $ses_info = [
        '_onlis_id' => $_COOKIE['_onlis_id'],
        'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
    ];

    $data   = array_merge($ses_info, ["action" => "rules_list"]);
    $result = send_request($data, "users");
    ?>
      <div class="col-12">
        <table class="table table-sm table-striped table-hover caption-top mt-2">
          <caption>Список прав доступа</caption>
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
