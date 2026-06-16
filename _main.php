<?php
require_once("./app/includes/request.php");

$cntrl_ok = isset($_SERVER['HTTP_X_CSRF_TOKEN']) && isset($_COOKIE['_onlis_id']);

if ($cntrl_ok) {
    $ses_info = [
        '_onlis_id' => $_COOKIE['_onlis_id'],
        'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
    ];
    $data   = array_merge($ses_info, ['action' => 'in_cntrl']);
    $result = send_request($data, 'main');
}

if ($cntrl_ok && $result && $result['sccss']) {
    ?>
        <div class="row d-flex justify-content-end align-items-start sticky-top">
          <div class="col-12">
            <div class="row p-2">
              <div class="col-12 py-2 d-flex justify-content-between align-items-center shadow-sm" style="border-radius: 5px; background-color: #fff;">
                <div class="my-menu-div-btn">
                  <i class="bi bi-list"></i>
                  <span class="fw-bolder ms-2" style="cursor: pointer;">Меню</span>
                </div>
                <div class="d-flex align-items-center">
                  <span class="fw-bolder me-4" style="font-size: 16px; user-select: none;" id="spnCurrTime"></span>
                  <i class="bi bi-bell text-dark me-2"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="row d-flex justify-content-end align-items-start">
          <div class="col-12">
            <div class="row">
              <div class="col-12">
                <h5 class="my-0 mx-2 fw-bolder" id="sectionHeader">Заголовок</h5>
              </div>
            </div>
            <div class="row mt-2 py-1 px-0" style="min-height: 85vh; overflow: auto;">
              <div class="col-12">
                <div class="row px-2" id="divMainContent"></div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal modal-xl fade" id="mainModal" tabindex="-1" data-bs-backdrop="static" aria-labelledby="mainModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content">
              <div class="offcanvas offcanvas-end" tabindex="-1" id="modalOffcanvas" aria-labelledby="modalOffcanvasLabel" style="position: absolute;">
                <div class="offcanvas-header">
                  <h5 class="offcanvas-title fw-bolder" id="modalOffcanvasLabel"></h5>
                  <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <div class="offcanvas-body pt-0" id="modalOffcanvasBody"></div>
              </div>
              <div class="modal-header">
                <h1 class="modal-title fw-bolder fs-5" id="mainModalLabel" style="line-height: 1.1em;"></h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body" id="mainModalBody"></div>
            </div>
          </div>
        </div>
        <div class="offcanvas offcanvas-start" tabindex="-1" id="myOffcanvas">

            <div class="offcanvas-header">
                <span class="my-menu-logo">
                    <img class="img-fluid" style="max-height: 35px; object-fit: contain;" src="./img/logo.png" alt="">
                </span>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>

            <div class="offcanvas-body d-flex flex-column p-0">

                <!-- ОСНОВНАЯ НАВИГАЦИЯ -->
                <nav class="flex-grow-1 px-3 py-3" style="overflow-y: auto; scrollbar-width: none;">

                    <div class="my-nav-section-label">Главное</div>
                    <ul class="my-nav">
                        <li class="my-nav-item">
                            <a class="my-nav-link">
                                <i class="bi bi-calendar3"></i>
                                <span class="my-nav-link__name link-item"
                                      data-onload="1" data-ln="main_main"
                                      data-pth="_main" data-ttl="Расписание"
                                      data-inside="main">Расписание</span>
                            </a>
                        </li>
                    </ul>

                    <div class="my-nav-section-label mt-3">Справочники</div>
                    <ul class="my-nav">
                        <li class="my-nav-item">
                            <a class="my-nav-link">
                                <i class="bi bi-activity"></i>
                                <span class="my-nav-link__name link-item"
                                      data-ln="main_trainings" data-pth="_books_trainings"
                                      data-ttl="Тренировки" data-inside="trainings">Тренировки</span>
                            </a>
                        </li>
                        <li class="my-nav-item">
                            <a class="my-nav-link">
                                <i class="bi bi-building"></i>
                                <span class="my-nav-link__name link-item"
                                      data-ln="main_rooms" data-pth="_books_rooms"
                                      data-ttl="Залы" data-inside="rooms">Залы</span>
                            </a>
                        </li>
                        <li class="my-nav-item">
                            <a class="my-nav-link">
                                <i class="bi bi-people"></i>
                                <span class="my-nav-link__name link-item"
                                      data-ln="main_users" data-pth="_books_users"
                                      data-ttl="Сотрудники" data-inside="users">Сотрудники</span>
                            </a>
                        </li>
                    </ul>

                </nav>

                <!-- ФУТЕР -->
                <div class="my-menu-footer-wrapper px-3 py-2">
                    <a class="my-nav-link" id="menuButtonProfile">
                        <i class="bi bi-person-circle"></i>
                        <span id="spanUsersName" class="ms-2"><?php echo htmlspecialchars($result['user_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </a>
                    <a class="my-nav-link" id="spnQuit">
                        <i class="bi bi-box-arrow-left"></i>
                        <span class="ms-2">Выход</span>
                    </a>
                </div>

            </div>
        </div>
        <script src="./js/_main.js?2026061602"></script>
      <?php
} else {
    echo "Сессия истекла. Обновите страницу.";
}
