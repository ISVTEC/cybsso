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
 * try {
 * 	$sso_param = array(
 * 		'location' => 'https://my.machine.com/cybsso/',
 * 		'login'    => 'my-login',
 * 		'password' => 'my-password',
 * 		'uri'      => '',
 * 	);
 * 
 * 	$cybsso = new SoapClient(null, $sso_param);
 * 
 * 	// Check auth ticket
 *  $ticket = '0E5Y9qXXn6sARVEKjE5jczLzE9hDYzLDHKL3EQlXxgeTU8cj78AknPoOTewJHhFHh03GHScP8I0BzZx4Sf6bfBQWk1q8mFQaC8Q6R2MbzpBdo65OXuXFfT5SIeCkwjfM';
 *  $email = 'user1@company.com';
 * 	print_r($cybsso->TicketCheck($ticket, $email));
 *
 * 	// Fetch user information
 * 	print_r($cybsso->UserGetInfo('user1@company.com'));
 * }
 * catch(SoapFault $fault) {
 * 	echo $fault;
 * }
 * \endcode
 */

class CybSSO {

	private $_db = false;

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
	 * @return ticket_expiration_date timestamp. Will contain the expiration
	 * date of the ticket. The method will throw an exception if the ticket is
	 * invalid.
	 *
	 */
	function TicketCheck($ticket = null, $email = null) {

		$this->_ValidateTicket($ticket);
		$this->_ValidateEmail($email);

		$email  = mysql_escape_string($email);
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

	protected function _TicketCreate($email = null, $password = null) {
		$this->_ValidateEmail($email);
		$this->_ValidatePassword($password);
		
		$email    = mysql_escape_string($email);

		# Crypt password
		$password = sha1(mysql_escape_string($password));

		# Generate random ticket
		$ticket = sha1(uniqid('', true)) . sha1(uniqid('', true));

		$tomorrow = time() + 86400;

		$result = $this->_SQLQuery(
			'UPDATE user ' .
			"SET ticket='$ticket', " .
			"  ticket_expiration_date = $tomorrow ".
			"WHERE email = '$email' AND " .
			"  crypt_password = '$password' " .
			'LIMIT 1');

		if(mysql_affected_rows() != 1)
			throw new SoapFault(__CLASS__ .'->'. __FUNCTION__.'()',
								'Invalid email or password');

		return $ticket;
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
		$email  = mysql_escape_string($email);

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

		foreach(array('email', 'password', 'language', 'firstname', 'lastname')
				as $key) {
			if(!isset($user[$key]))
				$user[$key] = '';
			$user[$key]  = mysql_escape_string($user[$key]);
		}

		# Crypt password
		$user['password'] = sha1($user['password']);

		# Generate random ticket
		$ticket = sha1(uniqid('', true)) . sha1(uniqid('', true));

		$tomorrow = time() + 86400;

		$result = $this->_SQLQuery(
			'INSERT INTO user '.
			"SET email                = '$user[email]', ".
			"  crypt_password         = '$user[password]', ".
			"  firstname              = '$user[firstname]', ".
			"  lastname               = '$user[lastname]', ".
			"  language               = '$user[language]', ".
			"  ticket                 = '$ticket', ".
			"  ticket_expiration_date = $tomorrow");

		return $ticket;
	}

}

?>
