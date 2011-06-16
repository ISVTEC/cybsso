#
# Copyright (C) 2010-2011 Cyril Bouthors <cyril@bouthors.org>
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.
#

DESTDIR=/usr/local

all: test doc;

include autobuild.mk

.PHONY:. doc test;

doc:
	$(MAKE) -C $@

clean:
	$(MAKE) -C doc $@

install:
	mkdir -p $(DESTDIR)/etc/cybsso
	cp etc/apache.conf etc/htpasswd $(DESTDIR)/etc/cybsso
	chmod 600 $(DESTDIR)/etc/cybsso/htpasswd

	mkdir -p $(DESTDIR)/usr/share/doc/cybsso
	cp etc/config.php.skel $(DESTDIR)/usr/share/doc/cybsso

	mkdir -p $(DESTDIR)/usr/share/cybsso/www
	cp -r www/* $(DESTDIR)/usr/share/cybsso/www

	mkdir -p $(DESTDIR)/usr/share/cybsso/api
	cp api/* $(DESTDIR)/usr/share/cybsso/api

	mkdir -p $(DESTDIR)/usr/share/php/cybsso
	cp lib/*.php $(DESTDIR)/usr/share/php/cybsso

	$(MAKE) -C doc install

test:
	$(MAKE) -C test
