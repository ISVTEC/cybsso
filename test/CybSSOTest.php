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
		mysql_query(file_get_contents('schema.sql'))
			or die(mysql_error());

		// Close connection
		mysql_close()
			or die(mysql_error());

		// CybSSO::_ValidateEmail()
        $this->_ValidateEmail = new ReflectionMethod('CybSSO', '_ValidateEmail');
        $this->_ValidateEmail->setAccessible(TRUE);

		// CybSSO::_ValidateDomain()
        $this->_ValidateDomain =
			new ReflectionMethod('CybSSO', '_ValidateDomain');
        $this->_ValidateDomain->setAccessible(TRUE);

		// CybSSO::_ValidateTicket()
        $this->_ValidateTicket = new ReflectionMethod('CybSSO', '_ValidateTicket');
        $this->_ValidateTicket->setAccessible(TRUE);

		// CybSSO::_ValidatePassword()
        $this->_ValidatePassword =
			new ReflectionMethod('CybSSO', '_ValidatePassword');
        $this->_ValidatePassword->setAccessible(TRUE);

		// CybSSO::_TicketCreate()
        $this->_TicketCreate = new ReflectionMethod('CybSSO', '_TicketCreate');
        $this->_TicketCreate->setAccessible(TRUE);

		// CybSSO::_UserCreate()
        $this->_UserCreate = new ReflectionMethod('CybSSO', '_UserCreate');
        $this->_UserCreate->setAccessible(TRUE);
	}

	protected function TearDown() {
		require('../etc/config.php');

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

	function testValidateEmailWithoutDomain() {
        $this->setExpectedException('SoapFault');
		$this->_ValidateEmail->invoke($this->CybSSO, 'foo-domain.com');
	}

    function testValidateEmailWithMultipleDomains() {
        $this->setExpectedException('SoapFault');
		$this->_ValidateEmail->invoke($this->CybSSO, 'foo@foo@foo.com');
	}

    function testValidateEmailInvalidSyntax() {
        $this->setExpectedException('SoapFault');
		$this->_ValidateEmail->invoke($this->CybSSO, "/3//1//''''@domain.com");
	}

    function testValidateEmailTooShort() {
        $this->setExpectedException('SoapFault');
		$this->_ValidateEmail->invoke($this->CybSSO, 'a');
	}

    function testValidateEmailTooLong() {
        $this->setExpectedException('SoapFault');
		$this->_ValidateEmail->invoke($this->CybSSO,
									  'very-long-email-very-long-email-'.
									  'very-long-email-very-long@email.com');
    }

    function testValidateEmailOK() {
		$this->_ValidateEmail->invoke($this->CybSSO, 'foo@domain.com');
		$this->_ValidateEmail->invoke($this->CybSSO, 'foo-bar@do-main.com');
    }

    function testValidateDomainInvalidSyntax() {
        $this->setExpectedException('SoapFault');
		$this->_ValidateDomain->invoke($this->CybSSO, 'invalid syntax.com');
    }

    function testValidateDomainTooShort() {
        $this->setExpectedException('SoapFault');
		$this->_ValidateDomain->invoke($this->CybSSO, 'x');
    }

    function testValidateDomainTooLong() {
        $this->setExpectedException('SoapFault');
		$this->_ValidateDomain->invoke($this->CybSSO,
									   'very-extremely-very-very-very-very-'.
									   'very-very-very-very-very-very-abusive-'.
									   'very-very-very-very-very-very-very-'.
									   'very-very-very-very-very-very-very-'.
									   'very-very-very-very-very-very-very-'.
									   'very-very-very-very-very-very-very-'.
									   'long-hostname');
    }

    function testValidateDomainOK() {
		$this->_ValidateDomain->invoke($this->CybSSO, 'google.com');
    }

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

    function testValidatePasswordAlnum() {
        $this->setExpectedException('SoapFault');
		$this->_ValidatePassword->invoke($this->CybSSO, '!*(!$)(!');
    }

    function testValidatePasswordTooShort() {
        $this->setExpectedException('SoapFault');
		$this->_ValidatePassword->invoke($this->CybSSO, 'Goovm');
    }

    function testValidatePasswordTooLong() {
        $this->setExpectedException('SoapFault');
		$this->_ValidatePassword->invoke($this->CybSSO,
										 'ThisPassWordIsExtremelyReallyTooLong');
    }

    function testValidatePasswordOK() {
		$this->_ValidatePassword->invoke($this->CybSSO, 'GoovmJk6C2vDvGoN');
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
		$email    = 'user1@company.com';
		$password = 'invalid-password';
		$this->setExpectedException('SoapFault');
		$this->_TicketCreate->invoke($this->CybSSO, $email, $password);
	}

	function testTicketCreateOK() {
		global $cybsso_sql_config;

		$email    = 'user1@company.com';
		$password = 'valid-password';

		mysql_connect($cybsso_sql_config['host'],
					  $cybsso_sql_config['user'],
					  $cybsso_sql_config['pass'])
			or die(mysql_error());

		mysql_query('INSERT INTO user SET '.
					"email  = '$email', " .
					"crypt_password = SHA1('$password'), " .
					"firstname = 'firstname', ".
					"lastname = 'lastname'")
			or die(mysql_error());

		$ticket = $this->_TicketCreate->invoke($this->CybSSO, $email, $password);

		$this->assertType('string', $ticket);
	}

	################################################################
	# User

	function testUserGetInfoUnknownUser() {
		$this->setExpectedException('SoapFault');
		$this->CybSSO->UserGetInfo('user1@company.com');
	}

	function testUserGetInfoOK() {
		global $cybsso_sql_config;
		$email = 'user1@company.com';
		$firstname = 'John';
		$lastname = 'Doe';

		mysql_connect($cybsso_sql_config['host'],
					  $cybsso_sql_config['user'],
					  $cybsso_sql_config['pass'])
			or die(mysql_error());

		mysql_query('INSERT INTO user SET '.
					"email  = '$email', " .
					"crypt_password = SHA1('crypt'), " .
					"firstname = '$firstname', ".
					"lastname = '$lastname'")
			or die(mysql_error());

		$user = $this->CybSSO->UserGetInfo($email);

		$this->assertEquals($firstname, $user['firstname']);
		$this->assertEquals($lastname,  $user['lastname']);
		$this->assertEquals($email,     $user['email']);
	}

	function testUserCreateOK() {
		$user = array(
			'email' => 'user1@company.com',
			'password' => 'invalid-password',
		);

		$this->_UserCreate->invoke($this->CybSSO, $user);
	}
}
?>
