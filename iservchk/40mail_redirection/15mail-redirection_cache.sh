#!/bin/bash
FN_CACHEDIR="/var/lib/iserv/stsbl-iserv-mail-redirection/app/cachedir"

[ -f "$FN_CACHEDIR" ] || exit 0

CACHE_DIR="$(< "$FN_CACHEDIR")"

[ -n "$CACHE_DIR" ] || exit 0
[[ "$CACHE_DIR" =~ ^/var/cache/iserv/stsbl-iserv-mail-redirection/app/ ]] || exit 0

cat<<EOT
MkDir 0755 root:root /var/cache/iserv/stsbl-iserv-mail-redirection
MkDir 2770 stsbl-iserv-mail-redirection:stsbl-iserv-mail-redirection /var/cache/iserv/stsbl-iserv-mail-redirection/app
MkDir 2770 stsbl-iserv-mail-redirection:stsbl-iserv-mail-redirection $CACHE_DIR
MkDir 2770 stsbl-iserv-mail-redirection:stsbl-iserv-mail-redirection $CACHE_DIR/{pools,templates}

EOT
