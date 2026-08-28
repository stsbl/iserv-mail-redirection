#!/bin/bash

set -e

. /usr/lib/iserv/cfg

# Memory limit for prod environment
FPM_MEMORY_LIMIT="64M"

# Allow for more memory in the dev environment as the re-compiling of the container crashes otherwise
if [ "$DevEnvironment" == "1" ]
then
  FPM_MEMORY_LIMIT="256M"
fi

/usr/lib/iserv/server-php/generate-fpm-config dynamic

cat << EOF
php_value[memory_limit] = ${FPM_MEMORY_LIMIT}
php_value[session.save_path] = "/var/lib/iserv/stsbl-iserv-mail-redirection/sessions"

env["APP_SECRET_FILE"] = "/var/lib/iserv/stsbl-iserv-mail-redirection/pwd/symfony.secret"
env["POSTGRES_VERSION"] = "$(psql iserv postgres -Atc "SHOW SERVER_VERSION;")"
EOF
