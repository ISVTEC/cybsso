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

require('../lib/CybSSOPrivate.php');

# Check return_url
$return_url='https://';
if($_SERVER['SERVER_PORT'] == 80)
	$return_url='http://';

$return_url .= $_SERVER['HTTP_HOST'] . '/self/';

if(isset($_GET['return_url']))
	$return_url = $_GET['return_url'];

if(isset($_POST['return_url']))
	$return_url = $_POST['return_url'];

if(!preg_match('/^https?:\/\/.*$/', $return_url)) {
	echo "Invalid return_url format";
	exit;
}
   
$url_separator='?';
if(strpos($return_url, '?') == true)
	$url_separator='&';

session_start();

# Go straight to $return_url if a valid ticket has been found in the session
if(isset($_SESSION['ticket']) and
   isset($_SESSION['ticket_expiration_date']) and
   $_SESSION['ticket_expiration_date'] > mktime() and
   isset($_SESSION['email'])) {

	header('Location: '.$return_url . $url_separator .
		   "ticket=$_SESSION[ticket]&email=$_SESSION[email]");
	exit;
}

# Process action
if(!isset($_POST['action']))
	$_POST['action'] = 'none';

try{
	$cybsso = new CybSSOPrivate;

	switch($_POST['action']) {

		case 'Log in':
			$ticket = $cybsso->TicketCreate($_POST['email'], $_POST['password']);
			break;

		case 'Create account':
			$ticket = $cybsso->UserCreate($_POST);
			break;

		case 'none':
			break;

		default:
			throw new SoapFault(__CLASS__ .'->'. __FUNCTION__.'()',
								'Unknown action');
	}

	if(!empty($ticket)) {

		$_SESSION = array(
			'ticket'                 => $ticket,
			'ticket_expiration_date' => mktime() + 86400,
			'email'                  => $_POST['email'],
		);

		header('Location: '.$return_url . $url_separator .
			   "ticket=$ticket&email=$_POST[email]");
		exit;
	}
}
catch(SoapFault $fault) {
	echo '<font color="red">'.$fault->getMessage() . '</font>';
}

?>

<h3>Log in</h3>
<form method="POST" action="./">
 Email: <input type="text" name="email" value="<?=isset($_POST['email'])?$_POST['email']:''?>" /> <br/>
 Password: <input type="password" name="password" value="<?=isset($_POST['password'])?$_POST['password']:''?>" /> <br/>
 <input type="hidden" name="return_url" value="<?=$return_url?>" />
 <input type="submit" name='action' value="Log in">
</form>

<br/>

<h3>Create new account</h3>
<form method="POST" action="./">
 Firstname: <input type="text" name="firstname" value="<?=isset($_POST['firstname'])?$_POST['firstname']:''?>" /> <br/>
 Lastname:  <input type="text" name="lastname" value="<?=isset($_POST['lastname'])?$_POST['lastname']:''?>" /> <br/>
 Email: <input type="text" name="email" value="<?=isset($_POST['email'])?$_POST['email']:''?>" /> <br/>
 Password: <input type="password" name="password" value="<?=isset($_POST['password'])?$_POST['password']:''?>" /> <br/>
	  Language: 
  <select name="language">
    <option value="fr_FR">French</option>
    <option value="en_US">English</option>
  </select>
<br/>
 <input type="hidden" name="return_url" value="<?=$return_url?>" />
 <input type="submit" name='action' value="Create account">
</form>

<br/>
