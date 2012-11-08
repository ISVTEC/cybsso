<?php
//
// Copyright (C) 2011-2012 Cyril Bouthors <cyril@bouthors.org>
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

session_start();

// If the user has just logged in, then we set the session and redirect to
// ourself

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
    // Ticket is invalid, go back to the SSO
    header('Location: /');
    exit;
  }
}

// Check if ticket is defined and is still valid
if(!isset($_SESSION['ticket']) or
  !isset($_SESSION['ticket_expiration_date']) or
  $_SESSION['ticket_expiration_date'] <= time() or
  !isset($_SESSION['user']['email'])) {

  // Redirect to the auth page if ticket is invalid
  header('Location: /');
  exit;
}

// Update user information if needed
$message = '';
if(isset($_POST['action']) and $_POST['action']=='Update') {
  try{
    $cybsso = new CybSSOPrivate;
    $cybsso->UserUpdate($_POST);
    $_SESSION['user'] = $cybsso->UserGetInfo($_SESSION['user']['email']);
  }
  catch(SoapFault $fault) {
    $message = $fault->getMessage();
  }
}

if(! empty($message)) {
  $message = (empty($message) ? $messages[$_GET['message']] : $message);  
  $message = '<div style="color:black;background-color:#ffced0;border:1px solid red;width:600px;margin-left:50px;padding:10px;margin-bottom:20px;">'
    . $message
    . "</div>";
}

$options = array(
  "fr_FR" => "French",
  "en_US" => "English",
);

$html = "";
foreach ($options as $value => $option) {
  $html .= sprintf("<option value='%s' %s>%s</option>",
           $value,
           (isset($_SESSION['user']['language']) && $_SESSION['user']['language'] == $value) ?
           "selected='selected'" : "",
           $option);
}

$form = sprintf(
  file_get_contents('../../template/self.phtml'),
  isset($_SESSION['user']['firstname'])?$_SESSION['user']['firstname']:'',
  isset($_SESSION['user']['lastname'])?$_SESSION['user']['lastname']:'',
  isset($_SESSION['user']['email'])?$_SESSION['user']['email']:'',
  $html
);

$page =  sprintf(
  str_replace("100%", "100percent", 
    file_get_contents('../../template/'.TEMPLATE.'.phtml')),
  $form,
  "none");

print str_replace("100percent","100%", $page);
