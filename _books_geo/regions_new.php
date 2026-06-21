<?php
    require_once('../app/includes/session_guard.php');
    fncRequireSession();

    $country_name = $_POST['country_name'];
    ?>
    <div class="col-12">
        <form id="formNew">
            <div class="row">
                <div class="col-12 mb-3">
                    <div class="form-context"><?php echo htmlspecialchars($country_name); ?></div>
                    <label class="my-input-label">Название</label>
                    <input type="text" class="form-in form-inp"
                        data-name="reg-name" data-type="text" data-required="1" value="">
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
