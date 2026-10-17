# Calder design system

The design behind Calder University Health, the fictional academic medical center in this demo. It explains each decision and where it lives in the code.

## Concept: held in balance

Alexander Calder's mobiles are separate pieces held in balance by thin wires. An academic medical center works the same way: patient care, teaching and research are separate, but they share people and depend on each other.

The site's structure makes that literal. Each doctor, condition, trial and program is entered once, and every page shows what connects to it. The design makes it visible:

- **Color marks the mission.** Red is care, blue is learn and ochre is discover, so color always tells you where you are.
- **Wires show connections.** The connections strip on a profile, the person spotlight and the logo all use the mobile's wire-and-shape language.
- **Shapes stand in for roles.** A doctor's avatar carries one colored shape per role. Dr. Patel, who treats patients, teaches and runs a lab, carries all three.

## Color

All tokens are in `web/themes/custom/calder/src/theme.css`. Every text color meets WCAG 2.2 AA (4.5:1) on every background it's used on. Most meet AAA.

| Token | Hex | Used for | Contrast |
|---|---|---|---|
| `ink` | #16181D | Text, primary buttons | 16.6:1 on paper |
| `ink-soft` | #4A4F59 | Secondary text | 7.7:1 on paper |
| `ink-muted` | #5F6470 | Icons, tertiary text | 5.5:1 on paper |
| `paper` | #FAF7F2 | Page background | |
| `sand` | #F2EDE4 | Panels, chips, monograms | |
| `line` | #E3DCCF | Decorative hairlines | |
| `line-strong` | #8F8679 | Form field borders | 3:1 for UI boundaries |
| `night` | #14161B | Footer and dark bands | |
| `care` | #C23A2B | Patient care | 5.0:1 on paper; white on it 5.3:1 |
| `learn` | #2346B0 | Education | 7.6:1 on paper; white on it 8.2:1 |
| `discover` | #EBAA2A | Research (fills only) | Ink on it 8.7:1 |
| `discover-deep` | #7E5300 | Research text | 6.3:1 on paper |
| `ok` | #1D7448 | "Accepting new patients", "Recruiting" | 5.4:1 on paper |

Each mission color has a light tint (`care-tint`, `learn-tint`, `discover-tint`) for panels such as the emergency callout or the trial eligibility box. Inside dark (`.dark`) sections the tokens switch to lighter versions that keep the same contrast against `night`.

## Type

| Role | Typeface | Why |
|---|---|---|
| Headlines | **Newsreader** (Production Type) | An editorial serif with academic gravitas. Its optical-size axis tightens the letterforms at large sizes, so big headlines stay crisp. |
| Body and interface | **Atkinson Hyperlegible Next** (Braille Institute) | Designed for readers with low vision: distinct letter shapes, a slashed zero and clear punctuation. A good fit for a healthcare audience. |

Both are open-source (SIL Open Font License) and served from the theme, with no third-party font requests. Body text is 18px. Headline sizes are fluid (`text-display`, `text-title`, `text-headline`, `text-subhead` in `src/main.css`), so they scale smoothly between phone and desktop.

## Illustration

There's no stock photography. The illustrations are **generated mobiles**, made by `web/themes/custom/calder/tools/mobiles.py`:

- Each arm's pivot sits where the torque balances (weight × distance is equal on both sides), so every mobile could hang in real life.
- Branches are spaced by the actual width of what hangs from them, so shapes never collide.
- Wires and black shapes use `currentColor`, so the same file works on paper and on the dark footer.
- On the page they sway gently from their hanging point. The motion turns off for anyone with "reduce motion" set.

Run `python3 tools/mobiles.py` from the theme folder to regenerate them. Edit `COMPOSITIONS` to add new ones.

People without a photo get a **monogram avatar** with their role shapes. Every person, news and lab record also has an image field, so real photography can replace any of this without code.

## Components

Components are Drupal single-directory components in `web/themes/custom/calder/components/`.

**Canvas library** (editors drag these onto pages):

| Component | Use |
|---|---|
| Hero | Page-opening headline, intro, buttons and a mobile. Large or compact. |
| Doctor search and quick links | Find a Doctor search box plus top-task links. |
| Quick link | Icon link for a top task (goes inside the above). |
| Section | Full-width band with heading, intro, link, background and 1–4 columns. |
| Mission card | Care, Learn or Discover, with its shape hanging from a wire. |
| Link card | Card linking to any page, with a mission color. |
| Statistic | Big number and label. |
| Callout | Full-width notice, such as when to call 911. |
| Call to action band | Bold colored band with a button and a mobile. |
| Rich text block | Heading and formatted text at reading width. |
| Mobile illustration | Any of the generated mobiles. |
| Status pill | Short status label. |
| Person spotlight (block) | One person shown as a mobile, with each role linking to the related page. |

Live lists (upcoming events, latest news) are Views blocks, also available in the library.

**Internal components** (used by the page templates, hidden from Canvas): avatar, person card, content card, mission marker, logo, site header and site footer.

## Page patterns

- **Header:** demo notice, logo, utility links, mission-colored main navigation, search and a persistent Find a Doctor button. On phones it collapses to a menu.
- **Page header:** breadcrumb, mission marker, serif title, intro, then key facts or actions. An illustration or fact card sits on the right.
- **Connections:** the bottom of every profile, condition, location and department page. A wire with one shape per group links down to the cards. It doubles as in-page navigation.
- **Listings:** filters on the left for finders (doctors, trials, faculty), or above the results for simple listings. The result count is always visible.
- **Cards:** whole-card click targets, but only the title is announced as the link.

## Accessibility

- Automated checks (axe-core, WCAG 2.2 A/AA) report no violations across 23 representative pages.
- No horizontal scrolling at 390px wide.
- Visible focus rings (3px blue, ochre on dark), 24px minimum touch targets and full-box form fields with 3:1 borders.
- The emergency guidance on condition pages is a labelled note near the top, never buried.
- Motion respects "reduce motion".
- Automated checks don't catch everything. A manual screen reader pass is still worth doing before showing this as an accessibility example.

## Where to change things

| Change | File |
|---|---|
| Colors, fonts, radius, shadows | `web/themes/custom/calder/src/theme.css` |
| Type scale, base styles, form styles | `web/themes/custom/calder/src/main.css` |
| Illustrations | `web/themes/custom/calder/tools/mobiles.py` |
| A component's markup | `web/themes/custom/calder/components/<name>/` |
| A page type's layout | `web/themes/custom/calder/templates/content/node--<type>--full.html.twig` |
| What data reaches the templates | `web/themes/custom/calder/src/Hook/CalderHooks.php` |

After changing CSS classes or `src/` files, rebuild the CSS with `npm run build` in the theme folder.
