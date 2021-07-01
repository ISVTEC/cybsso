<?php

require('cybsso.php');

echo "User successfully connected: " .
$_SESSION[cybsso_user][firstname] . ' ' .
$_SESSION[cybsso_user][lastname] . ' ' .
$_SESSION[cybsso_user][email]. ' ' .
$_SESSION[cybsso_user][language];

?>

<br/>
<a href="logout.php">Logout</a> <br/>
<a href="<?=CYBSSO_URL?>/self/">Self care</a>
