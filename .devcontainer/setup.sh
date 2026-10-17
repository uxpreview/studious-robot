#!/usr/bin/env bash
# Runs once, when a new Codespace is created.
set -euo pipefail

# Serve the site on fixed ports so Codespaces knows which one to open.
# config.local.yaml only applies to this Codespace and is not committed.
cat > .ddev/config.local.yaml <<'YAML'
host_webserver_port: "8080"
host_https_port: "8443"
YAML
