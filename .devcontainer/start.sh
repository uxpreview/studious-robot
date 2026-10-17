#!/usr/bin/env bash
# Runs every time the Codespace starts (including the first time).
# The first start also installs the site, which takes several minutes.
set -euo pipefail

echo "Waiting for Docker..."
until docker info >/dev/null 2>&1; do sleep 2; done

ddev start -y

echo
echo "Your Drupal site is running. Open the PORTS tab and click the globe next to 'Drupal site',"
echo "or run 'ddev login' in the terminal to open it already logged in."
