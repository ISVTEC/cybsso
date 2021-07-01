<?php

session_start();

# Connect to the SSO API
define('CYBSSO_URL', 'https://login.isvtec.com/');

$_SESSION = array();
if (ini_get('session.use_cookies')) {
	$params = session_get_cookie_params();
	setcookie(session_name(), '', time() - 42000, $params['path'],
			  $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();

$return_url = (($_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://') .
	$_SERVER['HTTP_HOST'] . '/cybsso/template-app/';

header('Location: '. CYBSSO_URL . "?action=logout&return_url=$return_url");
exit;

?>
