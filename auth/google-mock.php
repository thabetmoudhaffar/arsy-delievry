<?php
require_once __DIR__ . '/../includes/functions.php';

$state = $_GET['state'] ?? '';

// Handle custom account submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $name = trim($_POST['name'] ?? '');
    if (!empty($email) && !empty($name)) {
        $id = md5($email);
        $code = 'mock_' . $id . '|' . $email . '|' . urlencode($name);
        header('Location: google-callback.php?code=' . urlencode($code) . '&state=' . urlencode($state));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in - Google Accounts</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Roboto', arial, sans-serif;
            background-color: #f0f4f9;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            color: #1f1f1f;
        }

        .google-card {
            background: #ffffff;
            width: 100%;
            max-width: 400px;
            padding: 40px;
            border-radius: 8px;
            border: 1px solid #dadce0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            text-align: center;
            box-sizing: border-box;
        }

        .google-logo {
            width: 75px;
            height: 24px;
            margin-bottom: 16px;
        }

        h1 {
            font-size: 24px;
            font-weight: 400;
            line-height: 32px;
            margin: 0 0 8px 0;
        }

        p {
            font-size: 16px;
            color: #444746;
            margin: 0 0 28px 0;
        }

        .accounts-list {
            text-align: left;
            margin-bottom: 24px;
            border: 1px solid #dadce0;
            border-radius: 8px;
            overflow: hidden;
        }

        .account-item {
            display: flex;
            align-items: center;
            padding: 14px 16px;
            cursor: pointer;
            transition: background-color 0.2s;
            text-decoration: none;
            color: inherit;
            border-bottom: 1px solid #f1f3f4;
        }

        .account-item:last-child {
            border-bottom: none;
        }

        .account-item:hover {
            background-color: #f8fafd;
        }

        .avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: #e5e5e5;
            color: #444746;
            font-weight: 500;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 14px;
            flex-shrink: 0;
        }

        .avatar-red { background-color: #fde8e8; color: #e53e3e; }
        .avatar-blue { background-color: #e1effe; color: #1c64ec; }

        .account-details {
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .name {
            font-size: 14px;
            font-weight: 500;
            color: #1f1f1f;
        }

        .email {
            font-size: 12px;
            color: #5f6368;
        }

        .use-another-btn {
            background: none;
            border: none;
            color: #0b57d0;
            font-family: inherit;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 4px;
            margin-top: 10px;
            transition: background-color 0.2s;
        }

        .use-another-btn:hover {
            background-color: #f1f3f4;
        }

        .custom-form {
            text-align: left;
            border-top: 1px solid #dadce0;
            margin-top: 20px;
            padding-top: 20px;
            display: none;
        }

        .custom-form.show {
            display: block;
        }

        .form-title {
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 12px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        label {
            display: block;
            font-size: 12px;
            color: #5f6368;
            margin-bottom: 6px;
        }

        input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #dadce0;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
            outline: none;
        }

        input:focus {
            border-color: #0b57d0;
        }

        .submit-btn {
            background-color: #0b57d0;
            color: white;
            border: none;
            padding: 10px 16px;
            font-size: 14px;
            font-weight: 500;
            border-radius: 4px;
            cursor: pointer;
            float: right;
            transition: background-color 0.2s;
        }

        .submit-btn:hover {
            background-color: #0842a0;
        }

        .footer-note {
            margin-top: 24px;
            font-size: 12px;
            color: #5f6368;
        }
    </style>
</head>
<body>
    <div class="google-card">
        <!-- SVG Google Logo -->
        <svg class="google-logo" viewBox="0 0 74 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M12.24 4.88c-1.84 0-3.53.51-4.99 1.4L4.8 3.83c2.1-1.8 4.86-2.9 8.01-2.9C18.66.93 23.3 5.48 23.3 11c0 5.52-4.64 10.07-10.49 10.07-3.15 0-5.91-1.1-8.01-2.9l2.45-2.45c1.46.89 3.15 1.4 4.99 1.4 4.54 0 7.91-3.64 7.91-8.12s-3.37-8.12-7.91-8.12z" fill="#4285F4"/>
            <path d="M4 11a8 8 0 0 1 8-8c1.84 0 3.53.51 4.99 1.4L19.44 2c-2.1-1.8-4.86-2.9-8.01-2.9C5.1 -.9 0 3.7 0 9.8c0 6.1 5.1 10.7 11.4 10.7 3.15 0 5.91-1.1 8.01-2.9l-2.45-2.45c-1.46.89-3.15 1.4-4.99 1.4a8 8 0 0 1-8-8z" fill="#34A853"/>
            <path d="M12.24 4.88a8 8 0 0 1 8 8c0 4.48-3.37 8.12-7.91 8.12-1.84 0-3.53-.51-4.99-1.4l-2.45 2.45c2.1 1.8 4.86 2.9 8.01 2.9 5.85 0 10.49-4.55 10.49-10.07S18.66.93 12.8.93c-3.15 0-5.91 1.1-8.01 2.9L7.24 6.28c1.46-.89 3.15-1.4 4.99-1.4z" fill="#FBBC05"/>
            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#EA4335"/>
        </svg>

        <h1>Choisissez un compte</h1>
        <p>pour continuer vers Arsy Delivery</p>

        <div class="accounts-list">
            <!-- Mock Account 1 -->
            <a href="google-callback.php?code=<?= urlencode('mock_ahmed|ahmed.ali@gmail.com|Ahmed Ben Ali') ?>&state=<?= urlencode($state) ?>" class="account-item">
                <div class="avatar avatar-blue">A</div>
                <div class="account-details">
                    <span class="name">Ahmed Ben Ali</span>
                    <span class="email">ahmed.ali@gmail.com</span>
                </div>
            </a>
            <!-- Mock Account 2 -->
            <a href="google-callback.php?code=<?= urlencode('mock_yasmine|yasmine.t@gmail.com|Yasmine Trabelsi') ?>&state=<?= urlencode($state) ?>" class="account-item">
                <div class="avatar avatar-red">Y</div>
                <div class="account-details">
                    <span class="name">Yasmine Trabelsi</span>
                    <span class="email">yasmine.t@gmail.com</span>
                </div>
            </a>
        </div>

        <button type="button" class="use-another-btn" onclick="toggleCustomForm()">
            <i class="fas fa-user-plus"></i> Utiliser un autre compte
        </button>

        <form method="POST" class="custom-form" id="custom-account-form">
            <div class="form-title">Nouveau compte Google</div>
            <div class="form-group">
                <label for="name">Nom complet</label>
                <input type="text" id="name" name="name" required placeholder="Ex: Mohamed Salah">
            </div>
            <div class="form-group">
                <label for="email">Adresse email Google</label>
                <input type="email" id="email" name="email" required placeholder="Ex: mohamed.salah@gmail.com">
            </div>
            <button type="submit" class="submit-btn">Suivant</button>
        </form>

        <div class="footer-note">
            Google Demo Sandbox pour Arsy Delivery.
        </div>
    </div>

    <script>
        function toggleCustomForm() {
            document.getElementById('custom-account-form').classList.toggle('show');
        }
    </script>
</body>
</html>
