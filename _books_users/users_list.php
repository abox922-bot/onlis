<?php
  if (isset($_SERVER['HTTP_X_CSRF_TOKEN']) && isset($_COOKIE['_onlis_id'])) {
    require_once("../app/includes/request.php");
    $data = ["action" => "in_cntrl"];
    $ses_info = ["_onlis_id" => $_COOKIE["_onlis_id"], "x_token" => $_SERVER['HTTP_X_CSRF_TOKEN']];
    $data = array_merge($ses_info, $data);
    $result = send_request($data, "main");
    if ($result["sccss"]) {
      $data = ["action" => "list"];
      $data = array_merge($ses_info, $data);
      $result = send_request($data, "users");
      ?>
        <div class="row">
          <div class="col-12">
            <table class="table table-hover caption-top">
              <caption>Список сотрудников</caption>
              <thead>
                <tr>
                  <th scope="col">Фамилия Имя</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($result as $value): ?>
                  <tr class="itemTr <?= $value['actual'] == 0 ? 'd-none' : '' ?>"
                      data-id="<?= $value['id'] ?>"
                      data-actual="<?= $value['actual'] ?? 0 ?>">
                    <td>
                      <span class="itemName" data-id="<?= $value['id'] ?>">
                        <?= $value['name'] ?>
                      </span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
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
