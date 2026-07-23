<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$user_id = (int)($_POST['user_id'] ?? 0);

$result = send_request(array_merge($ses_info, [
    'action'  => 'user_organizations',
    'user_id' => $user_id,
]), 'users');

if (!is_array($result) || isset($result['sccss'])) {
    $result = [];
}
?>
<div class="row">
    <div class="col-12 mb-2">
        <button type="button" class="btn-action-main" id="btnAddOrg">
            <i class="bi bi-plus-lg"></i>
            <span class="btn-label">Добавить</span>
        </button>
    </div>
    <div class="col-12">
        <?php if (empty($result)): ?>
            <div class="empty-hint">
                <i class="bi bi-building empty-hint__icon"></i>
                <div class="empty-hint__text">Пока нет привязок к организациям</div>
            </div>
        <?php else: ?>
            <table class="table table-sm table-hover mt-2">
                <tbody>
                    <?php foreach ($result as $org): ?>
                      <tr class="listTr orgTr" data-id="<?php echo $org['organization_staff_id']; ?>"
                          data-org-id="<?php echo $org['organization_id']; ?>">
                          <td class="py-2" style="line-height: 1.2em;">
                              <span class="orgName"><?php echo htmlspecialchars($org['display_name']); ?></span>
                              <?php if (!empty($org['title'])): ?>
                                  <div class="text-muted">
                                      <i class="bi bi-person-badge"></i>
                                      <small><?php echo htmlspecialchars($org['title']); ?></small>
                                  </div>
                              <?php endif; ?>
                          </td>
                      </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
<script src="./_books_users/js/users_info_organizations.js?2026072201"></script>
