<?php
/**
 * Swap Design - Admin Login Page
 *
 * Handles admin authentication with CSRF protection and rate limiting.
 * Redirects to dashboard if already logged in.
 *
 * @package SwapDesign
 */

require_once __DIR__ . '/includes/init.php';

/* If already logged in, redirect to dashboard */
if (Auth::check()) {
    $redirectTo = Session::get('auth_redirect', '/admin/index.php');
    Session::remove('auth_redirect');
    redirect($redirectTo);
}

$pageTitle = 'Login';
$csrfField = csrfToken();
$error     = '';
$email     = $_POST['email'] ?? Session::getFlash('login_email', '');

/* Handle login submission */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    /* CSRF check */
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security check failed. Please refresh and try again.';
    } else {
        $inputEmail    = sanitizeString($_POST['email'] ?? '');
        $inputPassword = $_POST['password'] ?? '';
        $remember      = !empty($_POST['remember']);

        if (empty($inputEmail) || empty($inputPassword)) {
            $error = 'Please enter both email and password.';
        } elseif (!validateEmail($inputEmail)) {
            $error = 'Please enter a valid email address.';
        } else {
            $result = Auth::login($inputEmail, $inputPassword, $remember);

            if ($result['success']) {
                Session::flash('success', 'Welcome back, ' . (Auth::user()['name'] ?? 'Admin') . '!');
                $redirectTo = Session::get('auth_redirect', '/admin/index.php');
                Session::remove('auth_redirect');
                redirect($redirectTo);
            } else {
                $error  = $result['message'];
                $email  = $inputEmail;
                Session::flash('login_email', $inputEmail);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Login | Admin — <?php echo esc($site->brand->name); ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="/admin/assets/css/admin.css">
    <link rel="stylesheet" href="/admin/assets/css/admin-premium.css">
    <!-- Google Fonts (Montserrat + Plus Jakarta Sans — design system typography) -->
    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet"
        media="print"
        onload="this.media='all'"
    >
    <noscript>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    </noscript>
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/images/favicon/favicon-32.png">
</head>
<body class="admin-login-page">

    <a href="#login-form" class="admin-skip-link">Skip to login form</a>

    <div class="admin-login">
        <div class="admin-login__card">
            <!-- Logo -->
            <div class="admin-login__brand">
                <span class="admin-login__logo">S</span>
                <h1 class="admin-login__title">Swap Design</h1>
                <p class="admin-login__subtitle">Admin Panel</p>
            </div>

            <?php if ($error): ?>
            <div class="admin-flash admin-flash--error" role="alert">
                <?php echo esc($error); ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="/admin/login.php" class="admin-login__form" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo esc($csrfField); ?>">

                <div class="admin-form-group">
                    <label for="login-email" class="admin-form-label">Email Address</label>
                    <input
                        type="email"
                        id="login-email"
                        name="email"
                        class="admin-form-input"
                        value="<?php echo esc($email); ?>"
                        placeholder="admin@swapdesign.com"
                        required
                        autocomplete="email"
                        autofocus
                    >
                </div>

                <div class="admin-form-group">
                    <label for="login-password" class="admin-form-label">Password</label>
                    <input
                        type="password"
                        id="login-password"
                        name="password"
                        class="admin-form-input"
                        placeholder="Enter password"
                        required
                        autocomplete="current-password"
                    >
                </div>

                <div class="admin-form-group admin-form-group--row">
                    <label class="admin-form-checkbox">
                        <input type="checkbox" name="remember" value="1">
                        <span>Remember me</span>
                    </label>
                </div>

                <button type="submit" class="admin-btn admin-btn--primary admin-btn--block">
                    Sign In
                </button>
            </form>
        </div>

        <p class="admin-login__footer">
            &copy; <?php echo date('Y'); ?> <?php echo esc($site->brand->name); ?>. All rights reserved.
        </p>
    </div>

</body>
</html>
