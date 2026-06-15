<?php
  if (isset($_SERVER['HTTP_X_CSRF_TOKEN']) && isset($_COOKIE['_onlis_id'])) {
    require_once("../app/includes/request.php");
    $data = ["action" => "in_cntrl"];
    $ses_info = ["_onlis_id" => $_COOKIE["_onlis_id"], "x_token" => $_SERVER['HTTP_X_CSRF_TOKEN']];
    $data = array_merge($ses_info, $data);
    $result = send_request($data, "main");
    $rules = $result["rules"];
    $user = $result["user"];

    $slot_id  = $_POST["slot"];
    $room_name = $_POST["room_name"];
    $sub      = $_POST["sub"];
    $hour     = $_POST["book_time"];
    $next_hour = $hour + 1;
    $title    = "$hour:00 – $next_hour:00";

    if ($result["sccss"]) {
      ?>
        <div class="row">
          <div class="col-12">
            <div class="p-3">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="badge rounded-pill bg-dark"><?= $room_name ?></span>
                </div>
                <h5 class="fw-bold mb-1"><?= $title ?></h5>
                <p class="text-muted small mb-3"><?= $sub ?></p>
                <hr>

                <!-- Блок оплаты — пока скрыт, в перспективе раскроется -->
                <div id="paymentBlock" class="mb-3" style="display: none;">
                    <label class="my-input-label mb-2">Сумма оплаты</label>
                    <input type="number" class="form-inp form-in" name="amount" placeholder="0.00">
                </div>

                <div class="d-flex gap-2 justify-content-around mt-3">
                    <button class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Закрыть</button>
                    <button class="btn btn-outline-danger rounded-pill px-4" id="btnCancelBooking"
                            data-slot="<?= $slot_id ?>">
                        <i class="bi bi-x-circle me-1"></i>Отменить
                    </button>
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
