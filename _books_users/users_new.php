<?php
  if (isset($_COOKIE['_cntrl_nmb']) && isset($_COOKIE['_onlis_id'])) {
    $data = ["action" => "in_cntrl"];
    $data = array_merge($_COOKIE, $data);
    $result = send_request($data, "main");
    $today = $result["today"];
    if ($result["sccss"]) {
      ?>
        <div class="col-12">
          <form id="formUser">
            <div class="row">
              <div class="col-12 col-lg-4 mb-2">
                <label for="inpLastName" class="form-label mb-0">Фамилия</label>
                <input type="text" class="form-control form-control-sm form-inp" id="inpLastName" data-name="staff_last" data-type="text" data-required="1" value="">
              </div>
              <div class="col-12 col-lg-4 mb-2">
                <label for="inpName" class="form-label mb-0">Имя</label>
                <input type="text" class="form-control form-control-sm form-inp" id="inpName" data-name="staff_name" data-type="text" data-required="1" value="">
              </div>
              <div class="col-12 col-lg-4 mb-2">
                <label for="inpMdName" class="form-label mb-0">Отчество</label>
                <input type="text" class="form-control form-control-sm form-inp" id="inpMdName" data-name="staff_md" data-type="text" value="">
              </div>
              <div class="col-12 col-lg-4 mb-2">
                <label for="inpBDate" class="form-label mb-0">Дата рождения</label>
                <input type="date" class="form-control form-control-sm form-inp" id="inpBDate" data-name="staff_bdate" value="<?php echo $today; ?>">
              </div>
              <div class="col-12 col-lg-4 mb-2">
                <label for="inpPhone" class="form-label mb-0">Телефон</label>
                <input type="text" class="form-control form-control-sm onlis-inp-form" id="inpPhone" data-name='staff_phone' data-type="phone" data-phone-code="7" data-phone-mask="+7 (000) 000-00-00" value="" autocomplete="off">
              </div>
              <div class="col-12 col-lg-4 mb-2">
                <label for="inpEmail" class="form-label mb-0">Email</label>
                <input type="text" class="form-control form-control-sm onlis-inp-form" id="inpEmail" data-name='staff_email' data-type="email" value="" autocomplete="off">
              </div>
              <div class="col-12 mb-2">
                <label for="inpStatus" class="form-label mb-0">Должность</label>
                <input type="text" class="form-control form-control-sm form-inp" id="inpStatus" data-name="staff_status" value="">
              </div>
              <div class="col-12 mb-4">
                <label for="inpComment" class="form-label mb-0">Комментарий</label>
                <input type="text" class="form-control form-control-sm" id="inpComment" data-name="staff_comment" value="">
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
