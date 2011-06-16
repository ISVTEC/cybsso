-- -*- sql -*-
--
-- Copyright (C) 2011 Cyril Bouthors <cyril@bouthors.org>
--
-- This program is free software: you can redistribute it and/or modify it under
-- the terms of the GNU General Public License as published by the Free Software
-- Foundation, either version 3 of the License, or (at your option) any later
-- version.
--
-- This program is distributed in the hope that it will be useful, but WITHOUT
-- ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
-- FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
-- details.
--
-- You should have received a copy of the GNU General Public License along with
-- this program. If not, see <http://www.gnu.org/licenses/>.
--

CREATE TABLE user (
  email VARCHAR(128) NOT NULL,
  crypt_password VARCHAR(128) NOT NULL,
  language VARCHAR(8) NOT NULL DEFAULT 'en_US',
  firstname VARCHAR(32) NOT NULL,
  lastname VARCHAR(32) NOT NULL,
  ticket VARCHAR(128),
  ticket_expiration_date INT DEFAULT 0 NOT NULL,
  PRIMARY KEY (email),
  KEY (crypt_password),
  KEY (ticket)
) ENGINE=MyISAM CHARSET=utf8;
