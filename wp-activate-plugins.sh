#!/usr/bin/env bash
vendor/wp-cli/wp-cli/bin/wp plugin deactivate --network --all
vendor/wp-cli/wp-cli/bin/wp plugin activate --network --all
