<?php

require_once __DIR__ . '/../config/auth.php';

if (admin_logged_in()) {
    header('Location: ' . page_url('admin/dashboard.php'));
    exit;
}

$error = '';

$loginCsrf = app_csrf_token();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    if (!app_verify_csrf($csrf)) {
        $error = 'Sesi login tidak valid. Silakan muat ulang halaman.';
    } elseif (admin_login_is_throttled()) {
        $error = 'Terlalu banyak percobaan login. Silakan tunggu sekitar 10 menit lalu coba lagi.';
    } else {
        $pdo = db();

        if (!$pdo) {
            $error = 'Login tidak dapat diproses saat ini.';
        } else {
            $stmt = $pdo->prepare(
                'SELECT * FROM admins WHERE username = ? LIMIT 1'
            );
            $stmt->execute([$username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                session_regenerate_id(true);
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['name'];
                $_SESSION['admin_username'] = $admin['username'];
                admin_login_clear_throttle();
                header('Location: ' . page_url('admin/dashboard.php'));
                exit;
            } else {
                admin_login_record_failure();
                usleep(350000);
                $error = 'Username atau password salah.';
            }
        }
    }
}

?>

<!DOCTYPE html>

<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Login Admin - Rosa Indah Printing</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {

            margin: 0;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background:
                linear-gradient(135deg,
                    #fff1f7,
                    #ffffff);

            min-height: 100vh;

            display: flex;

            align-items: center;

            justify-content: center;

        }

        .login-wrapper {

            width: 100%;

            max-width: 430px;

            padding: 20px;

        }

        .login-card {

            background: white;

            border-radius: 24px;

            padding: 35px;

            box-shadow:
                0 20px 60px rgba(214, 74, 138, .15);

            border:
                1px solid #f4dce8;

        }

        .logo {

            display: block;

            width: 150px;

            max-width: 100%;

            margin:
                0 auto 15px;

        }

        .brand {

            text-align: center;

            font-size: 22px;

            font-weight: 800;

            color: #18243a;

            margin-bottom: 5px;

        }

        .brand span {

            color: #d64a8a;

        }

        .subtitle {

            text-align: center;

            color: #888;

            font-size: 14px;

            margin-bottom: 30px;

        }

        h1 {

            text-align: center;

            color: #18243a;

            font-size: 25px;

            margin-bottom: 25px;

        }

        .field {

            margin-bottom: 18px;

        }

        .field label {

            display: block;

            margin-bottom: 7px;

            font-size: 14px;

            font-weight: 700;

            color: #444;

        }

        .field input {

            width: 100%;

            padding: 13px 15px;

            border:
                1px solid #e8dbe2;

            border-radius: 12px;

            outline: none;

            font-size: 15px;

        }

        .field input:focus {

            border-color: #d64a8a;

            box-shadow:
                0 0 0 3px rgba(214, 74, 138, .08);

        }

        .btn {

            width: 100%;

            border: none;

            padding: 14px;

            border-radius: 12px;

            background: #d64a8a;

            color: white;

            font-size: 15px;

            font-weight: 800;

            cursor: pointer;

        }

        .btn:hover {

            background: #c83d7c;

        }

        .error {

            background: #fff0f0;

            border:
                1px solid #ffd4d4;

            color: #a33b3b;

            padding: 12px;

            border-radius: 10px;

            margin-bottom: 18px;

            font-size: 14px;

        }

        .info {

            text-align: center;

            margin-top: 20px;

            color: #999;

            font-size: 12px;

        }
    </style>

</head>

<body>

    <div class="login-wrapper">

        <div class="login-card">

            <img
                class="logo"
                src="<?php
                        echo page_url(
                            'assets/images/logo/logori.png'
                        );
                        ?>"
                alt="Rosa Indah Printing">

            <div class="brand">

                ROSA INDAH
                <span>PRINTING</span>

            </div>

            <div class="subtitle">

                Panel Administrasi

            </div>

            <h1>

                Login Admin

            </h1>

            <?php if ($error): ?>

                <div class="error">

                    <?php
                    echo htmlspecialchars($error);
                    ?>

                </div>

            <?php endif; ?>

            <form method="POST" autocomplete="on">

                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($loginCsrf, ENT_QUOTES, 'UTF-8'); ?>">

                <div class="field">

                    <label>
                        Username
                    </label>

                    <input
                        type="text"
                        name="username"
                        autocomplete="username"
                        required>

                </div>

                <div class="field">

                    <label>
                        Password
                    </label>

                    <input
                        type="password"
                        name="password"
                        autocomplete="current-password"
                        required>

                </div>

                <button
                    type="submit"
                    class="btn">

                    Masuk ke Dashboard

                </button>

            </form>



        </div>

    </div>

</body>

</html>