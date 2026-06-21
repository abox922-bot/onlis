<?php
    require_once('../app/includes/session_guard.php');
    $result = fncRequireSession();

    $ses_info = [
        '_onlis_id' => $_COOKIE['_onlis_id'],
        'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
    ];

    $data   = array_merge($ses_info, ['action' => 'countries_regs_list']);
    $result = send_request($data, 'geo');
    ?>
    <div class="section-toolbar">
        <select class="form-in toolbar-filter" id="slctCountry">
            <option value="0">Выберите страну</option>
            <?php foreach ($result['countries'] as $value): ?>
                <option value="<?php echo $value['id']; ?>"><?php echo htmlspecialchars($value['name']); ?></option>
            <?php endforeach; ?>
        </select>
        <select class="form-in toolbar-filter" id="slctRegion" disabled>
            <option value="0">Выберите регион</option>
            <?php foreach ($result['regions'] as $value): ?>
                <option value="<?php echo $value['id']; ?>" data-country="<?php echo $value['country']; ?>"><?php echo htmlspecialchars($value['name']); ?></option>
            <?php endforeach; ?>
        </select>
        <div class="toolbar-search">
            <i class="bi bi-search toolbar-search__icon"></i>
            <input type="text" class="form-in" id="inpSearchVal" placeholder="Поиск...">
        </div>
        <button type="button" class="btn-action-main toolbar-add" id="btnFastNew" disabled>
            <i class="bi bi-plus-lg"></i>
            <span class="btn-label">Добавить</span>
        </button>
    </div>

    <div class="d-none" id="divChptContent"></div>

    <div class="empty-hint" id="divEmptyHint">
        <i class="bi bi-building empty-hint__icon"></i>
        <div class="empty-hint__text">Выберите страну и регион для просмотра городов</div>
    </div>

    <script src="./_books_geo/js/cities.js?2026062101"></script>
