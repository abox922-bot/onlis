<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id = (int)($_POST['id'] ?? 0);

$utility = send_request(array_merge($ses_info, ['action' => 'object_utility_type_info', 'id' => $id]), 'objs');
if (!is_array($utility) || isset($utility['sccss'])) {
    $utility = [];
}

?>
<div class="row">
    <?php if (fncCan(fncRequireSession()['rules'], 'objects.manage')): ?>
        <div class="col-12 mb-3">
            <button type="button" class="btn-action-main" id="btnNewReading">
                <i class="bi bi-plus-lg"></i>
                <span class="btn-label">Добавить показание</span>
            </button>
        </div>
        <div class="col-12 mb-3 d-none" id="divNewReading" style="background: rgba(0,0,0,0.03); border-radius: 8px; padding: 16px;">
            <form id="formReadingNew">
                <div class="row">
                    <div class="col-12 col-md-4 mb-2">
                        <label class="my-input-label" for="inpReadingDate">Дата</label>
                        <input type="date" class="form-in form-inp" id="inpReadingDate" data-name="reading_date" data-type="text" data-required="1">
                    </div>
                    <div class="col-12 col-md-4 mb-2">
                        <label class="my-input-label" for="inpReadingValue">Значение</label>
                        <input type="text" class="form-in form-inp" id="inpReadingValue" data-name="reading_value" data-type="digits_double" data-required="1">
                    </div>
                    <div class="col-12 col-md-4 mb-2">
                        <label class="my-input-label" for="inpReadingTariff">Тариф</label>
                        <input type="text" class="form-in form-inp" id="inpReadingTariff" data-name="tariff" data-type="digits_double" data-required="1" value="<?php echo htmlspecialchars($utility['current_tariff'] ?? ''); ?>">
                    </div>
                    <div class="col-12 mt-2 d-none" id="divReadingFormError">
                        <div class="form-error-msg" id="spnReadingFormError"></div>
                    </div>
                    <div class="col-12 mt-2">
                        <button type="submit" class="btn-action-main" id="btnReadingSave">
                            <span id="btnReadingSaveText">Сохранить</span>
                            <div class="spinner-border spinner-border-sm d-none" id="divReadingSaveLoading"></div>
                        </button>
                        <button type="button" class="btn-action-outline" id="btnReadingClose">Закрыть</button>
                    </div>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <div class="col-6 mb-3">
        <label class="my-input-label" for="inpHistStartDate">Начало</label>
        <input type="date" class="form-in" id="inpHistStartDate">
    </div>
    <div class="col-6 mb-3">
        <label class="my-input-label" for="inpHistEndDate">Окончание</label>
        <input type="date" class="form-in" id="inpHistEndDate">
    </div>

    <div class="col-12" id="divHistListContent"></div>
</div>
<script src="./_books_objs/js/object_info_rent_utilities_info_hist.js?2026070801"></script>
