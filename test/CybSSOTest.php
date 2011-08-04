<?php
//
// Copyright (C) 2010 Cyril Bouthors <cyril@bouthors.org>
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

require_once('../lib/CybSSO.php');
require('../etc/config-test.php');

class CybSSOTest extends PHPUnit_Framework_TestCase
{

    protected function setUp() {
		global $cybsso_sql_config;

		$this->CybSSO = new CybSSO;

		// Allow access to private methods

		// CybSSO::_SQLOpen()
        $this->_SQLOpen = new ReflectionMethod('CybSSO', '_SQLOpen');
        $this->_SQLOpen->setAccessible(TRUE);

		// CybSSO::_SQLQuery()
		$this->_SQLQuery = new ReflectionMethod('CybSSO', '_SQLQuery');
		$this->_SQLQuery->setAccessible(TRUE);

		// Connect to the MySQL server
		mysql_connect($cybsso_sql_config['host'],
					  $cybsso_sql_config['user'],
					  $cybsso_sql_config['pass'])
			or die(mysql_error());

		// Drop database if needed
		mysql_query('DROP DATABASE IF EXISTS ' . $cybsso_sql_config['db'])
			or die(mysql_error());

		// Create database
		mysql_query('CREATE DATABASE ' . $cybsso_sql_config['db'])
			or die(mysql_error());

		// Select the MySQL database
		mysql_select_db($cybsso_sql_config['db'])
			or die(mysql_error());

		// Create table(s)
		foreach(split(';', file_get_contents('schema.sql')) as $query) {
			if(preg_match('/^\s+$/', $query))
				continue;
			mysql_query($query)
			   or die(mysql_error());
		}

		// Close connection
		mysql_close()
			or die(mysql_error());

		// CybSSO::_ValidateTicket()
        $this->_ValidateTicket = new ReflectionMethod('CybSSO', '_ValidateTicket');
        $this->_ValidateTicket->setAccessible(TRUE);

		// CybSSO::_ValidateUserAvailable()
        $this->_ValidateUserAvailable =
			new ReflectionMethod('CybSSO', '_ValidateUserAvailable');
        $this->_ValidateUserAvailable->setAccessible(TRUE);

		// CybSSO::_ValidateUserExists()
        $this->_ValidateUserExists =
			new ReflectionMethod('CybSSO', '_ValidateUserExists');
        $this->_ValidateUserExists->setAccessible(TRUE);

		// CybSSO::_TicketCreate()
        $this->_TicketCreate = new ReflectionMethod('CybSSO', '_TicketCreate');
        $this->_TicketCreate->setAccessible(TRUE);

		// CybSSO::_UserCreate()
        $this->_UserCreate = new ReflectionMethod('CybSSO', '_UserCreate');
        $this->_UserCreate->setAccessible(TRUE);

		// CybSSO::_UserUpdate()
        $this->_UserUpdate = new ReflectionMethod('CybSSO', '_UserUpdate');
        $this->_UserUpdate->setAccessible(TRUE);

		// CybSSO::_PasswordRecovery()
        $this->_PasswordRecovery =
			new ReflectionMethod('CybSSO', '_PasswordRecovery');
        $this->_PasswordRecovery->setAccessible(TRUE);

		// CybSSO::_PasswordRecoveryCheckTicket()
        $this->_PasswordRecoveryCheckTicket =
			new ReflectionMethod('CybSSO', '_PasswordRecoveryCheckTicket');
        $this->_PasswordRecoveryCheckTicket->setAccessible(TRUE);

		// CybSSO::_PasswordReset()
        $this->_PasswordReset =
			new ReflectionMethod('CybSSO', '_PasswordReset');
        $this->_PasswordReset->setAccessible(TRUE);
	}

	protected function TearDown() {
		require('../etc/config-test.php');

		// Connect to the MySQL server
		mysql_connect($cybsso_sql_config['host'],
					  $cybsso_sql_config['user'],
					  $cybsso_sql_config['pass'])
			or die(mysql_error());

		// Drop database if needed
		mysql_query('DROP DATABASE IF EXISTS ' . $cybsso_sql_config['db'])
			or die(mysql_error());

		// Close connection
		mysql_close()
			or die(mysql_error());
	}

	################################################################
	# MySQL
    function testSQLOpenUnableToConnect() {
		global $cybsso_sql_config;
		$old_user = $cybsso_sql_config['user'];
		$cybsso_sql_config['user'] = 'invalid-user';

        $this->setExpectedException('SoapFault');
		$this->_SQLOpen->invoke($this->CybSSO);

		$cybsso_sql_config['user'] = $old_user;
    }

    function testSQLOpenUnableToSelectDB() {
		$this->TearDown();

        $this->setExpectedException('SoapFault');
		$this->_SQLOpen->invoke($this->CybSSO);
    }

    function testSQLOpenOK() {
		$this->_SQLOpen->invoke($this->CybSSO);
    }

    function testSQLQueryInvalidSQLSyntax() {
        $this->setExpectedException('SoapFault');
		$this->_SQLQuery->invoke($this->CybSSO, 'invalid sql syntax');
    }

    function testSQLQueryOK() {
		$this->_SQLQuery->invoke($this->CybSSO, 'SELECT 1');
    }

	################################################################
	# Validators

	function testValidateTickeInvalid() {
        $this->setExpectedException('SoapFault');
		$this->_ValidateTicket->invoke($this->CybSSO, 'too-short');

        $this->setExpectedException('SoapFault');
		$this->_ValidateTicket->invoke($this->CybSSO,
									   'very-extremely-very-very-very-very-'.
									   'very-very-very-very-very-very-abusive-'.
									   'very-very-very-very-very-very-very-'.
									   'very-very-very-very-very-very-very-'.
									   'very-very-very-very-very-very-very-'.
									   'very-very-very-very-very-very-very-'.
									   'long-ticket');

        $this->setExpectedException('SoapFault');
		$this->_ValidateTicket->invoke($this->CybSSO, '!@#$%&^*&');
	}

	function testValidateTicketOK() {
		$ticket = 'ab6586039173d096651d700c5db63928ab3cbd68b635a6c814da37414f9';
		$email = 'user1@company.com';
		$this->_ValidateTicket->invoke($this->CybSSO, $ticket);
	}

    function testValidateUserAvailable() {
		$user = array(
			'email'    => 'user1@company.com',
			'password' => 'my-password',
		);

		$this->_ValidateUserAvailable->invoke($this->CybSSO, $user['email']);
		$this->_UserCreate->invoke($this->CybSSO, $user);

		$this->setExpectedException('SoapFault');
		$this->_ValidateUserAvailable->invoke($this->CybSSO, $user['email']);
    }

    function testValidateUserExistsOK() {
		$user = array(
			'email'    => 'user1@company.com',
			'password' => 'my-password',
		);

		$this->_UserCreate->invoke($this->CybSSO, $user);
		$this->_ValidateUserExists->invoke($this->CybSSO, $user['email']);
    }

    function testValidateUserExistNoUser() {
		$this->setExpectedException('SoapFault');
		$this->_ValidateUserExists->invoke($this->CybSSO, 'user1@company.com');
	}

	################################################################
	# Ticket

	function testTicketCheckUnknownTicket() {
		$ticket = 'IuRK2RQxkG0H1c7Byxw0Z6oaTvRrETYxbOdqSmYkJmPsrjqrqbo3h6sxvu';
		$email = 'user1@company.com';
		$this->setExpectedException('SoapFault');
		$this->CybSSO->TicketCheck($ticket, $email);
	}

	function testTicketCheckOK() {
		global $cybsso_sql_config;
		$ticket = 'IuRK2RQxkG0H1c7Byxw0Z6oaTvRrETYxbOdqSmYkJmPsrjqrqbo3h6sxvu';
		$email = 'user1@company.com';

		mysql_connect($cybsso_sql_config['host'],
					  $cybsso_sql_config['user'],
					  $cybsso_sql_config['pass'])
			or die(mysql_error());

		$tomorrow = time() + 86400;

		mysql_query('INSERT INTO user SET '.
					"ticket = '$ticket', " .
					"email  = '$email', " .
					"ticket_expiration_date = $tomorrow, ".
					"crypt_password = SHA1('crypt'), " .
					"firstname = 'John', ".
					"lastname = 'Doe'")
			or die(mysql_error());
		
		$this->CybSSO->TicketCheck($ticket, $email);
	}

	function testTicketCreateInvalidPassword() {
		$user = array(
			'email'    => 'user1@company.com',
			'password' => 'my-password',
		);

		$this->_UserCreate->invoke($this->CybSSO, $user);

		$this->setExpectedException('SoapFault');
		$this->_TicketCreate->invoke($this->CybSSO, $user['email'],
									 'incorrect-password');
	}

	function testTicketCreateOK() {
		$user = array(
			'email'     => 'user1@company.com',
			'firstname' => 'John',
			'lastname'  => 'Doe',
			'password'  => 'valid-password',
		);

		$this->_UserCreate->invoke($this->CybSSO, $user);

		$ticket = $this->_TicketCreate->invoke($this->CybSSO,
											   $user['email'],
											   $user['password']);

		$this->assertType('array', $ticket);
		$this->assertArrayHasKey('name', $ticket);
		$this->assertArrayHasKey('expiration', $ticket);
	}

	################################################################
	# User

	function testUserGetInfoUnknownUser() {
		$this->setExpectedException('SoapFault');
		$this->CybSSO->UserGetInfo('user1@company.com');
	}

	function testUserGetInfoOK() {
		$user = array(
			'email'     => 'user1@company.com',
			'firstname' => 'John',
			'lastname'  => 'Doe',
			'password'  => 'valid-password',
		);

		$this->_UserCreate->invoke($this->CybSSO, $user);

		$new_user = $this->CybSSO->UserGetInfo($user['email']);

		$this->assertEquals($user['firstname'], $new_user['firstname']);
		$this->assertEquals($user['lastname'],  $new_user['lastname']);
		$this->assertEquals($user['email'],     $new_user['email']);
	}

	function testUserCreateDuplicated() {
		$user = array(
			'email'    => 'user1@company.com',
			'password' => 'valid-password',
		);

		$this->_UserCreate->invoke($this->CybSSO, $user);
		$this->setExpectedException('SoapFault');
		$this->_UserCreate->invoke($this->CybSSO, $user);
	}

	function testUserCreateOK() {
		$user = array(
			'email'    => 'user1@company.com',
			'password' => 'valid-password',
		);

		$this->_UserCreate->invoke($this->CybSSO, $user);
	}

	function testUserUpdateOK() {
		$user_create = array(
			'email'     => 'user2@company.com',
			'password'  => 'valid-password',
			'firstname' => 'initial firstname',
			'lastname'  => 'initial lastname',
			'language'  => 'en_US',
		);

		$this->_UserCreate->invoke($this->CybSSO, $user_create);

		$user = $this->CybSSO->UserGetInfo($user_create['email']);

		$this->assertEquals($user_create['firstname'], $user['firstname']);
		$this->assertEquals($user_create['lastname'],  $user['lastname']);
		$this->assertEquals($user_create['email'],     $user['email']);
		$this->assertEquals($user_create['language'],  $user['language']);

		$user_update = array(
			'email'     => 'user2@company.com',
			'firstname' => 'new first name',
			'lastname'  => 'new last name',
			'language'  => 'fr_FR',
		);
		$this->_UserUpdate->invoke($this->CybSSO, $user_update);

		$user = $this->CybSSO->UserGetInfo($user_create['email']);

		$this->assertEquals($user_update['firstname'], $user['firstname']);
		$this->assertEquals($user_update['lastname'],  $user['lastname']);
		$this->assertEquals($user_update['email'],     $user['email']);
		$this->assertEquals($user_update['language'],  $user['language']);
	}

	function testPasswordRecoveryOK() {
		$user = array(
			'email'    => 'user1@company.com',
			'password' => 'valid-password',
		);

		$this->_UserCreate->invoke($this->CybSSO, $user);

		$this->_PasswordRecovery->invoke($this->CybSSO, $user['email']);
	}

	function testPasswordRecoveryCheckTicketUnknownTicket() {
		$ticket = 'IuRK2RQxkG0H1c7Byxw0Z6oaTvRrETYxbOdqSmYkJmPsrjqrqbo3h6sxvu';

		$user = array(
			'email'    => 'user1@company.com',
			'password' => 'valid-password',
		);

		$this->_UserCreate->invoke($this->CybSSO, $user);

		$this->setExpectedException('SoapFault');
		$this->_PasswordRecoveryCheckTicket->invoke(
			$this->CybSSO, $user['email'], $ticket);
	}

	function testPasswordRecoveryCheckTicketOK() {
		global $cybsso_sql_config;
		$ticket = 'IuRK2RQxkG0H1c7Byxw0Z6oaTvRrETYxbOdqSmYkJmPsrjqrqbo3h6sxvu';

		$user = array(
			'email'    => 'user1@company.com',
			'password' => 'valid-password',
		);

		$this->_UserCreate->invoke($this->CybSSO, $user);

		mysql_connect($cybsso_sql_config['host'],
					  $cybsso_sql_config['user'],
					  $cybsso_sql_config['pass'])
			or die(mysql_error());

		$tomorrow = time() + 86400;

		mysql_query('INSERT INTO password_recovery SET '.
					"ticket = '$ticket', " .
					"email  = '$user[email]', " .
					"ticket_expiration_date = $tomorrow")
			or die(mysql_error());
		
		$this->_PasswordRecoveryCheckTicket->invoke(
			$this->CybSSO, $user['email'], $ticket);
	}

	function testPasswordResetPasswordMismatch() {
		$user = array(
			'email'    => 'user1@company.com',
			'password' => 'valid-password',
		);

		$this->_UserCreate->invoke($this->CybSSO, $user);

		$this->setExpectedException('SoapFault');
		$this->_PasswordReset->invoke(
			$this->CybSSO, $user['email'], 'password1', 'password2');
	}

	function testPasswordResetUserDidNotAskReset() {
		$user = array(
			'email'    => 'user1@company.com',
			'password' => 'valid-password',
		);

		$this->_UserCreate->invoke($this->CybSSO, $user);

		$this->setExpectedException('SoapFault');
		$this->_PasswordReset->invoke(
			$this->CybSSO, $user['email'], 'password', 'password');
	}

	function testPasswordResetOK() {
		$user = array(
			'email'    => 'user1@company.com',
			'password' => 'valid-password',
		);

		$this->_UserCreate->invoke($this->CybSSO, $user);

		$this->_PasswordRecovery->invoke($this->CybSSO, $user['email']);

		$this->_PasswordReset->invoke(
			$this->CybSSO, $user['email'], 'password', 'password');
	}

	function testUrl() {
		$this->CybSSO->url();
	}
}
?>
