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
require('../../lib/CybSSO.php');

session_start();

# If the user just logged in, then we set the session and redirect
if(isset($_GET['ticket'], $_GET['email'])) {
	try{
		$cybsso = new CybSSO;
		$expiration = $cybsso->TicketCheck($_GET['ticket'], $_GET['email']);

		$_SESSION = array(
			'ticket'                 => $_GET['ticket'],
			'ticket_expiration_date' => $expiration,
			'email'                  => $_GET['email'],
		);

		header('Location: ./');
		exit;
	}
	catch(SoapFault $fault) {
		echo '<font color="red">'.$fault->getMessage() . '</font>';
	}
}

# Check if ticket is defined and still valid
if(!isset($_SESSION['ticket']) or
   !isset($_SESSION['ticket_expiration_date']) or
   $_SESSION['ticket_expiration_date'] <= mktime() or
   !isset($_SESSION['email'])) {

	header('Location: /');
	exit;
}

$cybsso = new CybSSO;
$user = $cybsso->UserGetInfo($_SESSION['email']);
print_r($user);exit;
?>

<h3>Self care</h3>

<h3>User information</h3>
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


<a href="/logout">Log out</a>
