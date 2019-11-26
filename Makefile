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

all:
	@if [ $$(phpunit --version|head -1| cut -c9-9) -lt 5 ]; then echo "Requires PHPUnit 5 or greater (https://phpunit.de/getting-started/phpunit-5.html)"; exit 1; fi;
	$(MAKE) -C test
	$(MAKE) -C doc

include autobuild.mk

.PHONY:. doc test;

doc:
	$(MAKE) -C $@

clean:
	$(MAKE) -C doc $@

install:
	mkdir -p $(DESTDIR)/etc/cybsso
	cp etc/htpasswd $(DESTDIR)/etc/cybsso
	chmod 600 $(DESTDIR)/etc/cybsso/htpasswd

	cp etc/config.php.skel $(DESTDIR)/etc/cybsso/config.php

	mkdir -p $(DESTDIR)/usr/share/cybsso/www
	cp -r www/* $(DESTDIR)/usr/share/cybsso/www

	mkdir -p $(DESTDIR)/usr/share/php/cybsso
	cp lib/*.php $(DESTDIR)/usr/share/php/cybsso

	mkdir -p $(DESTDIR)/usr/share/cybsso/template
	cp template/*.phtml $(DESTDIR)/usr/share/cybsso/template

	cp -r test/schema.sql $(DESTDIR)/usr/share/cybsso

	$(MAKE) -C doc install

test:
	$(MAKE) -C test
