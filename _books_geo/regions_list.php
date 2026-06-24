<?php
    require_once('../app/includes/session_guard.php');
    $result = fncRequireSession();

    $ses_info = [
        '_onlis_id' => $_COOKIE['_onlis_id'],
        'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
    ];

    $country = $_POST['country'];
    $data    = array_merge($ses_info, ['action' => 'regions_list', 'country' => $country]);
    $result  = send_request($data, 'geo');
    if (!is_array($result) || isset($result['sccss'])) {
        $result = [];
    }
    ?>
    <div class="col-12">
        <?php if (empty($result)): ?>
            <div class="empty-hint">
                <i class="bi bi-inbox empty-hint__icon"></i>
                <div class="empty-hint__text">Записей не найдено</div>
            </div>
        <?php else: ?>
            <table class="table table-sm table-hover mt-2">
                <tbody>
                    <?php foreach ($result as $value): ?>
                        <tr class="itemTr" data-id="<?php echo $value['id']; ?>">
                            <td class="py-2 itemName" data-id="<?php echo $value['id']; ?>">
                                <?php echo htmlspecialchars($value['name']); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
