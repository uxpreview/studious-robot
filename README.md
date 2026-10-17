# Drupal CMS Sandbox

A Drupal CMS site for client demos and for exploring what Drupal can do. It's safe to break: you can make save points, roll back, or wipe it and start fresh in about a minute.

- **Drupal CMS 2.2.5** (built on Drupal 11.4)
- Starts from the **Byte** site template: a SaaS-style marketing site with landing pages, a blog, pricing and contact pages, and sample content
- Pages are built with **Drupal Canvas**, the drag-and-drop page builder

## Open it in your browser (recommended)

Nothing to install. It runs on GitHub Codespaces.

1. On this repo's GitHub page, click the green **Code** button, open the **Codespaces** tab, and click **Create codespace on main**.
2. The first launch takes 5 to 10 minutes while it downloads Drupal and builds the site. When it's ready, the site opens in a new tab.
   If it doesn't, open the **Ports** tab at the bottom and click the globe icon next to **Drupal site**.
3. To log in, type `ddev login` in the terminal at the bottom and click the link it prints.

Coming back later: go to [github.com/codespaces](https://github.com/codespaces) and open the same codespace. Your content is still there.

Things to know:
- A codespace stops itself after 30 minutes of inactivity. That's fine; reopening it restarts the site.
- GitHub deletes a codespace that hasn't been opened for 30 days, and the site's content goes with it. See [Keeping your work](#keeping-your-work).
- GitHub's free plan covers roughly 60 hours a month on the default machine size.

## Or run it on your Mac

One-time setup:
1. Install [OrbStack](https://orbstack.dev) (runs the containers; free for personal use) and open it once.
2. In Terminal: `brew install ddev/ddev/ddev`, then `mkcert -install`.
3. Download this repo (`git clone`), then `cd` into the folder.

Then:
- `ddev start` starts the site. The first time also builds it (about 5 minutes).
- `ddev login` opens it, already logged in.

The site lives at https://drupal-sandbox.ddev.site while it's running.

## Everyday commands

Type these in the terminal (Codespaces or Mac).

| You want to | Type |
|---|---|
| Open the site, logged in | `ddev login` |
| Make a save point before trying something | `ddev snapshot --name=before-redesign` |
| Go back to that save point | `ddev snapshot restore before-redesign` |
| Go back to the most recent save point | `ddev snapshot restore --latest` |
| See your save points | `ddev snapshot --list` |
| Wipe everything and start fresh from Byte | `ddev reset` |
| Start fresh from a different template | `ddev reset haven` (see below) |
| See emails the site sends (forms, password resets) | `ddev mailpit` |
| Stop or start the site | `ddev stop` / `ddev start` |

Save points cover all content and settings. They don't roll back uploaded images, so an image you deleted after a save point stays deleted.

## Site templates

`ddev reset <name>` rebuilds the site from any Drupal CMS template. Useful for showing a client something closer to their world.

| Name | What it is |
|---|---|
| `byte` | SaaS product marketing site (default) |
| `haven` | Nonprofit: blog, projects, people, newsletter, donations |
| `everbright` | Portfolio, sharp black-and-white design |
| `forma` | Portfolio, warm earthy design |
| `mercury_demo` | Tour of the Mercury theme and all its components |
| `dashi` | Food magazine with recipes, in English and Spanish |
| `starter` | Bare basics, bring your own design |
| `blank` | Truly empty |

The first time you use a template it gets downloaded, which adds it to `composer.json` and `composer.lock`. Commit those two files if you want the template to stick around.

## Showing it to a client

**From Codespaces:** in the **Ports** tab, right-click **Drupal site**, then choose **Port visibility > Public**. Send the client that address. It only works while your codespace is running, and anyone with the link can view the site (they can't log in without your password). Set it back to Private afterwards.

**From your Mac:** `ddev share` gives you a temporary public link. It needs a free [ngrok](https://ngrok.com) account, or `brew install cloudflared` and then `ddev share --provider=cloudflared` with no account. (Not tested yet in this setup.)

**A permanent link** (like `demo.uxpreview.com`) needs real hosting, such as Pantheon or a small server. That isn't set up yet.

## Keeping your work

Codespaces aren't a safe long-term home for demo content. To keep a copy of the whole site:

- `ddev export-db --file=backup.sql.gz` saves the database to a file. In Codespaces, right-click the file in the sidebar and choose **Download**.
- `ddev import-db --file=backup.sql.gz` loads it back.

For site *structure* (content types, page layouts, menus, settings), `ddev drush config:export` saves it as files in `config/sync`, which can be committed to this repo.

## Things worth exploring

- **Page building:** go to **Pages**, open any page, and you're in Canvas. Drag components in, edit text in place, and preview at phone and tablet sizes.
- **Code components:** Canvas lets you write your own components (JSX and CSS) right in the browser. This is the "how far can we take it" area for design systems.
- **Add-ons:** the dashboard's **Choose recommended add-ons** installs features from the browser: AI Assistant, Multilingual, Accessibility Tools, Mailchimp and Google Tag. **Extend** lets you browse thousands more. The AI Assistant needs an API key from an AI provider.
- **Content modeling:** **Structure > Content types** shows how content is set up, such as blog posts and their fields.
- **Editorial workflow:** blog posts can be saved as drafts, published, unpublished, and scheduled to go live later.
- **Roles:** **People** lets you create users with limited roles (like Content editor) to show what an editor sees.

## Where things live

| File or folder | What it controls |
|---|---|
| `.ddev/config.yaml` | Local setup: site name, web address, what runs on start |
| `.ddev/commands/web/reset` | The `ddev reset` command (default template, site name) |
| `.ddev/commands/host/login` | The `ddev login` command |
| `.devcontainer/` | Codespaces setup |
| `web/sites/default/settings.php` | Drupal settings. The block at the bottom is specific to this sandbox |
| `composer.json` | The list of Drupal add-ons and their versions |
| `web/themes/custom/`, `web/modules/custom/` | Where custom themes and code go |
| `config/sync/` | Exported site structure |

## Help

- [Drupal CMS user guide](https://www.drupal.org/docs/drupal-cms)
- [DDEV docs](https://docs.ddev.com)
