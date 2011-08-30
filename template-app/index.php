<?php

session_start();

# Connect to the SSO API
$sso_param = array(
	'location' => 'https://login.isvtec.com/api/',
	'login'    => 'api-login',
	'password' => 'api-password',
	'uri'      => '',
);

$cybsso = new SoapClient(null, $sso_param);

$return_url = (($_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://') .
	$_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

try{
	# Check if ticket is defined and is still valid
	if(!isset($_SESSION['cybsso_ticket'],
			  $_SESSION['cybsso_ticket_expiration_date'],
			  $_SESSION['cybsso_user']['email']) or
	   $_SESSION['cybsso_ticket_expiration_date'] <= time()) {

		# Redirect to the auth page if ticket is invalid and no information is
		# given
		if(!isset($_GET['cybsso_ticket'], $_GET['cybsso_email']))
			throw new SoapFault('Invalid SSO ticket');

		# If the user has just logged in, then we set the session and redirect
		# to ourself
		$expiration = $cybsso->TicketCheck($_GET['cybsso_ticket'],
										   $_GET['cybsso_email']);

		$cybsso_user = $cybsso->UserGetInfo($_GET['cybsso_email']);

		$_SESSION = array(
			'cybsso_ticket'                 => $_GET['cybsso_ticket'],
			'cybsso_ticket_expiration_date' => $expiration,
			'cybsso_user'                   => $cybsso_user,
			'cybsso_url'                    => $cybsso->url(),
		);

		header("Location: $return_url");
		exit;
	}

	# Check if the ticket is valid
	$_SESSION['cybsso_ticket_expiration_date'] =
		$cybsso->TicketCheck($_SESSION['cybsso_ticket'],
							 $_SESSION['cybsso_user']['email']);
}
catch(SoapFault $fault) {
	# If the ticket is invalid for some reason, then we destroy the session and
	# redirect to the SSO
	$_SESSION = array();
	if (ini_get('session.use_cookies')) {
		$params = session_get_cookie_params();
		setcookie(session_name(), '', time() - 42000, $params['path'],
				  $params['domain'], $params['secure'], $params['httponly']);
	}
	session_destroy();
	header('Location: ' . $cybsso->url() . "?return_url=$return_url");
	exit;
}

unset($return_url);

echo '<pre>';
print_r($_SESSION['cybsso_user']);
?>
</pre>
<br/>
<a href="<?=$_SESSION['cybsso_url']?>?action=logout">Logout</a> <br/>
<a href="<?=$_SESSION['cybsso_url']?>/self/">Self care</a>
