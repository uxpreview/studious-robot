# Brief: Academic medical center demo

**Status:** planned, not started. Open questions are at the bottom.

## Goal

Turn the sandbox into a fictional academic medical center (AMC) for client demos that cover both healthcare and higher education. This is **not a full AMC site.** It has a few realistic examples of every page type and every listing view, connected to each other so the relationships show.

The idea at the center of the demo: **one person, many roles.** A doctor is entered once and appears in Find a Doctor, the faculty directory, her lab, the trials she leads, the program she directs, and the events she speaks at. Healthcare and university clients both struggle with the same person living in five places that drift out of sync.

## Page types and examples

Examples are spread across three departments so the filters and related-content lists have something to show: **Heart & Vascular**, **Neurology**, and **Children's Health**.

| Page type | Examples | Notes |
|---|---|---|
| Person | 8 | One profile type with roles (clinician, faculty, researcher). Fields: credentials, photo, title and academic rank, departments, specialties, locations, languages, accepting new patients, bio, education, research interests |
| Department | 3 | Heart & Vascular, Neurology, Children's Health. Overview plus auto-listed people, conditions, trials, programs, events |
| Condition | 4 | Atrial fibrillation, heart failure, migraine, congenital heart disease. Plain-language overview, symptoms, treatment, auto-listed specialists and trials |
| Location | 3 | Main Hospital, Riverside Outpatient Center, Children's Pavilion. Address, hours, parking, services, auto-listed providers |
| Clinical trial | 4 | Mixed statuses (recruiting, active, completed). Condition, eligibility, principal investigator, contact |
| Education program | 3 | MD program, Cardiovascular Disease Fellowship, Neuroscience PhD. Program director, length, how to apply, deadlines |
| Research lab | 2 | One clinician-led lab, one PhD-led lab. Lead, members, focus, related trials |
| Event | 4 | Grand Rounds (clinicians), admissions open house (students), community heart screening (public), virtual support group (patients). Mix of upcoming and past |
| News / story | 4 | Patient story, research news, education news (Match Day), hospital news |
| Landing page (Canvas) | 4 | Homepage plus hubs for Patients & Visitors, Education, and Research |
| Basic page | 2 | Visitor information and parking, Billing and insurance |

### People (the thread that ties it together)

1. **Dr. Priya Patel, MD:** cardiologist (heart rhythm), associate professor, fellowship director, leads the Arrhythmia Lab and the AFib trial, speaks at Grand Rounds. *The demo walkthrough follows her.*
2. **Dr. Marcus Reed, MD:** interventional cardiologist, works at two locations
3. **Maria Santos, DNP:** cardiology nurse practitioner (a provider who isn't an MD)
4. **Dr. Elena Ruiz, MD, PhD:** neurologist (headache), professor, leads the migraine trial, speaks Spanish
5. **Dr. James Okafor, MD:** neurosurgeon
6. **Dr. Hannah Kim, MD:** pediatrician, directs the pediatrics rotation in the MD program
7. **Dr. Samuel Brooks, MD:** pediatric cardiologist, in two departments (Heart & Vascular and Children's Health)
8. **Dr. Laila Haddad, PhD:** researcher only, leads a neuroscience lab. Not a clinician, so she isn't in Find a Doctor but is in the faculty directory

## Listing views

| View | What it shows |
|---|---|
| Find a Doctor | Clinicians only. Filters: specialty, department, location, language, accepting new patients. Keyword search |
| Faculty directory | Same people, academic view: rank, department, research interests. Includes researchers |
| Conditions A to Z | Alphabetical list |
| Locations | List with hours and services (map optional) |
| Clinical trials finder | Filters: condition, status, department |
| Programs | Filter by type: degree, fellowship, PhD |
| Events | Upcoming by audience, plus past events |
| News | Filter by mission: patient care, education, research |
| Site search | Everything |

Detail pages also carry automatic related lists: doctors on a department page, specialists and trials on a condition page, providers at a location, and trials, events and news on a person's profile.

Supporting vocabularies: specialty, mission (patient care, education, research), audience (patients, students, clinicians, researchers, community), language, program type.

## The five-minute walkthrough

1. Find a Doctor: filter to cardiology and accepting new patients, then open Dr. Patel.
2. Her profile shows locations, conditions she treats, her trial, her fellowship, and her upcoming talk.
3. The AFib condition page lists her and the trial automatically.
4. The fellowship page shows her as director. Switch to the faculty directory: same person, academic view.
5. Log in as an editor, change her profile once, and show it updated everywhere, with a review step before it publishes.

## How to build it

- Package the demo as our own site template (a recipe tracked in this repo, for example `recipes/amc_demo`, un-ignored in `.gitignore`), with all sample content included. Then `ddev reset amc_demo` rebuilds a clean demo every time. Make it the default once it's ready.
- All content is clearly fictional. Add a site-wide "Demo site: fictional institution" notice so nobody mistakes it for real medical guidance.
- Phases:
  1. Content types, sample content and listing views, using a neutral theme
  2. Look and feel: brand colors and type, then the homepage and hubs in Canvas
  3. Extras: department-level editing permissions with a review step (needs an add-on such as Group), Spanish versions of one or two pages, and an accessibility pass

## Open questions

1. **Name:** fictional and not resembling a real institution. Options: Larkspur University Health, Calder University Medical Center, Ashby Academic Health. Check the pick isn't a real place.
2. **Audience:** which clients is this for, and what's their usual pain (people can't find things, too many editors and no control, accessibility, an aging CMS)? That decides what to polish first.
3. **Look:** restyle Drupal CMS's neutral Mercury theme, or something more custom? Look at `ddev reset mercury_demo` and `ddev reset haven` first.
4. **Photos:** licensed stock photos of people as fictional doctors, or illustrated avatars?
