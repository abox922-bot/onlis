<?php
require_once('./app/includes/session_guard.php');
$result = fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$data      = array_merge($ses_info, ['action' => 'profile_info']);
$result    = send_request($data, 'users');
$is_actual = $result['actual'];
?>
    <div class="row">
      <div class="col-12 mt-2 mb-3">
        <form id="formInfo" data-id="<?= $result["id"]; ?>">
          <div class="row g-3">

            <!-- Статус: актуальный / архивный -->
            <div class="btn-group" role="group">
              <input type="radio" class="btn-check btnItemActual" name="btnItemActual"
                     id="btnItemActual0" data-target="1" autocomplete="off"
                     <?= $is_actual ? 'checked' : '' ?> disabled>
              <label class="btn btn-sm" for="btnItemActual0">
                <i class="bi bi-check-circle me-1"></i>Актуальный
              </label>

              <input type="radio" class="btn-check btnItemActual" name="btnItemActual"
                     id="btnItemActual1" data-target="0" autocomplete="off"
                     <?= !$is_actual ? 'checked' : '' ?> disabled>
              <label class="btn btn-sm" for="btnItemActual1">
                <i class="bi bi-archive me-1"></i>Архивный
              </label>
            </div>

            <!-- ФИО -->
            <div class="col-12 col-md-4">
              <label for="inpLastName" class="my-input-label">Фамилия <span class="text-danger">*</span></label>
              <input type="text" class="form-in form-inp" id="inpLastName"
                     data-name="usr-last-name" data-type="text" data-required="1"
                     value="<?= htmlspecialchars($result["last_name"], ENT_QUOTES, 'UTF-8') ?>"
                     autocomplete="off" placeholder="Введите фамилию">
            </div>

            <div class="col-12 col-md-4">
              <label for="inpName" class="my-input-label">Имя <span class="text-danger">*</span></label>
              <input type="text" class="form-in form-inp" id="inpName"
                     data-name="usr-name" data-type="text" data-required="1"
                     value="<?= htmlspecialchars($result["name"], ENT_QUOTES, 'UTF-8') ?>"
                     autocomplete="off" placeholder="Введите имя">
            </div>

            <div class="col-12 col-md-4">
              <label for="inpMdName" class="my-input-label">Отчество</label>
              <input type="text" class="form-in form-inp" id="inpMdName"
                     data-name="usr-md-name" data-type="text"
                     value="<?= htmlspecialchars($result["md_name"], ENT_QUOTES, 'UTF-8') ?>"
                     autocomplete="off" placeholder="Введите отчество">
            </div>

            <!-- КОНТАКТЫ -->
            <div class="col-12 col-md-6">
              <label for="inpPhone" class="my-input-label">Телефон</label>
              <input type="text" class="form-in form-inp" id="inpPhone"
                     data-name="usr-phone" data-type="phone"
                     data-phone-code="7" data-phone-mask="+7 (000) 000-00-00"
                     value="<?= $result["phone"] ?>"
                     autocomplete="off" placeholder="+7 (000) 000-00-00">
            </div>

            <div class="col-12 col-md-6">
              <label for="inpEmail" class="my-input-label">E-mail</label>
              <input type="text" class="form-in form-inp" id="inpEmail"
                     data-name="usr-email" data-type="email"
                     value="<?= htmlspecialchars($result["email"], ENT_QUOTES, 'UTF-8') ?>"
                     autocomplete="off" placeholder="example@mail.ru">
            </div>

            <!-- ДОСТУП -->
            <div class="col-12 col-md-6">
              <label class="my-input-label">Логин для входа</label>
              <div class="input-group">
                <input type="text" class="form-in form-inp" id="inpLogin"
                       data-name="usr-login" data-type="text"
                       placeholder="Сгенерируйте логин"
                       value="<?= $result["login"] ?>" disabled>
                <button type="button" class="btn btn-generate" id="btnNewLogin"
                        title="Сгенерировать логин">
                  <i class="bi bi-arrow-counterclockwise"></i>
                </button>
              </div>
            </div>

            <div class="col-12 col-md-6">
              <label class="my-input-label">ПИН-код</label>
              <div class="input-group">
                <input type="text" class="form-in form-inp" id="inpPin"
                       data-name="usr-pin" data-type="text"
                       placeholder="Сгенерируйте пароль"
                       value="<?= $result["pin"] ?>" disabled>
                <button type="button" class="btn btn-generate border-start" id="btnNewPin"
                        title="Сгенерировать пин-код">
                  <i class="bi bi-arrow-counterclockwise"></i>
                </button>
              </div>
            </div>

            <!-- КНОПКИ -->
            <div class="col-12 mt-2">
              <div class="d-flex gap-2">
                <button type="submit" class="btn-action-main d-flex align-items-center gap-2" id="btnSave">
                  <span id="btnText">Сохранить</span>
                  <div class="spinner-border spinner-border-sm d-none" id="divSaveLoading"></div>
                </button>
                <button type="button" class="btn-action-outline" data-bs-dismiss="modal">
                  Отмена
                </button>
              </div>
            </div>

          </div>
        </form>
      </div>
    </div>
