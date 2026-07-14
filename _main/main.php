<?php
require_once('../app/includes/session_guard.php');
$result = fncRequireSession();
$user_name = htmlspecialchars($result['onl_name'], ENT_QUOTES, 'UTF-8');
?>
<div class="container-fluid px-2 py-2">
    <div class="row g-3 pb-4">

      <!-- Приветствие -->
      <div class="col-12">
          <div style="padding: 24px 20px 20px;">
              <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;
                          text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 6px;">
                  Добро пожаловать
              </div>
              <div style="font-size: 1.4rem; font-weight: 700; color: var(--text-main);">
                  <?= $user_name ?>
              </div>
          </div>
      </div>

      <!-- Плитки-статистика (скелетон) -->
      <div class="col-6 col-md-3">
          <div class="skeleton-card">
              <div class="skeleton-icon"><i class="bi bi-calendar3"></i></div>
              <div class="skeleton-line skeleton-line--short"></div>
              <div class="skeleton-value"></div>
          </div>
      </div>
      <div class="col-6 col-md-3">
          <div class="skeleton-card">
              <div class="skeleton-icon"><i class="bi bi-people"></i></div>
              <div class="skeleton-line skeleton-line--short"></div>
              <div class="skeleton-value"></div>
          </div>
      </div>
      <div class="col-6 col-md-3">
          <div class="skeleton-card">
              <div class="skeleton-icon"><i class="bi bi-graph-up"></i></div>
              <div class="skeleton-line skeleton-line--short"></div>
              <div class="skeleton-value"></div>
          </div>
      </div>
      <div class="col-6 col-md-3">
          <div class="skeleton-card">
              <div class="skeleton-icon"><i class="bi bi-box-seam"></i></div>
              <div class="skeleton-line skeleton-line--short"></div>
              <div class="skeleton-value"></div>
          </div>
      </div>

      <!-- Большой блок-заглушка (будущий график) -->
      <div class="col-12 col-md-8">
          <div class="skeleton-card skeleton-card--tall">
              <div class="skeleton-line skeleton-line--medium mb-3"></div>
              <div class="skeleton-chart">
                  <div class="skeleton-bar" style="height: 60%;"></div>
                  <div class="skeleton-bar" style="height: 80%;"></div>
                  <div class="skeleton-bar" style="height: 45%;"></div>
                  <div class="skeleton-bar" style="height: 90%;"></div>
                  <div class="skeleton-bar" style="height: 65%;"></div>
                  <div class="skeleton-bar" style="height: 75%;"></div>
                  <div class="skeleton-bar" style="height: 50%;"></div>
              </div>
          </div>
      </div>

      <!-- Маленький блок-заглушка (будущий список) -->
      <div class="col-12 col-md-4">
          <div class="skeleton-card skeleton-card--tall">
              <div class="skeleton-line skeleton-line--medium mb-3"></div>
              <div class="skeleton-list-item"></div>
              <div class="skeleton-list-item"></div>
              <div class="skeleton-list-item"></div>
              <div class="skeleton-list-item"></div>
              <div class="skeleton-list-item"></div>
          </div>
      </div>

      <!-- Нижняя строка заглушек -->
      <div class="col-12 col-md-6">
          <div class="skeleton-card">
              <div class="skeleton-line skeleton-line--medium mb-2"></div>
              <div class="skeleton-line"></div>
              <div class="skeleton-line skeleton-line--short"></div>
          </div>
      </div>
      <div class="col-12 col-md-6">
          <div class="skeleton-card">
              <div class="skeleton-line skeleton-line--medium mb-2"></div>
              <div class="skeleton-line"></div>
              <div class="skeleton-line skeleton-line--short"></div>
          </div>
      </div>

    </div>
</div>

<style>
.skeleton-card {
    background: #fff;
    border-radius: 14px;
    padding: 20px;
    border: 1px solid var(--border-color);
}
.skeleton-card--tall { min-height: 220px; display: flex; flex-direction: column; }

.skeleton-icon {
    font-size: 1.4rem;
    color: #d8d8d8;
    margin-bottom: 12px;
}

.skeleton-line {
    height: 10px;
    background: #efefef;
    border-radius: 6px;
    margin-bottom: 8px;
    width: 100%;
}
.skeleton-line--short { width: 55%; }
.skeleton-line--medium { width: 75%; }

.skeleton-value {
    height: 28px;
    background: #efefef;
    border-radius: 8px;
    width: 50%;
    margin-top: 10px;
}

.skeleton-chart {
    display: flex;
    align-items: flex-end;
    gap: 8px;
    flex: 1;
    padding-top: 10px;
}
.skeleton-bar {
    flex: 1;
    background: #efefef;
    border-radius: 6px 6px 0 0;
}

.skeleton-list-item {
    height: 28px;
    background: #f5f5f5;
    border-radius: 8px;
    margin-bottom: 10px;
}
</style>
