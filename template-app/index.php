<?php

session_start();

# Check if ticket is defined and is still valid
if(!isset($_SESSION['ticket'],
		  $_SESSION['ticket_expiration_date'],
		  $_SESSION['cybsso_user']['email']) or
   $_SESSION['ticket_expiration_date'] <= time()) {

	try{
		$return_url = (($_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://') .
			$_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		# Connect to the SSO API
		$sso_param = array(
			'location' => 'https://login.isvtec.com/api/',
			'login'    => 'api-login',
			'password' => 'api-password',
			'uri'      => '',
			);

		$cybsso = new SoapClient(null, $sso_param);

		# Redirect to the auth page if ticket is invalid and no information is
		# given
		if(!isset($_GET['cybsso_ticket'], $_GET['cybsso_email'])) {
			header('Location: ' . $cybsso->url() . "?return_url=$return_url");
			exit;
		}

		# If the user has just logged in, then we set the session and redirect
		# to ourself
		$expiration = $cybsso->TicketCheck($_GET['cybsso_ticket'],
										   $_GET['cybsso_email']);

		$cybsso_user = $cybsso->UserGetInfo($_GET['cybsso_email']);

		$_SESSION = array(
			'ticket'                 => $_GET['cybsso_ticket'],
			'ticket_expiration_date' => $expiration,
			'cybsso_user'            => $cybsso_user,
			'cybsso_url'             => $cybsso->url(),
		);

		header("Location: $return_url");
		exit;
	}
	catch(SoapFault $fault) {
		# Ticket is invalid, go back to the SSO
		header('Location: ' . $cybsso->url() . "?return_url=$return_url");
		exit;
	}

	unset($return_url);
}

echo '<pre>';
print_r($_SESSION['cybsso_user']);
?>
</pre>
<br/>
<a href="<?=$_SESSION['cybsso_url']?>?action=logout">Logout</a> <br/>
<a href="<?=$_SESSION['cybsso_url']?>/self/">Self care</a>
