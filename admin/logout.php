<?php
/**
 * Swap Design - Admin Logout
 *
 * Destroys the user session, clears remember tokens,
 * and redirects to the login page.
 *
 * @package SwapDesign
 */

require_once __DIR__ . '/includes/init.php';

Auth::logout();

Session::destroy();

redirect('/admin/login.php');
