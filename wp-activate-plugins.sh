#!/usr/bin/env bash
vendor/wp-cli/wp-cli/bin/wp plugin deactivate --network --all
for D in `find wp-content/plugins/* -type d -maxdepth 0 -printf "%f\n"`
do
    vendor/wp-cli/wp-cli/bin/wp plugin activate --network $D
done
