<?php
  if (isset($_COOKIE['_cntrl_nmb']) && isset($_COOKIE['_onlis_id'])) {
    $data = ["action" => "in_cntrl"];
    $data = array_merge($_COOKIE, $data);
    $result = send_request($data, "main");
    $today = $result["today"];
    if ($result["sccss"]) {
      $data = ["action" => "rules_list"];
      $data = array_merge($_COOKIE, $data);
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
      <?php
    } else {
      echo "Oops... something went wrong...";
    }
  } else {
    echo "Oops... something went wrong...";
  }
  //============================================================================
  function send_request($data, $module){
    require('../includes/paths.php');

    $my_сurl = curl_init();
    $curl_data = [CURLOPT_URL => $urls[$module],
                  CURLOPT_RETURNTRANSFER => true,
                  CURLOPT_POST => true,
                  CURLOPT_POSTFIELDS => http_build_query($data)];
    curl_setopt_array($my_сurl, $curl_data);
    $response = json_decode(curl_exec($my_сurl), true);
    curl_close($my_сurl);
    return $response;
  }
  //============================================================================
?>
