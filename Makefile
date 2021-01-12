# Copyright (C) Cyril Bouthors - All Rights Reserved
# Unauthorized copying of this file, via any medium is strictly prohibited
# Proprietary and confidential
# Written by Cyril Bouthors <cyril@boutho.rs>, 2003-2021

DESTDIR=/usr/local

all:
#	@if [ $$(phpunit --version|head -1| cut -c9-9) -lt 5 ]; then echo "Requires PHPUnit 5 or greater (https://phpunit.de/getting-started/phpunit-5.html)"; exit 1; fi;
#	$(MAKE) -C test
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
