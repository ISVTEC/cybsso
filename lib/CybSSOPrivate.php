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

require('CybSSO.php');

class CybSSOPrivate extends CybSSO {

	# Export protected function as public function
	function TicketCreate($email = null, $password = null) {
		return $this->_TicketCreate($email, $password);
	}

	function UserCreate(array $user = array()) {
		return $this->_UserCreate($user);
	}
}

?>
