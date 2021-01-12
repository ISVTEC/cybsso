# Copyright (C) Cyril Bouthors - All Rights Reserved
# Unauthorized copying of this file, via any medium is strictly prohibited
# Proprietary and confidential
# Written by Cyril Bouthors <cyril@boutho.rs>, 2003-2021

DESTDIR=/usr/local
RSYNC=rsync -avz --del

ALL= html/index.html

all: $(ALL);

html/index.html: ../lib/CybSSO.php
	doxygen doxygen.conf

clean:
	rm -rf html

install: all
	mkdir -p $(DESTDIR)/usr/share/doc/cybsso
	$(RSYNC) html $(DESTDIR)/usr/share/doc/cybsso
