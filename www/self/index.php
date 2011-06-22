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

?>
<?
require('/etc/cybsso/config.php');
require('cybsso/CybSSOPrivate.php');

session_start();

# If the user has just logged in, then we set the session and redirect to ourself
if(isset($_GET['cybsso_ticket'], $_GET['cybsso_email'])) {
	try{
		$cybsso = new CybSSO;
		$expiration = $cybsso->TicketCheck($_GET['cybsso_ticket'],
										   $_GET['cybsso_email']);

		$_SESSION = array(
			'ticket'                 => $_GET['cybsso_ticket'],
			'ticket_expiration_date' => $expiration,
			'user'                   => $cybsso->UserGetInfo($_GET['cybsso_email']),
		);

		header('Location: ./');
		exit;
	}
	catch(SoapFault $fault) {
		# Ticket is invalid, go back to the SSO
		header('Location: /');
		exit;
	}
}

# Check if ticket is defined and is still valid
if(!isset($_SESSION['ticket']) or
   !isset($_SESSION['ticket_expiration_date']) or
   $_SESSION['ticket_expiration_date'] <= time() or
   !isset($_SESSION['user']['email'])) {

	# Redirect to the auth page if ticket is invalid
	header('Location: /');
	exit;
}

# Update user information if needed
if(isset($_POST['action']) and $_POST['action']=='Update') {
	try{
		$cybsso = new CybSSOPrivate;
		$cybsso->UserUpdate($_POST);
		$_SESSION['user'] = $cybsso->UserGetInfo($_SESSION['user']['email']);
	}
	catch(SoapFault $fault) {
		echo '<font color="red">'.$fault->getMessage() . '</font>';
	}
}

?>

<html>
<head>
<title>Self care</title>

<h3>Self care</h3>

<h3>User information</h3>
<form method="POST" action="./">
 Firstname: <input type="text" name="firstname" value="<?=isset($_SESSION['user']['firstname'])?$_SESSION['user']['firstname']:''?>" /> <br/>
 Lastname:  <input type="text" name="lastname" value="<?=isset($_SESSION['user']['lastname'])?$_SESSION['user']['lastname']:''?>" /> <br/>
 Email: <input type="text" name="email" value="<?=isset($_SESSION['user']['email'])?$_SESSION['user']['email']:''?>" /> <br/>
	  Language: 
  <select name="language">
    <option value="fr_FR">French</option>
    <option value="en_US" <?if(isset($_SESSION['user']['language']) and $_SESSION['user']['language'] == 'en_US') echo 'selected';?>>English</option>
  </select>
<br/>
 <input type="submit" name='action' value="Update">
</form>


<a href="/?action=logout">Log out</a>
