<?php
  require_once('./app/includes/session_guard.php');
  $result = fncRequireSession();

  $perms = $result['rules'] ?? [];

  $menu = require('./menu_map.php');

  $ses_info = [
      '_onlis_id' => $_COOKIE['_onlis_id'],
      'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
  ];
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
  <div class="row d-flex justify-content-end align-items-start" id="divScrollArea">
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
          <ul class="my-nav">
              <?php foreach ($menu as $group_key => $group): ?>

                  <?php if (!empty($group['single'])): ?>
                      <?php $show = !isset($group['slug']) || fncCan($perms, $group['slug']); ?>
                      <?php if ($show): ?>
                          <li class="my-nav-item">
                              <a class="my-nav-link head-link">
                                  <i class="bi <?php echo $group['icon']; ?>"></i>
                                  <span class="my-nav-link__name link-item"
                                        data-module="<?php echo $group['module']; ?>"
                                        data-ttl="<?php echo $group['title']; ?>">
                                      <?php echo $group['title']; ?>
                                  </span>
                              </a>
                          </li>
                      <?php endif; ?>

                  <?php else: ?>
                      <?php $visible = array_filter($group['items'], fn($item) => !isset($item['slug']) || fncCan($perms, $item['slug'])); ?>
                      <?php if ($visible): ?>
                          <li class="my-nav-item">
                              <a class="my-nav-link head-item head-link" data-target="<?php echo $group_key; ?>" style="cursor: pointer;">
                                  <i class="bi <?php echo $group['icon']; ?>"></i>
                                  <span class="my-nav-link__name"><?php echo $group['title']; ?></span>
                                  <i class="bi bi-chevron-down ms-auto small"></i>
                              </a>
                          </li>
                          <?php foreach ($visible as $item): ?>
                              <li class="my-nav-item_second_level d-none" data-target="<?php echo $group_key; ?>">
                                  <a class="my-nav-link">
                                      <i class="bi <?php echo $item['icon']; ?>"></i>
                                      <span class="my-nav-link__name link-item"
                                            data-module="<?php echo $item['module']; ?>"
                                            data-ttl="<?php echo $item['title']; ?>">
                                          <?php echo $item['title']; ?>
                                      </span>
                                  </a>
                              </li>
                          <?php endforeach; ?>
                      <?php endif; ?>

                  <?php endif; ?>

              <?php endforeach; ?>
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
  <script src="./js/_main.js?2026070401"></script>
