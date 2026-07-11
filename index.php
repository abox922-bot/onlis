<?php
require_once("./app/includes/request.php");

if (isset($_COOKIE["_onlis_id"])) {
    $onlis_id = $_COOKIE["_onlis_id"];
} else {
    $onlis_id = bin2hex(random_bytes(20));
}

setcookie('_onlis_id', $onlis_id, [
    'expires'  => time() + 86400,
    'path'     => '/',
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Lax',
]);

$data   = ["action" => "info", "_onlis_id" => $onlis_id];
$result = send_request($data, "main");
$cntrl  = ($result && !empty($result['cntrl'])) ? $result['cntrl'] : null;
?>
<!DOCTYPE html>
<html lang="ru">
    <head>
        <title>ONL.IS</title>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <?php if ($cntrl): ?>
            <meta name="csrf-token" content="<?php echo htmlspecialchars($cntrl, ENT_QUOTES, 'UTF-8'); ?>">
        <?php endif; ?>
        <link rel="icon" href="./img/favicon.ico" type="image/x-icon">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/css/tom-select.bootstrap5.min.css">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="./css/style.css?2026071002">
    </head>
    <body>
        <div class="section d-flex flex-column justify-content-start container auth-container" id="mainContainer">
            <div class="row h-100">
                <div class="col-12 d-flex justify-content-center align-items-center" style="min-height: 100vh;">
                    <div class="spinner-border spinner-border-sm text-dark" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js" integrity="sha384-G/EV+4j2dNv+tEPo3++6LCgdCROaejBqfUeNjuKAiuXbjrxilcCdDz6ZAVfHWe1Y" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/js/tom-select.complete.min.js"></script>
        <script src="./min_js/jquery.mask.min.js"></script>
        <script src="./js/index.js?2026070100"></script>
    </body>
</html>
