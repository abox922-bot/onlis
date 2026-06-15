<?php
  if (isset($_SERVER['HTTP_X_CSRF_TOKEN']) && isset($_COOKIE['_onlis_id'])) {
    require_once("../app/includes/request.php");
    $data = ["action" => "in_cntrl"];
    $ses_info = ["_onlis_id" => $_COOKIE["_onlis_id"], "x_token" => $_SERVER['HTTP_X_CSRF_TOKEN']];
    $data = array_merge($ses_info, $data);
    $result = send_request($data, "main");
    if ($result["sccss"]) {
      $data_groups = ["action" => "groups_list"];
      $data_groups = array_merge($ses_info, $data_groups);
      $groups = send_request($data_groups, "users");
      ?>
        <div class="row">
          <div class="col-12 mt-2 mb-3">
            <form id="formNew">
              <div class="row g-3">

                <!-- РОЛЬ И ТРЕНЕР -->
                <div class="col-12">
                  <div class="d-flex align-items-center gap-3 flex-wrap">

                    <!-- Выбор роли -->
                    <div class="d-flex align-items-center gap-2">
                      <i class="bi bi-shield-lock text-muted" style="font-size: 0.85rem;"></i>
                      <select class="form-in form-inp" id="inpGroup"
                              data-name="usr-group" data-type="select" data-required="1"
                              style="height: 36px; width: auto; padding: 4px 12px;">
                        <option value="0">— роль —</option>
                        <?php foreach ($groups as $group): ?>
                          <option value="<?= $group["id"] ?>"
                            <?= $group["id"] == 2 ? 'selected' : '' ?>>
                            <?= $group["name"] ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>

                    <!-- Свитч тренера -->
                    <div class="form-check form-switch mb-0">
                      <input class="form-check-input form-inp" type="checkbox" role="switch"
                             id="chckCoach" data-name="usr-coach" data-type="check"
                             disabled checked>
                      <label class="form-check-label small fw-semibold" for="chckCoach">
                        Является тренером
                      </label>
                    </div>

                  </div>
                </div>

                <div class="col-12"><hr class="my-1"></div>

                <!-- ФИО -->
                <div class="col-12 col-md-4">
                  <label for="inpLastName" class="my-input-label">Фамилия <span class="text-danger">*</span></label>
                  <input type="text" class="form-in form-inp" id="inpLastName"
                         data-name="usr-last-name" data-type="text" data-required="1"
                         autocomplete="off" placeholder="Введите фамилию">
                </div>

                <div class="col-12 col-md-4">
                  <label for="inpName" class="my-input-label">Имя <span class="text-danger">*</span></label>
                  <input type="text" class="form-in form-inp" id="inpName"
                         data-name="usr-name" data-type="text" data-required="1"
                         autocomplete="off" placeholder="Введите имя">
                </div>

                <div class="col-12 col-md-4">
                  <label for="inpMdName" class="my-input-label">Отчество</label>
                  <input type="text" class="form-in form-inp" id="inpMdName"
                         data-name="usr-md-name" data-type="text"
                         autocomplete="off" placeholder="Введите отчество">
                </div>

                <!-- КОНТАКТЫ -->
                <div class="col-12 col-md-6">
                  <label for="inpPhone" class="my-input-label">Телефон</label>
                  <input type="text" class="form-in form-inp" id="inpPhone"
                         data-name="usr-phone" data-type="phone"
                         data-phone-code="7" data-phone-mask="+7 (000) 000-00-00"
                         autocomplete="off" placeholder="+7 (000) 000-00-00">
                </div>

                <div class="col-12 col-md-6">
                  <label for="inpEmail" class="my-input-label">E-mail</label>
                  <input type="text" class="form-in form-inp" id="inpEmail"
                         data-name="usr-email" data-type="email"
                         autocomplete="off" placeholder="example@mail.ru">
                </div>

                <div class="col-12"><hr class="my-1"></div>

                <!-- КНОПКИ -->
                <div class="col-12 mt-2">
                  <div class="d-flex gap-2">
                    <button type="submit" class="btn-action-main d-flex align-items-center gap-2" id="btnSave">
                      <span id="btnText">Создать</span>
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

      <?php
    } else {
      echo "Oops... something went wrong...";
    }
  } else {
    echo "Oops... something went wrong...";
  }
?>
