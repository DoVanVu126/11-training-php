<?php
session_start();

require_once 'models/UserModel.php';
$userModel = new UserModel();

// Nếu chưa có CSRF token thì tạo mới
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

if (!empty($_POST['submit'])) {
    // Kiểm tra CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF validation failed.");
    }

    $users = [
        'username' => $_POST['username'],
        'password' => $_POST['password']
    ];
    $user = NULL;

    if ($user = $userModel->auth($users['username'], $users['password'])) {
        $_SESSION['id'] = $user[0]['id'];
        $_SESSION['message'] = 'Login successful';

        $token = bin2hex(random_bytes(16));

        // Kết nối Redis
        $redis = new Redis();
        $redis->connect('redis', 6379);

        // Lưu user vừa login vào Redis
        $redis->set("login:$token", json_encode([
            'id' => $user[0]['id'],
            'name' => $user[0]['name']
        ]));
        $redis->expire("login:$token", 3600); // key sống 1 giờ

        // Lưu token + info vào LocalStorage và redirect
        echo "<script>
            localStorage.setItem('token', '$token');
            localStorage.setItem('user', JSON.stringify({id: ".$user[0]['id'].", name: '".$user[0]['name']."'}));
            window.location.href = 'list_users.php';
        </script>";
        exit;

    } else {
        $_SESSION['message'] = 'Login failed';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>User form</title>
    <?php include 'views/meta.php' ?>
</head>
<body>
<?php include 'views/header.php'?>

<div class="container">
    <div id="loginbox" style="margin-top:50px;" 
         class="mainbox col-md-6 col-md-offset-3 col-sm-8 col-sm-offset-2">
        <div class="panel panel-info">
            <div class="panel-heading">
                <div class="panel-title">Login</div>
                <div style="float:right; font-size: 80%; position: relative; top:-10px">
                    <a href="#">Forgot password?</a>
                </div>
            </div>

            <div style="padding-top:30px" class="panel-body">
                <form method="post" class="form-horizontal" role="form">
                    <!-- CSRF token -->
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                    <div class="margin-bottom-25 input-group">
                        <span class="input-group-addon">
                            <i class="glyphicon glyphicon-user"></i>
                        </span>
                        <input id="login-username" type="text" class="form-control" 
                               name="username" placeholder="username or email" required>
                    </div>

                    <div class="margin-bottom-25 input-group">
                        <span class="input-group-addon">
                            <i class="glyphicon glyphicon-lock"></i>
                        </span>
                        <input id="login-password" type="password" class="form-control" 
                               name="password" placeholder="password" required>
                    </div>

                    <div class="margin-bottom-25">
                        <input type="checkbox" tabindex="3" name="remember" id="remember">
                        <label for="remember"> Remember Me</label>
                    </div>

                    <div class="margin-bottom-25 input-group">
                        <div class="col-sm-12 controls">
                            <button type="submit" name="submit" value="submit" 
                                    class="btn btn-primary">Submit</button>
                            <a id="btn-fblogin" href="#" class="btn btn-primary">Login with Facebook</a>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-md-12 control">
                            Don't have an account!
                            <a href="form_user.php">Sign Up Here</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>