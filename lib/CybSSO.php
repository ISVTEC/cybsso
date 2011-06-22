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

/**
 * This API handles SSO.
 *
 * \code
 * <?php
 * 
 * session_start();
 * 
 * // Check if ticket is defined and is still valid
 * if(!isset($_SESSION['ticket'],
 * 		  $_SESSION['ticket_expiration_date'],
 * 		  $_SESSION['cybsso_user']['email']) or
 *    $_SESSION['ticket_expiration_date'] <= time()) {
 * 
 * 	try{
 * 		$return_url = (($_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://') .
 * 			$_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
 * 
 * 		// Connect to the SSO API
 * 		$sso_param = array(
 * 			// 'location' => 'https://login.isvtec.com/',
 * 			'location' => 'http://cybsso-api.dev.isvtec.com/',
 * 			'login'    => 'api-login',
 * 			'password' => 'api-password',
 * 			'uri'      => '',
 * 			);
 * 
 * 		$cybsso = new SoapClient(null, $sso_param);
 * 
 * 		// Redirect to the auth page if ticket is invalid and no information is
 * 		// given
 * 		if(!isset($_GET['cybsso_ticket'], $_GET['cybsso_email'])) {
 * 			header('Location: ' . $cybsso->url() . "?return_url=$return_url");
 * 			exit;
 * 		}
 * 
 * 		// If the user has just logged in, then we set the session and redirect
 * 		// to ourself
 * 		$expiration = $cybsso->TicketCheck($_GET['cybsso_ticket'],
 * 										   $_GET['cybsso_email']);
 * 
 * 		$cybsso_user = $cybsso->UserGetInfo($_GET['cybsso_email']);
 * 
 * 		$_SESSION = array(
 * 			'ticket'                 => $_GET['cybsso_ticket'],
 * 			'ticket_expiration_date' => $expiration,
 * 			'cybsso_user'            => $cybsso_user,
 * 			'cybsso_url'             => $cybsso->url(),
 * 		);
 * 
 * 		header("Location: $return_url");
 * 		exit;
 * 	}
 * 	catch(SoapFault $fault) {
 * 		// Ticket is invalid, go back to the SSO
 * 		header('Location: ' . $cybsso->url() . "?return_url=$return_url");
 * 		exit;
 * 	}
 * }
 * 
 * echo '<pre>';
 * print_r($_SESSION['cybsso_user']);
 * 
 * ?>
 * <br/>
 * <a href="<?=$_SESSION['cybsso_url']?>?action=logout">Logout</a> <br/>
 * <a href="<?=$_SESSION['cybsso_url']?>/self/">Self care</a>
 * \endcode
 */

class CybSSO {

	private $_db = false;
	private $_ticket_validity = 86400;
	private $_email_sender_name = 'Go Managed Applications';
	private $_email_sender_address = 'noreply@go-managed-app.com';
	// public $url = 'https://login.isvtec.com/';
	public $url = 'http://cybsso.dev.isvtec.com/';

	# Ugly SOAP work-around
	function url() {
		return $this->url;
	}

	################################################################
	# Validate

	private function _ValidateEmail($email ='') {
		if(strlen($email) < 3)
			throw new SoapFault(__CLASS__ .'->'. __FUNCTION__.'()',
								'Email address is too short');

		if(strlen($email) > 64)
			throw new SoapFault(__CLASS__ .'->'. __FUNCTION__.'()',
								'Email address is too long');

		if(!preg_match('/^([\w\-\.]|\*)+@[\w\-]+\.[\w\-]+$/', $email))
			throw new SoapFault(__CLASS__ .'->'. __FUNCTION__.'()',
								'Email address is syntactically incorrect');

		$domain = preg_replace('/.*@/', '', $email);
		$this->_ValidateDomain($domain);
	}

	/* If this method requires IDN support or complicated stuff like that, you
	 * should have a look at /usr/share/php/Zend/Validate/Hostname.php as this
	 * method was using Zend_Validate_Hostname before
	 */
	private function _ValidateDomain($domain) {
		# Check if $domain is too short
		if (strlen($domain) < 4)
			throw new SoapFault(__CLASS__ .'->'. __FUNCTION__.'()',
								'Domain name is too short');

		# Check if $domain is too long
		if (strlen($domain) > 128)
			throw new SoapFault(__CLASS__ .'->'. __FUNCTION__.'()',
								'Domain name is too long');

		# Check if $domain is syntactically correct
		if(!preg_match('/^[\w\-]+\.[\w\-\.]+$/', $domain))
			throw new SoapFault(__CLASS__ .'->'. __FUNCTION__.'()',
								'Invalid domain syntax');
	}

	private function _ValidateTicket($ticket ='') {
		if(!preg_match('/^[a-zA-Z0-9]{50,128}$/', $ticket))
			throw new SoapFault(__CLASS__ .'->'. __FUNCTION__.'()',
								'Ticket name is syntactically incorrect');
	}

	private function _ValidatePassword($password) {
		# Check if $password is too short
		if (strlen($password) < 6)
			throw new SoapFault(__CLASS__ .'->'. __FUNCTION__.'()',
								'Password is too short');

		# Check if $password is too long
		if (strlen($password) > 32)
			throw new SoapFault(__CLASS__ .'->'. __FUNCTION__.'()',
								'Password is too long');

		# Check if $password is syntactically correct
		if(!preg_match('/^[a-zA-Z0-9\_\-]+$/', $password))
			throw new SoapFault(__CLASS__ .'->'. __FUNCTION__.'()',
								'Invalid password syntax');
	}

	private function _ValidateUserAvailable($email = null) {
		$email = strtolower(mysql_escape_string($email));

		$result = $this->_SQLQuery('SELECT email '.
								   'FROM user '.
								   "WHERE email = '$email'");

		if(mysql_num_rows($result) != 0)
			throw new SoapFault(__CLASS__ .'->'. __FUNCTION__.'()',
								'User already exists');
	}

	private function _ValidateUserExists($email = null) {
		$email = strtolower(mysql_escape_string($email));

		$result = $this->_SQLQuery('SELECT email '.
								   'FROM user '.
								   "WHERE email = '$email'");

		if(mysql_num_rows($result) != 1)
			throw new SoapFault(__CLASS__ .'->'. __FUNCTION__.'()',
								'User does not exist');
	}

	################################################################
	# MySQL

	private function _SQLOpen() {
		global $cybsso_sql_config;
		# Connect to the database

		$this->_db = @mysql_connect($cybsso_sql_config['host'],
									$cybsso_sql_config['user'],
									$cybsso_sql_config['pass']);
		if(!$this->_db)
			throw new SoapFault(__CLASS__ .'->'. __FUNCTION__.'()',
								mysql_error());

		# Tell MySQL to report any warning
		@mysql_query("SET SESSION sql_mode='STRICT_TRANS_TABLES'");

		# Select MySQL database
		if(!@mysql_select_db($cybsso_sql_config['db']))
			throw new SoapFault(__CLASS__ .'->'. __FUNCTION__.'()',
								mysql_error());

	}

	private function _SQLQuery($query) {
		// Open SQL connection if needed
		if(!$this->_db)
			$this->_SQLOpen();

		// Check that we are able to run SQL queries because if we are called
		// from a persistent Soap session then the object might be here but not
		// the file descriptor anymore. We need to run _SQLOpen again
		$result = @mysql_query('SELECT 1', $this->_db);
		if($result === false)
			$this->_SQLOpen();

		$result = @mysql_query($query, $this->_db);

		if($result === false)
			throw new SoapFault(__CLASS__ .'->'. __FUNCTION__.'()',
								"Unable to perform SQL query:\n$query\n" .
								mysql_error());

		return $result;
	}

	################################################################
	# Ticket

	/**
	 * Check ticket validity
	 *
	 * @param ticket string. The ticket reference.
	 * @param email string. The user email address.
	 *
	 * @return expiration int. The expiration date of the ticket in a UNIX
	 * timestamp format.
	 * 
	 * The method will throw an exception if the ticket is invalid.
	 */
	function TicketCheck($ticket = null, $email = null) {

		$this->_ValidateTicket($ticket);
		$this->_ValidateEmail($email);

		$email  = strtolower(mysql_escape_string($email));
		$ticket = mysql_escape_string($ticket);

		$now = time();

		$result = $this->_SQLQuery('SELECT ticket_expiration_date ' .
								   'FROM user ' .
								   "WHERE email = '$email' AND " .
								   "ticket = '$ticket' AND ".
								   "ticket_expiration_date > $now");

		if(mysql_num_rows($result) == 1) {
			$row = mysql_fetch_row($result);
			return $row[0];
		}

		throw new SoapFault(__CLASS__ .'->'. __FUNCTION__.'()',
							'Invalid ticket');
	}

	/**
	 * Create a new ticket
	 *
	 * @param $email string. The user email address.
	 *
	 * @param password string. The user password.
	 * 
	 * @return ticket_info array. An array contaning information about the
	 * ticket, for example:
	 *
	 * $ticket = array(
	 * 	'name'       => '4b09dc55b89862439d3c1c68e544a98c3962c7a7619051e43df8a',
	 * 	'expiration' => 1308376031,
	 * );
	 * 
	 * 'ticket' contains the ticket name.
	 *
	 * expiration contains the expiration date of the ticket in a UNIX timestamp
	 * format.
	 */
	protected function _TicketCreate($email = null, $password = null) {
		$this->_ValidateEmail($email);
		$this->_ValidatePassword($password);
		$this->_ValidateUserExists($email);
		
		$email = strtolower(mysql_escape_string($email));

		# Crypt password
		$password = sha1($password);

		# Generate random ticket
		$ticket = sha1(uniqid('', true)) . sha1(uniqid('', true));

		$tomorrow = time() + $this->_ticket_validity;

		$result = $this->_SQLQuery(
			'UPDATE user ' .
			"SET ticket='$ticket', " .
			"    ticket_expiration_date = $tomorrow ".
			"WHERE email = '$email' AND " .
			"  crypt_password = '$password' " .
			'LIMIT 1');

		if(mysql_affected_rows() != 1)
			throw new SoapFault(__CLASS__ .'->'. __FUNCTION__.'()',
								'Invalid email or password');

		return array(
			'name'       => $ticket,
			'expiration' => $tomorrow,
		);
	}

	################################################################
	# User

	/**
	 * Get user information. If no user matches the email address then an
	 * exception will be thrown.
	 *
	 * @param email string. The user email address.
	 *
	 * @return user array. An array containing the user information.
	 *
	 * \code
	 * $user = array(
	 * 	'email'     => 'user1@company.com',
	 * 	'language'  => 'en_US',
	 * 	'firstname' => 'John',
	 * 	'lastname'  => 'Doe',
	 * );
	 * \endcode
	 *
	 */
	function UserGetInfo($email = null) {
		$this->_ValidateEmail($email);
		$email = strtolower(mysql_escape_string($email));

		$result = $this->_SQLQuery(
			'SELECT email, language, firstname, lastname '.
			'FROM user '.
			"WHERE email='$email' " .
			'LIMIT 1');

		if(mysql_num_rows($result) != 1)
			throw new SoapFault(__CLASS__ .'->'. __FUNCTION__.'()',
								'Unknown user');

		return mysql_fetch_assoc($result);
	}

	protected function _UserCreate(array $user = array()) {
		$this->_ValidateEmail($user['email']);
		$this->_ValidatePassword($user['password']);
		$this->_ValidateUserAvailable($user['email']);

		foreach(array('email', 'password', 'language', 'firstname', 'lastname')
				as $key) {
			if(!isset($user[$key]))
				$user[$key] = '';
			$user[$key] = mysql_escape_string($user[$key]);
		}

		# Lowercase email address
		$user['email'] = strtolower($user['email']);

		# Crypt password
		$user['password'] = sha1($user['password']);

		# Generate random ticket
		$ticket = sha1(uniqid('', true)) . sha1(uniqid('', true));

		$tomorrow = time() + $this->_ticket_validity;

		$result = $this->_SQLQuery(
			'INSERT INTO user '.
			"SET email                  = '$user[email]', ".
			"    crypt_password         = '$user[password]', ".
			"    firstname              = '$user[firstname]', ".
			"    lastname               = '$user[lastname]', ".
			"    language               = '$user[language]', ".
			"    ticket                 = '$ticket', ".
			"    ticket_expiration_date = $tomorrow");

		return array(
			'name'       => $ticket,
			'expiration' => $tomorrow,
		);
	}

	protected function _UserUpdate(array $user = array()) {
		$this->_ValidateEmail($user['email']);
		$this->_ValidateUserExists($user['email']);

		foreach(array('email', 'language', 'firstname', 'lastname') as $key) {
			if(!isset($user[$key]))
				$user[$key] = '';
			$user[$key] = mysql_escape_string($user[$key]);
		}

		# Lowercase email address
		$user['email'] = strtolower($user['email']);

		$result = $this->_SQLQuery(
   			'UPDATE user '.
   			'SET ' .
			"    firstname = '$user[firstname]', ".
			"    lastname  = '$user[lastname]', ".
			"    language  = '$user[language]' ".
			"WHERE   email = '$user[email]' " .
			'LIMIT 1');
	}

   	protected function _PasswordRecovery($email = null, $return_url = null) {
   		$this->_ValidateEmail($email);
		$this->_ValidateUserExists($email);

   		# Lowercase email address
		$email = strtolower(mysql_escape_string($email));

		# Generate random ticket
		$ticket = sha1(uniqid('', true)) . sha1(uniqid('', true));

		$four_hours = time() + 4*60*60;

		# Delete any previous password recovery ticket if needed
   		$result = $this->_SQLQuery(
   			'DELETE FROM password_recovery '.
   			"WHERE email = '$email' ".
			'LIMIT 1');

		# Insert a new password recovery ticket
   		$result = $this->_SQLQuery(
   			'INSERT INTO password_recovery '.
   			"SET email = '$email', ".
			"    ticket = '$ticket', ".
			"    ticket_expiration_date = $four_hours");

		$link = $this->url."?email=$email&ticket=$ticket&".
			"action=Password%20recovery2";

		if(!empty($return_url))
			$link .= '&return_url=' . urlencode($return_url);

		$subject = 'Password recovery';
		$body = "<a href=\"$link\">Password recovery</a>";
		$headers = "From: $this->_email_sender_name ".
			"<$this->_email_sender_address>";

		# Avoid sending emails when we are running unit tests
		if(php_sapi_name() != 'cli')
			mail($email, $subject, $body, $headers);

   	}

   	protected function _PasswordRecoveryCheckTicket($email = null,
													$ticket = null) {

		$this->_ValidateTicket($ticket);
		$this->_ValidateEmail($email);
		$this->_ValidateUserExists($email);

		$email  = strtolower(mysql_escape_string($email));
		$ticket = mysql_escape_string($ticket);

		$now = time();

		$result = $this->_SQLQuery('SELECT ticket_expiration_date ' .
								   'FROM password_recovery ' .
								   "WHERE email = '$email' AND " .
								   "ticket = '$ticket' AND ".
								   "ticket_expiration_date > $now");

		if(mysql_num_rows($result) == 1) {
			$row = mysql_fetch_row($result);
			return $row[0];
		}

		throw new SoapFault(__CLASS__ .'->'. __FUNCTION__.'()',
							'Invalid ticket');
	}
   	protected function _PasswordReset($email = null, $password = null,
									  $password2 = null) {

		$this->_ValidateEmail($email);
		$this->_ValidateUserExists($email);
		$this->_ValidatePassword($password);

		if($password != $password2)
			throw new SoapFault(__CLASS__ .'->'. __FUNCTION__.'()',
								'Passwords do not match');

		$email  = strtolower(mysql_escape_string($email));

		# Crypt password
		$password = sha1($password);

		$result = $this->_SQLQuery(
			'DELETE FROM password_recovery ' .
			"WHERE email = '$email' " .
			'LIMIT 1');

		if(mysql_affected_rows() != 1)
			throw new SoapFault(__CLASS__ .'->'. __FUNCTION__.'()',
								'User did not ask for password reset');

		$result = $this->_SQLQuery(
			'UPDATE user ' .
			"SET crypt_password = '$password' ".
			"WHERE email = '$email' " .
			'LIMIT 1');
	}
}

?>
