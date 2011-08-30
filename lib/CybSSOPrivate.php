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

require_once('CybSSO.php');

# Export protected functions as public function
class CybSSOPrivate extends CybSSO {

	function TicketCreate($email = null, $password = null) {
		return $this->_TicketCreate($email, $password);
	}

	function TicketDelete($email = null) {
		return $this->_TicketDelete($email);
	}

	function UserCreate(array $user = array()) {
		return $this->_UserCreate($user);
	}

	function UserUpdate(array $user = array()) {
		return $this->_UserUpdate($user);
	}

	function PasswordRecovery($email = null, $return_url = null) {
		return $this->_PasswordRecovery($email, $return_url);
	}

	function PasswordRecoveryCheckTicket($email = null, $ticket = null) {
		return $this->_PasswordRecoveryCheckTicket($email, $ticket);
	}

	function PasswordReset($email = null, $password = null, $password2 = null) {
		return $this->_PasswordReset($email, $password, $password2);
	}
}

?>
