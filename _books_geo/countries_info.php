<?php
    require_once('../app/includes/session_guard.php');
    fncRequireSession();

    $ses_info = [
        '_onlis_id' => $_COOKIE['_onlis_id'],
        'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
    ];

    $id     = (int)$_POST['id'];
    $data   = array_merge($ses_info, ['action' => 'country_info', 'id' => $id]);
    $result = send_request($data, 'geo');
    ?>
    <div class="col-12">
        <form id="formInfo">
            <div class="row">
                <div class="col-12 mb-3">
                    <label class="my-input-label">Полное название</label>
                    <input type="text" class="form-in form-inp"
                        data-name="cntry-fname" data-type="text" data-required="1"
                        value="<?php echo htmlspecialchars($result['full_name']); ?>">
                </div>
                <div class="col-12 col-md-6 mb-4">
                    <label class="my-input-label">Краткое название</label>
                    <input type="text" class="form-in form-inp"
                        data-name="cntry-name" data-type="text" data-required="1"
                        value="<?php echo htmlspecialchars($result['name']); ?>">
                </div>
                <div class="col-12 mb-2">
                    <span class="form-group-label">Телефония</span>
                </div>
                <div class="col-12 col-md-4 mb-3">
                    <label class="my-input-label">Код</label>
                    <input type="text" class="form-in form-inp"
                        data-name="cntry-code" data-type="digits_only" data-required="1"
                        value="<?php echo htmlspecialchars($result['phone_code']); ?>">
                </div>
                <div class="col-12 col-md-8 mb-3">
                    <label class="my-input-label">Маска номера</label>
                    <input type="text" class="form-in form-inp"
                        data-name="cntry-mask" data-type="without_letters" data-required="1"
                        placeholder="+7 (000) 000-00-00"
                        value="<?php echo htmlspecialchars($result['phone_mask']); ?>">
                </div>
                <div class="col-12 mt-2">
                    <button type="submit" class="btn-action-main" id="btnSave">
                        <span id="btnSaveText">Сохранить</span>
                        <div class="spinner-border spinner-border-sm d-none" id="divSaveLoading"></div>
                    </button>
                </div>
            </div>
        </form>
    </div>
