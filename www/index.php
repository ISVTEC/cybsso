<?php
//
// Copyright (C) 2011 Cyril Bouthors <cyril@bouthors.org>
//
// This program is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free Software
// Foundation, either version 3 of the License, or (at your option) any later
// version.
//
// This program is distributed in the hope that it will be useful, but WITHOUT
// ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
// FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
// details.
//
// You should have received a copy of the GNU General Public License along with
// this program. If not, see <http://www.gnu.org/licenses/>.
//

require('/etc/cybsso/config.php');
require('cybsso/CybSSOPrivate.php');

# Check return_url
$return_url = 'http://';
if($_SERVER['SERVER_PORT'] == 443)
	$return_url = 'https://';

# Default return URL goes to the customer self-care
$return_url .= $_SERVER['HTTP_HOST'] . '/self/';

if(isset($_GET['return_url']))
	$return_url = $_GET['return_url'];
elseif(isset($_POST['return_url']))
	$return_url = $_POST['return_url'];

if(!preg_match('/^https?:\/\/.*$/', $return_url)) {
	echo "Invalid return_url format";
	exit;
}
   
$url_separator='?';
if(strpos($return_url, '?') == true)
	$url_separator='&';

session_start();

# Process action
$action = 'none';
if(isset($_GET['action']))
	$action = $_GET['action'];

if(isset($_POST['action']))
	$action = $_POST['action'];

# Default focus
$focus = 'log-in';
$message = "";

try{
	$cybsso = new CybSSOPrivate;
    $forceDisplay = 'null';

	switch($action) {

		case 'logout':
			# Delete SSO ticket
			if(isset($_SESSION['user']['email']))
				$cybsso->TicketDelete($_SESSION['user']['email']);

			# Delete cookie
			$_SESSION = array();
			if (ini_get('session.use_cookies')) {
				$params = session_get_cookie_params();
				setcookie(session_name(), '', time() - 42000, $params['path'],
						  $params['domain'], $params['secure'],
						  $params['httponly']);
			}

			# Destroy session
			session_destroy();

			# Redirect and show a message
			header("Location: ./?message=logout&return_url=$return_url");
			exit;

		case 'Log in':
			$focus = 'log-in';

			$email = '';
			$password = '';

			# Only accept insecure email and password from GET arguments for
			# demo purpose
			if(isset($_GET['email']) and $_GET['email'] == 'demo@isvtec.com') {
				$email = $_GET['email'];
				if(isset($_GET['password']))
					$password = $_GET['password'];
			}
			else {
				if(isset($_POST['email']))
					$email = $_POST['email'];

				if(isset($_POST['password']))
					$password = $_POST['password'];
			}

			$ticket = $cybsso->TicketCreate($email, $password);
			break;

		case 'Create account':
			$focus = 'create-account';
			$ticket = $cybsso->UserCreate($_POST);
			$email = $_POST['email'];
			break;

		case 'Password recovery':
			$focus = 'password-recovery';
			$cybsso->PasswordRecovery($_POST['email'], $return_url);
			$_GET['message'] = 'password sent';
			$focus = 'none';
			break;

		case 'Password recovery2':
			$focus = 'none';
			$cybsso->PasswordRecoveryCheckTicket($_GET['email'], $_GET['ticket']);
			$focus = 'new-password';
			break;

		case 'Password recovery3':
			$focus = 'new-password';
			$cybsso->PasswordRecoveryCheckTicket($_POST['email'],
												 $_POST['ticket']);
			$cybsso->PasswordReset($_POST['email'],
								   $_POST['password'],
								   $_POST['password2']);

			$ticket = $cybsso->TicketCreate($_POST['email'], $_POST['password']);
			$email = $_POST['email'];
			break;

		default:
			# Check ticket if no particular action was requested and a valid
			# session has been found
			if(!isset($_SESSION['ticket']) or
			   !isset($_SESSION['user']['email']))
				break;

			$cybsso->TicketCheck($_SESSION['ticket'],
								 $_SESSION['user']['email']);

			$ticket = array('name' => $_SESSION['ticket']);
			$email = $_SESSION['user']['email'];
	}

	if(isset($ticket)) {

		$_SESSION = array(
			'ticket' => $ticket['name'],
			'user'   => $cybsso->UserGetInfo($email),
		);

		header('Location: '. $return_url . $url_separator .
			   "cybsso_ticket=$ticket[name]&cybsso_email=$email");
		exit;
	}
}
catch(SoapFault $fault) {
	$message =  '<font color="red">'.$fault->getMessage() . '</font>';

	# Delete cookie
	$_SESSION = array();
	if (ini_get('session.use_cookies')) {
		$params = session_get_cookie_params();
		setcookie(session_name(), '', time() - 42000, $params['path'],
				  $params['domain'], $params['secure'],
				  $params['httponly']);
	}

	# Destroy session
	session_destroy();
}

$messages = array(
                  'logout'            => _('You are now successfully logged out'),
                  'password sent'     => _('Successfully sent password recovery '.
                                           'instructions by email, please check'),
                  'password modified' => _('Password successfully modified'),
                  );

if(isset($_GET['message']) || (! empty($message))) {
    $message = (empty($message) ? $messages[$_GET['message']] : $message); 
    $message = '<div style="color:black;background-color:#ffced0;border:1px solid red;width:600px;margin-left:100px;padding:10px;margin-bottom:20px;">'
        . $message
        . "</div>";
}


$form = sprintf(
                file_get_contents('../template/login.phtml'),
                $message,
                isset($_POST['email'])?$_POST['email']:'', //2
                isset($_POST['password'])?$_POST['password']:'', //3
                $return_url, //4
                isset($_POST['password2'])?$_POST['password2']:'', //5
                isset($_GET['email'])?$_GET['email']:'', //6
                isset($_GET['ticket'])?$_GET['ticket']:'', //7
                isset($_POST['firstname'])?$_POST['firstname']:'', //8
                isset($_POST['lastname'])?$_POST['lastname']:''  //9
                );

$page =  sprintf(
                 str_replace("100%", "100percent", 
                             file_get_contents('../template/'.TEMPLATE.'.phtml')),
                 $form,
                 $focus);

print str_replace("100percent","100%", $page);
?>
