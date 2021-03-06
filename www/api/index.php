<?php
//
// Copyright (C) 2010-2011 Cyril Bouthors <cyril@bouthors.org>
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
require('cybsso/CybSSO.php');

try {
	$data = file_get_contents('php://input');
	$server = new SoapServer(null, array('uri' => 'ns1'));

	$server->setClass('CybSSO');
	$server->setPersistence(SOAP_PERSISTENCE_SESSION);
	$server->handle($data);
}
catch(SoapFault $fault) {
}

?>
