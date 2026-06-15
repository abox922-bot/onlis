<?php
  if (isset($_SERVER['HTTP_X_CSRF_TOKEN']) && isset($_COOKIE['_onlis_id'])) {
    require_once("../app/includes/request.php");
    $data = ["action" => "in_cntrl"];
    $ses_info = ["_onlis_id" => $_COOKIE["_onlis_id"], "x_token" => $_SERVER['HTTP_X_CSRF_TOKEN']];
    $data = array_merge($ses_info, $data);
    $result = send_request($data, "main");

    $today_day = date("j", strtotime($result["today"]));
    $today_month = date("n", strtotime($result["today"]));

    $today = $_POST["trg_date"] ?? $result["today"];
    $dow = date("N", strtotime($today));
    $curr_day = date_create($today);

    $dif = $dow - 1;
    $dif = $dif.' days';
    $interval = date_interval_create_from_date_string($dif);
    date_sub($curr_day, $interval);

    $monday_mnth = date_format($curr_day, "n");

    $sunday_date = date_create(date_format($curr_day, "Y-m-d"));
    $interval = date_interval_create_from_date_string("6 days");
    date_add($sunday_date, $interval);
    $sunday_mnth = date_format($sunday_date, "n");
    $sunday_year = date_format($sunday_date, "Y");


    $prv_monday = date_create(date_format($curr_day, "Y-m-d"));
    $interval = date_interval_create_from_date_string("7 days");
    date_sub($prv_monday, $interval);

    $next_monday = date_create(date_format($curr_day, "Y-m-d"));
    $interval = date_interval_create_from_date_string("7 days");
    date_add($next_monday, $interval);

    if ($result["sccss"]) {
      $months = ["", "Январь", "Февраль", "Март", "Апрель", "Май", "Июнь", "Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь"];
      $dows = ["", "ПН", "ВТ", "СР", "ЧТ", "ПТ", "СБ", "ВС"];
      $months_another = ["", "января", "февраля", "марта", "апреля", "мая", "июня", "июля", "августа", "сентября", "октября", "ноября", "декабря"];
      $dows_another = ["", "Понедельник", "Вторник", "Среда", "Четверг", "Пятница", "Суббота", "Воскресенье"];
      ?>
        <div class="row mb-2">
            <div class="col-12 text-center">
                <span class="text-uppercase fw-bold letter-spacing-2 small text-muted" id="currentMonthYear">
                  <?php
                    if ($monday_mnth == $sunday_mnth) {
                      echo $months[$monday_mnth]."&nbsp;".$sunday_year;
                    } else {
                      echo $months[$monday_mnth]."&nbsp;-&nbsp;".$months[$sunday_mnth]."&nbsp;".$sunday_year;
                    }
                  ?>
                </span>
            </div>
        </div>
        <div class="d-flex align-items-center mb-4 bg-light p-2 rounded-4">
            <button class="btn btn-sm border-0 px-2 btnDatesChng" data-date="<?= date_format($prv_monday, "Y-m-d"); ?>"><i class="bi bi-chevron-left"></i></button>

            <div class="date-strip-container d-flex flex-grow-1 justify-content-between overflow-auto mx-2">
                <?php
                  $interval = date_interval_create_from_date_string('1 days');
                  for ($i=1; $i <=7 ; $i++) {
                    $curr_mnth = date_format($curr_day, "n");
                    $curr_monday = date_format($curr_day, "j");
                    ?>
                      <div class="date-item <?php if($curr_monday == $today_day && $today_month == $curr_mnth){echo "active";} ?>" data-dow="<?= $dows_another[$i]?>" data-day="<?= $curr_monday; ?>" data-month="<?= $months_another[$curr_mnth]?>" data-date="<?= date_format($curr_day, "Y-m-d"); ?>"><span><?= $dows[$i]; ?></span><strong><?= $curr_monday; ?></strong></div>
                    <?php
                    date_add($curr_day, $interval);
                  }
                ?>
            </div>

            <button class="btn btn-sm border-0 px-2 btnDatesChng" data-date="<?= date_format($next_monday, "Y-m-d"); ?>"><i class="bi bi-chevron-right"></i></button>
        </div>
      <?php
    } else {
      echo "Oops... something went wrong...";
    }
  } else {
    echo "Oops... something went wrong...";
  }
?>
