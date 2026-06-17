<?php
  require_once('./app/includes/session_guard.php');
  require_once('./modules_map.php');

  $result = fncRequireSession();

  $module = $_GET['module'] ?? '';

  if (!array_key_exists($module, $modules_map)) {
      die('Раздел не найден.');
  }

  $module_data = $modules_map[$module];
  $sections    = $module_data['sections'];

  if (empty($sections)) {
      die('Для этого модуля не настроены разделы.');
  }

  $default_section = $sections[0];
  foreach ($sections as $section) {
      if (!empty($section['default'])) {
          $default_section = $section;
          break;
      }
  }
  ?>

  <div class="col-12 py-2" id="divModuleShell"
       data-folder="<?= htmlspecialchars($module_data['folder'], ENT_QUOTES, 'UTF-8') ?>"
       data-default="<?= htmlspecialchars($default_section['file'], ENT_QUOTES, 'UTF-8') ?>"
       style="border-radius: 5px; background-color: #fff;">
      <?php if (count($sections) > 1): ?>
          <div class="dropdown div-chpt-control">
              <button class="btn-action dropdown-toggle mt-2" id="btnSlct" type="button"
                      data-target="<?= htmlspecialchars($default_section['key'], ENT_QUOTES, 'UTF-8') ?>"
                      data-bs-toggle="dropdown" aria-expanded="false">
                  <?= htmlspecialchars($default_section['title'], ENT_QUOTES, 'UTF-8') ?>
              </button>
              <ul class="dropdown-menu custom-dropdown-menu">
                  <?php foreach ($sections as $section): ?>
                      <li class="list-group-item liSlct" data-target="<?= htmlspecialchars($section['key'], ENT_QUOTES, 'UTF-8') ?>" style="cursor: pointer;">
                          <div class="dropdown-item">
                              <span class="liSlctItem"><?= htmlspecialchars($section['title'], ENT_QUOTES, 'UTF-8') ?></span>
                          </div>
                      </li>
                  <?php endforeach; ?>
              </ul>
          </div>
      <?php endif; ?>
      <div class="row mt-2" id="rowContent">
          <div class="col-12">
              <div class="spinner-border spinner-border-sm" role="status">
                  <span class="visually-hidden">Loading...</span>
              </div>
          </div>
      </div>
  </div>
