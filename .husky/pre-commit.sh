#!/usr/bin/env sh
. "$(dirname -- "$0")/_/husky.sh"
cd wp-content/themes/wp-rock
yarn lint-staged
