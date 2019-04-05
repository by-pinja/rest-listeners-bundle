#!/bin/bash
set -e

#
# Note that all the chmod stuff is for users who are using docker-compose within Linux environment. More info in link
# below:
#   https://jtreminio.com/blog/running-docker-containers-as-current-host-user/
#

if [ "$1" = 'tail' ]; then
    composer install

    # Step 8
    chmod -R o+s+w /rest-listeners-bundle
fi

exec "$@"
