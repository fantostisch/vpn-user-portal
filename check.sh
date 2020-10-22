#!/bin/bash
set -e
set -x

psalm
diff_highlight="/usr/share/doc/git/contrib/diff-highlight/diff-highlight"
php-cs-fixer fix --dry-run --diff --diff-format udiff | "$diff_highlight" | colordiff
php-cs-fixer fix --dry-run -q --stop-on-violation
vendor/bin/phpunit
echo "Success"

