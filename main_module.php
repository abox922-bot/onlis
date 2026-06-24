<?php
    require_once('./app/includes/session_guard.php');
    require_once('./modules_map.php');

    $result = fncRequireSession();
    $perms  = $result['rules'] ?? [];

    $module = $_GET['module'] ?? '';

    if (!array_key_exists($module, $modules_map)) {
        die('Раздел не найден.');
    }

    $module_data = $modules_map[$module];
    $sections = array_filter($module_data['sections'], function($section) use ($perms) {
        return !isset($section['slug']) || fncCan($perms, $section['slug']);
    });
    $sections = array_values($sections);

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
<div class="module-shell" id="divModuleShell"
     data-folder="<?= htmlspecialchars($module_data['folder'], ENT_QUOTES, 'UTF-8') ?>"
     data-default="<?= htmlspecialchars($default_section['file'], ENT_QUOTES, 'UTF-8') ?>">

    <?php if (count($sections) > 1): ?>
        <div class="module-tabs" role="tablist">
            <?php foreach ($sections as $section): ?>
                <button class="module-tab  <?php if ($section['key'] === $default_section['key']) echo 'active'; ?>"
                        type="button"
                        role="tab"
                        data-target="<?= htmlspecialchars($section['key'], ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($section['title'], ENT_QUOTES, 'UTF-8') ?>
                </button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="row" id="rowContent">
        <div class="col-12 p-3">
            <div class="spinner-border spinner-border-sm" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>

</div>
