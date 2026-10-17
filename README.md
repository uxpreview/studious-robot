# Calder University Health: a Drupal CMS demo

A demo site for a fictional academic medical center, built on Drupal CMS. It covers healthcare and higher education in one site: doctors, conditions, locations, clinical trials, degree programs, research labs, events and news, all connected to each other.

It's safe to break. Make save points, roll back, or wipe it and rebuild the whole demo in about a minute.

- **Drupal CMS 2.2.5** (Drupal 11.4) with **Drupal Canvas**, the drag-and-drop page builder
- A custom design system, **Calder**: see [docs/design-system.md](docs/design-system.md)
- 9 content types, 9 listing pages and 35 connected pieces of sample content, all packaged as a site template

## The idea in one sentence

Each person is entered once, and every page that involves them (Find a Doctor, the faculty directory, their trials, lab, programs, talks and news) updates from that one profile.

## The five-minute walkthrough

1. **Find a Doctor:** search "AFib", then filter by language or tick "Accepting new patients".
2. **Dr. Priya Patel's profile:** clinician, professor and researcher in one profile. Scroll to the connections at the bottom.
3. **A condition guide** (Atrial fibrillation): the emergency guidance is up top, and specialists and trials are listed automatically.
4. **A clinical trial:** plain-language question first, eligibility and contact close by.
5. **The faculty directory:** the same people, shown as academics.
6. **Log in and edit:** change Dr. Patel's job title, save, and watch it change on every page. Open the homepage in Canvas and drag in a component.

The site's own **About this demo** page (linked from the banner at the top) tells the same story for clients.

## Open it in your browser (recommended)

Nothing to install. It runs on GitHub Codespaces.

1. On this repo's GitHub page, click the green **Code** button, open the **Codespaces** tab, and click **Create codespace on main**.
2. The first launch takes 5 to 10 minutes while it downloads Drupal and builds the demo. When it's ready, the site opens in a new tab.
   If it doesn't, open the **Ports** tab at the bottom and click the globe icon next to **Drupal site**.
3. To log in, type `ddev login` in the terminal at the bottom and click the link it prints.

Coming back later: go to [github.com/codespaces](https://github.com/codespaces) and open the same codespace.

Things to know:
- A codespace stops itself after 30 minutes of inactivity. Reopening it restarts the site.
- GitHub deletes a codespace that hasn't been opened for 30 days. See [Keeping your work](#keeping-your-work).
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
| Wipe everything and rebuild the Calder demo | `ddev reset` |
| Make your current content the new starting point for `ddev reset` | `ddev save-demo`, then commit |
| Rebuild the design after changing theme files | `ddev build-theme` |
| Try a different Drupal CMS template | `ddev reset haven` (see below) |
| See emails the site sends (forms, password resets) | `ddev mailpit` |
| Stop or start the site | `ddev stop` / `ddev start` |

Save points cover all content and settings. They don't roll back uploaded images.

## Other site templates

`ddev reset <name>` rebuilds the site from any Drupal CMS template, and plain `ddev reset` always brings the Calder demo back.

| Name | What it is |
|---|---|
| `amc_demo` | Calder University Health (default) |
| `byte` | SaaS product marketing site |
| `haven` | Nonprofit: blog, projects, people, newsletter, donations |
| `everbright` | Portfolio, sharp black-and-white design |
| `forma` | Portfolio, warm earthy design |
| `mercury_demo` | Tour of the Mercury theme and its components |
| `dashi` | Food magazine with recipes, in English and Spanish |
| `starter` | Bare basics, bring your own design |
| `blank` | Truly empty |

The first time you use one of the Drupal CMS templates it gets downloaded, which changes `composer.json` and `composer.lock`. Commit those if you want it to stick around.

## Showing it to a client

**From Codespaces:** in the **Ports** tab, right-click **Drupal site**, then choose **Port visibility > Public**. Send the client that address. It only works while your codespace is running. Set it back to Private afterwards.

**From your Mac:** `ddev share` gives you a temporary public link. It needs a free [ngrok](https://ngrok.com) account, or `brew install cloudflared` and then `ddev share --provider=cloudflared`. (Not tested yet in this setup.)

**A permanent link** (like `demo.uxpreview.com`) needs real hosting, such as Pantheon or a small server. That isn't set up yet.

## Keeping your work

- **Changed the content and want to keep it as the demo?** Run `ddev save-demo` and commit the changes in `recipes/amc_demo`. From then on, `ddev reset` rebuilds your version.
- **Just want a backup of everything right now?** `ddev export-db --file=backup.sql.gz` saves the database. `ddev import-db --file=backup.sql.gz` loads it back.

## Things worth exploring

- **Page building:** go to **Pages**, open any page, and you're in Canvas. The **Calder** group in the component library has heroes, sections, mission cards, statistics, callouts and a Person spotlight you can point at any doctor.
- **Structured content:** **Create** in the admin menu shows the forms for people, conditions, trials, programs and so on. Each field is there for a reason, such as "Accepting new patients", which drives the Find a Doctor filter.
- **Listings:** Find a Doctor, the trials finder and the others are Views. **Structure > Views** lets you change their filters and sorting without code.
- **Editorial workflow:** every content type supports drafts, revisions and scheduled publishing.
- **Add-ons:** the dashboard's **Choose recommended add-ons** installs features from the browser, such as Multilingual and the AI Assistant.
- **Code components:** Canvas lets you write your own components (JSX and CSS) in the browser.

## Where things live

| File or folder | What it controls |
|---|---|
| `recipes/amc_demo/` | The Calder demo template: content types, views and all sample content |
| `web/themes/custom/calder/` | The Calder design: colors, type, components, page templates |
| `web/modules/custom/calder_demo/` | Site logic: connections between content, search keywords, the Person spotlight |
| `docs/design-system.md` | The design system, explained |
| `docs/amc-demo-brief.md` | The original brief for the demo |
| `.ddev/commands/web/` | The `reset`, `save-demo` and `build-theme` commands |
| `.ddev/config.yaml` | Local setup: site name, web address, what runs on start |
| `.devcontainer/` | Codespaces setup |
| `web/sites/default/settings.php` | Drupal settings. The block at the bottom is specific to this sandbox |
| `composer.json` | The list of Drupal add-ons and their versions |

## Help

- [Drupal CMS user guide](https://www.drupal.org/docs/drupal-cms)
- [DDEV docs](https://docs.ddev.com)
