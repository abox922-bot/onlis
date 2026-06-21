<?php
    require_once('../app/includes/session_guard.php');
    fncRequireSession();
    ?>
    <div class="col-12">
        <form id="formNew">
            <div class="row">
                <div class="col-12 mb-3">
                    <label class="my-input-label">Название</label>
                    <input type="text" class="form-in form-inp"
                        data-name="cntr-name" data-type="text" data-required="1" value="">
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
