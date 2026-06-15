<?php
  if (isset($_SERVER['HTTP_X_CSRF_TOKEN']) && isset($_COOKIE['_onlis_id'])) {
    require_once("../app/includes/request.php");
    $data = ["action" => "in_cntrl"];
    $ses_info = ["_onlis_id" => $_COOKIE["_onlis_id"], "x_token" => $_SERVER['HTTP_X_CSRF_TOKEN']];
    $data = array_merge($ses_info, $data);
    $result = send_request($data, "main");
    $today = $result["today"];
    $user = $result["user"];
    $rules = $result["rules"];
    if ($result["sccss"]) {
      $date = $_POST["date"];
      $data = ["action" => "list", "date" => $date];
      $data = array_merge($ses_info, $data);
      $result = send_request($data, "booking");
      $now = new DateTime($today);
      $selected_date = new DateTime($date);
      ?>
        <div class="main-schedule-container w-100">
          <div class="sticky-hall-headers d-flex w-100">
              <div class="col-6 col-md-6 text-center"><h6 class="fw-bold text-uppercase small letter-spacing m-0 py-2">Большой зал</h6></div>
              <div class="col-6 col-md-6 text-center"><h6 class="fw-bold text-uppercase small letter-spacing m-0 py-2">Малый зал</h6></div>
          </div>

            <?php
              for ($h = 7; $h <= 21; $h++) {
                $slot_dt = new DateTime($date);
                $is_past = $slot_dt < $now;

                $found_big = current(array_filter($result, function($item) use ($h) {
                    return $item["hour"] == $h && $item["room"] == 4;
                }));

                $found_small = current(array_filter($result, function($item) use ($h) {
                    return $item["hour"] == $h && $item["room"] == 5;
                }));
                ?>

                <div class="schedule-hour-block">
                  <div class="mobile-time-label d-md-none"><?= $h ?>:00</div>
                  <div class="row g-2 m-0 schedule-slots-row">
                    <div class="col-6 col-md-6 p-1">
                      <?php
                        $class = "";
                        if ($is_past && !$found_big && $rules == 2) {
                            $class = "slot-past";
                        } elseif ($found_big) {
                            $class = ($found_big["user"] == $user) ? "booked-my" : "booked-other";
                        } else {
                            $class = "free-slot";
                        }
                        $text = $found_big ? $found_big["user_name"] : "Свободно";
                      ?>
                      <div class="time-slot <?= $class ?> <?php if($found_big && ($rules == 1 || $found_big["user"] == $user)) {echo "active-slot"; } ?>" data-name="Большой зал" data-id="4" data-hour="<?= $h; ?>" <?php if($found_big){echo 'data-slot="'.$found_big["id"].'"';}?>>
                        <div class="time d-none d-md-block"><?= $h ?>:00</div>
                        <div class="slot-action-text">
                            <?php if ($is_past && !$found_big && $rules == 2): ?>
                                <span class="slot-training text-muted">—</span>
                            <?php elseif ($found_big): ?>
                                <span class="slot-training" <?php if($found_big){echo 'data-slot="'.$found_big["id"].'"';}?>><?= $found_big["training"] ?></span>
                                <span class="slot-trainer" <?php if($found_big){echo 'data-slot="'.$found_big["id"].'"';}?>><?= $found_big["user_name"] ?></span>
                            <?php else: ?>
                                Свободно
                            <?php endif; ?>
                        </div>
                        <?php if (!$found_big && ($rules == 1 || !$is_past)): ?>
                            <button class="btn btn-sm btn-book-action"><i class="bi bi-plus-circle"></i></button>
                        <?php endif; ?>
                      </div>
                    </div>

                    <div class="col-6 col-md-6 p-1">
                      <?php
                        $class = "";
                        if ($is_past && !$found_small && $rules == 2) {
                            $class = "slot-past";
                        } elseif ($found_small) {
                            $class = ($found_small["user"] == $user) ? "booked-my" : "booked-other";
                        } else {
                            $class = "free-slot";
                        }
                        $text = $found_small ? $found_small["user_name"] : "Свободно";
                      ?>
                      <div class="time-slot <?= $class ?> <?php if($found_small && ($rules == 1 || $found_big["user"] == $user)) {echo "active-slot"; } ?>" data-name="Малый зал" data-id="5" data-hour="<?= $h; ?>" <?php if($found_small){echo 'data-slot="'.$found_small["id"].'"';}?>>
                        <div class="time d-none d-md-block"><?= $h ?>:00</div>
                        <div class="slot-action-text">
                          <?php if ($is_past && !$found_small && $rules == 2): ?>
                              <span class="slot-training text-muted">—</span>
                          <?php elseif ($found_small): ?>
                              <span class="slot-training" <?php if($found_small){echo 'data-slot="'.$found_small["id"].'"';}?>><?= $found_small["training"] ?></span>
                              <span class="slot-trainer" <?php if($found_small){echo 'data-slot="'.$found_small["id"].'"';}?>><?= $found_small["user_name"] ?></span>
                          <?php else: ?>
                              Свободно
                          <?php endif; ?>
                        </div>
                        <?php if (!$found_small && ($rules == 1 || !$is_past)): ?>
                          <button class="btn btn-sm btn-book-action"><i class="bi bi-plus-circle"></i></button>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                </div>
                <?php
              }
            ?>

        </div>
        <!--
          <div class="d-flex mt-4 pt-3 border-top gap-4 w-100 flex-wrap">
              <div class="d-flex align-items-center gap-2">
                  <span class="legend-dot" style="background: transparent; border: 1.5px dashed #ccc;"></span>
                  <span class="small text-muted">Свободно</span>
              </div>
              <div class="d-flex align-items-center gap-2">
                  <span class="legend-dot" style="background: #EAF3DE; border: 1.5px solid #C0DD97;"></span>
                  <span class="small text-muted">Моя бронь</span>
              </div>
              <div class="d-flex align-items-center gap-2">
                  <span class="legend-dot" style="background: #EEEDFE; border: 1.5px solid #CECBF6;"></span>
                  <span class="small text-muted">Занято</span>
              </div>
          </div>
        -->
      <?php
    } else {
      echo "Oops... something went wrong...";
    }
  } else {
    echo "Oops... something went wrong...";
  }
?>
