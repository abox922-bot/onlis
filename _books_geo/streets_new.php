<?php
    require_once('../app/includes/session_guard.php');
    fncRequireSession();

    $ses_info = [
        '_onlis_id' => $_COOKIE['_onlis_id'],
        'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
    ];

    $city_name = $_POST['city_name'];
    $data      = array_merge($ses_info, ['action' => 'streets_types_list']);
    $result    = send_request($data, 'geo');
    ?>
    <div class="col-12">
        <form id="formNew">
            <div class="row">
                <div class="col-12 mb-3">
                    <div class="form-context"><?php echo htmlspecialchars($city_name); ?></div>
                </div>
                <div class="col-12 col-md-5 mb-3">
                    <label class="my-input-label">Тип</label>
                    <select class="form-in form-inp"
                        data-name="street-type" data-type="select" data-required="1">
                        <option value="0">Выберите тип</option>
                        <?php foreach ($result as $value): ?>
                            <option value="<?php echo $value['id']; ?>"><?php echo htmlspecialchars($value['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-7 mb-3">
                    <label class="my-input-label">Название</label>
                    <input type="text" class="form-in form-inp"
                        data-name="street-name" data-type="text" data-required="1" value="">
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
