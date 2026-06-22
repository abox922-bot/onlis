<?php
    require_once('../app/includes/session_guard.php');
    fncRequireSession();

    $ses_info = [
        '_onlis_id' => $_COOKIE['_onlis_id'],
        'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
    ];

    $id     = (int)$_POST['id'];
    $data   = array_merge($ses_info, ['action' => 'city_info', 'id' => $id]);
    $result = send_request($data, 'geo');
    ?>
    <div class="col-12">
        <form id="formInfo">
            <div class="row">
                <div class="col-12">
                    <div class="form-context">
                        <?php echo htmlspecialchars($result['country_name']); ?>
                        <span style="opacity:0.35;margin:0 6px">›</span>
                        <?php echo htmlspecialchars($result['region_name']); ?>
                    </div>
                </div>
                <div class="col-12 mb-3">
                    <label class="my-input-label">Название</label>
                    <input type="text" class="form-in form-inp"
                        data-name="city-name" data-type="text" data-required="1"
                        value="<?php echo htmlspecialchars($result['name']); ?>">
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
