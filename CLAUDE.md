@AGENTS.md

## This repo

A Drupal CMS 2.x demo and sandbox site, owned by a designer who doesn't write code day to day. Explain changes in plain language and keep README.md current when commands or setup change.

- Built from the `byte` site template. `extra.drupal-recipe-unpack.on-require` is `false` in composer.json so templates stay installed as packages and `ddev reset` can rebuild from them. Keep it that way.
- `web/sites/default/settings.php` is committed and holds no secrets. DDEV supplies the database settings via `settings.ddev.php`.
- Custom DDEV commands: `.ddev/commands/web/reset` (reinstall from a template) and `.ddev/commands/host/login` (one-time login link).
- Codespaces: `.devcontainer/` runs DDEV on port 8080. settings.php trusts the Codespaces proxy headers when `CODESPACES=true`.

## Next up

Building a fictional academic medical center demo on top of this site. The plan, examples and open questions are in `docs/amc-demo-brief.md`. Read it before starting that work.

## Claude Code cloud sessions

DDEV isn't installed by default and Docker containers can't reach the internet through the session proxy. For a quick preview without DDEV:

```sh
export COMPOSER_ALLOW_SUPERUSER=1
composer install
php vendor/drush/drush/drush.php site:install "$PWD/recipes/byte" --db-url=sqlite://localhost/sites/default/files/.ht.sqlite --account-name=admin --account-pass=admin -y
cd web && php -S 127.0.0.1:8888 .ht.router.php
```

That writes database settings into settings.php; don't commit that change.
