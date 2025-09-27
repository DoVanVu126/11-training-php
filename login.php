<?php
// --- Security headers (đặt trước output) ---
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("Referrer-Policy: no-referrer-when-downgrade");
header("Permissions-Policy: geolocation=()");
header("Content-Security-Policy: default-src 'self'; script-src 'self'; object-src 'none'; frame-ancestors 'none';");

// cấu hình cookie session an toàn (trước session_start)
$secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

// loại bỏ port nếu có
$host = $_SERVER['HTTP_HOST'] ?? '';
$hostNoPort = preg_replace('/:\d+$/', '', $host);

// session cookie params
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    //'domain' => $hostNoPort,
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

// rate-limiting basic (session-based)
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['first_attempt_time'] = time();
}

$LOCK_TIME = 300; // seconds lock after too many attempts
$MAX_ATTEMPTS = 5;
if ($_SESSION['login_attempts'] >= $MAX_ATTEMPTS && (time() - $_SESSION['first_attempt_time']) < $LOCK_TIME) {
    http_response_code(429);
    die("Too many failed login attempts. Try again later.");
}
if ((time() - $_SESSION['first_attempt_time']) >= $LOCK_TIME) {
    // reset window
    $_SESSION['login_attempts'] = 0;
    $_SESSION['first_attempt_time'] = time();
}

require_once 'models/UserModel.php';
$userModel = new UserModel();

// tạo CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$messages = [];
if (!empty($_POST['submit'])) {
    // validate CSRF
    if (
        !isset($_POST['csrf_token']) || !is_string($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        http_response_code(403);
        die("CSRF validation failed.");
    }

    // Basic input validation & trim
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $remember = isset($_POST['remember']);

    if ($username === '' || $password === '') {
        $messages[] = "Please enter username and password.";
    } else {
        // Auth via UserModel (uses prepared statements + password_verify)
        $user = $userModel->auth($username, $password);
        if ($user) {
            // success: reset attempts
            $_SESSION['login_attempts'] = 0;
            $_SESSION['first_attempt_time'] = time();

            // session fixation mitigation
            session_regenerate_id(true);
            $_SESSION['id'] = $user[0]['id'];
            $_SESSION['message'] = 'Login successful';

            // create auth token (secure random) and store in Redis
            $token = bin2hex(random_bytes(32));
            try {
                $redis = new Redis();
                $redis->connect('127.0.0.1', 6379);
                // store token -> user mapping, plus expiration
                $redis->setex("login:$token", $remember ? 60 * 60 * 24 * 30 : 3600, json_encode([
                    'id' => $user[0]['id'],
                    'name' => $user[0]['name']
                ]));
            } catch (Exception $e) {
                // fallback: you may log this. For now proceed but shorter expiry
            }

            // set cookie HttpOnly; if remember -> long expiry
            $cookieExpire = $remember ? time() + 60 * 60 * 24 * 30 : 0; // 30 days or session cookie
            setcookie('auth_token', $token, [
                'expires' => $cookieExpire,
                'path' => '/',
                //'domain' => $hostNoPort,
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);

            // rotate CSRF token after login
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            header('Location: list_users.php');
            exit;
        } else {
            // failed login -> increment attempts
            $_SESSION['login_attempts'] += 1;
            if ($_SESSION['login_attempts'] === 1) {
                $_SESSION['first_attempt_time'] = time();
            }
            $messages[] = "Login failed. Check username and password.";
        }
    }
}

// helper to output escaped
function e($str)
{
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Login</title>
    <?php include 'views/meta.php' ?>
</head>

<body>
    <?php include 'views/header.php' ?>

    <div class="container">
        <div id="loginbox" style="margin-top:50px;" class="mainbox col-md-6 col-md-offset-3 col-sm-8 col-sm-offset-2">
            <div class="panel panel-info">
                <div class="panel-heading">
                    <div class="panel-title">Login</div>
                </div>

                <div style="padding-top:30px" class="panel-body">
                    <?php if (!empty($messages)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php foreach ($messages as $m): ?>
                                <div><?php echo e($m); ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" class="form-horizontal" role="form" autocomplete="off" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">

                        <div class="margin-bottom-25 input-group">
                            <span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>
                            <input id="login-username" type="text" class="form-control" name="username" placeholder="username or email" required maxlength="150" autocomplete="username">
                        </div>

                        <div class="margin-bottom-25 input-group">
                            <span class="input-group-addon"><i class="glyphicon glyphicon-lock"></i></span>
                            <input id="login-password" type="password" class="form-control" name="password" placeholder="password" required autocomplete="current-password">
                        </div>

                        <div class="margin-bottom-25">
                            <input type="checkbox" tabindex="3" name="remember" id="remember">
                            <label for="remember"> Remember Me</label>
                        </div>

                        <div class="margin-bottom-25 input-group">
                            <div class="col-sm-12 controls">
                                <button type="submit" name="submit" value="submit" class="btn btn-primary">Submit</button>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>

</body>

</html>