<?php
  if (isset($_SERVER['HTTP_X_CSRF_TOKEN']) && isset($_COOKIE['_onlis_id'])) {
    require_once("../app/includes/request.php");
    $data = ["action" => "in_cntrl"];
    $ses_info = ["_onlis_id" => $_COOKIE["_onlis_id"], "x_token" => $_SERVER['HTTP_X_CSRF_TOKEN']];
    $data = array_merge($ses_info, $data);
    $result = send_request($data, "main");
    $user = $result["user"];
    $rules = $result["rules"];
    $room_id = $_POST["id"];
    $room_name = $_POST["room_name"];
    $sub = $_POST["sub"];
    $hour = $_POST["book_time"];
    $next_hour = $hour + 1;
    $title = "$hour:00 – $next_hour:00";
    $date = $_POST["book_date"];
    if ($result["sccss"]) {
      ?>
        <div class="row">
          <div class="col-12">
            <div class="p-3">
                <div class="d-flex align-items-center gap-2 mb-3">
                  <span class="badge rounded-pill bg-dark" id="mRoomBadge"><?= $room_name ?></span>
                </div>
                <h5 class="fw-bold mb-1"><?= $title ?></h5>
                <p class="text-muted small mb-3"><?= $sub ?></p>
                <hr>
                <?php
                  if ($rules == 1) {
                    ?>
                      <div class="mb-3">
                        <label class="my-input-label mb-2">Тренер</label>
                        <select class="form-select form-in form-inp" id="selectUser" data-name="user" data-type="select" data-required="1">
                          <option value="0">— выбери —</option>
                          <?php
                            $data = ["action" => "list"];
                            $data = array_merge($ses_info, $data);
                            $result = send_request($data, "users");
                            foreach ($result as $usr) {
                              if ($usr["is_coach"]) {
                                ?>
                                  <option value="<?= $usr["id"] ?>" <?php if($usr["id"] == $user){echo "selected";}?>><?= $usr["name"] ?></option>
                                <?php
                              }
                            }
                          ?>
                        </select>
                      </div>
                    <?php
                  }
                ?>
                <div class="mb-3">
                  <label class="my-input-label mb-2">Тренировка</label>
                  <select class="form-select form-in form-inp" id="selectTraining" data-name="training" data-type="select" data-required="1">
                    <option value="0">— выбери —</option>
                    <?php
                      $data = ["action" => "list"];
                      $data = array_merge($ses_info, $data);
                      $result = send_request($data, "trainings");
                      foreach ($result as $tr) {
                        ?>
                          <option value="<?= $tr["id"] ?>"><?= $tr["name"] ?></option>
                        <?php
                      }
                    ?>
                  </select>
                </div>
                <p class="text-center fw-semibold">Подтвердить бронирование?</p>
                <div class="d-flex gap-2 justify-content-around mt-3">
                    <button class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Отмена</button>
                    <button class="btn btn-dark rounded-pill px-4" id="btnConfirmBooking" data-room="<?= $room_id; ?>" data-date="<?= $date; ?>" data-hour="<?= $hour; ?>">Забронировать</button>
                </div>
            </div>
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
