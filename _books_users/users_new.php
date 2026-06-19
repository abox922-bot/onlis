<?php
    require_once('../app/includes/session_guard.php');
    $result = fncRequireSession();

    $ses_info = [
        '_onlis_id' => $_COOKIE['_onlis_id'],
        'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
    ];

    $today = $result["today"];
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
