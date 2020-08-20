#
# Copyright (C) 2010 Cyril Bouthors <cyril@bouthors.org>
#
# This program is free software: you can redistribute it and/or modify it under
# the terms of the GNU General Public License as published by the Free Software
# Foundation, either version 3 of the License, or (at your option) any later
# version.
#
# This program is distributed in the hope that it will be useful, but WITHOUT
# ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
# FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License along with
# this program. If not, see <http://www.gnu.org/licenses/>.
#

test: all;

all: clean
#	Make sure unit tests are not ran as 'root'
	@if [ $$(id -u) = 0 ];\
	then \
		echo "ERROR: Unit tests are not working as root because the read-only file checks are bypassed"; \
		exit 1; \
	fi
	phpunit --configuration config.xml --coverage-html report CybSSOTest.php
	@echo "Report is here: file://$$PWD/report/index.html"
	@./assert-code-coverage-level report/CybSSO.php.html

clean:
	@rm -rf report
