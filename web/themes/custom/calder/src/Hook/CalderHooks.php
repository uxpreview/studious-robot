<?php

declare(strict_types=1);

namespace Drupal\calder\Hook;

use Drupal\calder_demo\Connections;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Menu\MenuActiveTrailInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Calder presentation hooks.
 *
 * Templates in this theme don't dig through Drupal's field render arrays.
 * Instead, preprocess turns each node into a plain `calder` variable (strings,
 * small arrays, URLs) that maps directly onto the theme's components. That keeps
 * the Twig readable and makes the components reusable in Canvas, where props
 * are plain values too.
 */
final class CalderHooks {

  /**
   * Content types designed by this theme.
   */
  public const BUNDLES = ['person', 'department', 'condition', 'location', 'clinical_trial', 'program', 'lab', 'event', 'news'];

  /**
   * Bundle labels shown as card eyebrows.
   */
  private const TYPE_LABELS = [
    'person' => 'Person',
    'department' => 'Department',
    'condition' => 'Condition',
    'location' => 'Location',
    'clinical_trial' => 'Clinical trial',
    'program' => 'Program',
    'lab' => 'Research lab',
    'event' => 'Event',
    'news' => 'News',
  ];

  /**
   * Which mission each content type belongs to, for color coding.
   */
  private const BUNDLE_MISSIONS = [
    'department' => 'care',
    'condition' => 'care',
    'location' => 'care',
    'clinical_trial' => 'discover',
    'lab' => 'discover',
    'program' => 'learn',
    'event' => 'learn',
  ];

  private const ROLE_MISSIONS = [
    'clinician' => 'care',
    'faculty' => 'learn',
    'researcher' => 'discover',
  ];

  private const MISSION_TERMS = [
    'Patient care' => 'care',
    'Education' => 'learn',
    'Research' => 'discover',
  ];

  /**
   * Eyebrow, mission and introduction for each listing page.
   */
  private const LISTING_INTROS = [
    'find_a_doctor' => ['care', 'Patient care', 'Search by name, specialty or condition, or narrow the list by location and language. Every profile shows whether the doctor is taking new patients.'],
    'faculty' => ['learn', 'Education', 'The physicians and scientists who teach at the Calder University School of Medicine and lead its research.'],
    'conditions' => ['care', 'Patient care', 'Plain-language guides to symptoms, tests and treatment, each connected to the specialists who treat the condition.'],
    'locations' => ['care', 'Patient care', 'Our hospital, outpatient center and children’s pavilion, with hours, parking and the services at each.'],
    'clinical_trials' => ['discover', 'Research', 'Studies at Calder test new ways to prevent, find and treat disease. Joining is always your choice, and you can leave at any time.'],
    'programs' => ['learn', 'Education', 'Degree, residency, fellowship and PhD programs at the Calder University School of Medicine.'],
    'events' => ['learn', 'News & Events', 'Talks, open houses, screenings and support groups for patients, students and clinicians.'],
    'news' => ['neutral', 'News & Events', 'Patient stories, research findings and news from across Calder University Health.'],
    'labs' => ['discover', 'Research', 'The research groups behind Calder’s clinical trials and teaching.'],
  ];

  private const STATUS_TONES = [
    'recruiting' => 'ok',
    'not_yet_recruiting' => 'discover',
    'active_not_recruiting' => 'neutral',
    'completed' => 'neutral',
  ];

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly RouteMatchInterface $routeMatch,
    private readonly MenuLinkTreeInterface $menuTree,
    private readonly MenuActiveTrailInterface $activeTrail,
    private readonly DateFormatterInterface $dateFormatter,
    #[Autowire(service: 'service_container')]
    private readonly ContainerInterface $container,
  ) {}

  /**
   * Adds menus and page-level flags.
   *
   * Called from ThemeHooks::preprocessPage(), because a theme may implement
   * each hook only once.
   */
  public function addPageVariables(array &$variables): void {
    $variables['calder_menus'] = [
      'main' => $this->menu('main', 1),
      'utility' => $this->menu('utility', 1),
      'footer' => $this->menu('footer', 2),
    ];
    $variables['#cache']['contexts'][] = 'route.menu_active_trails:main';
    $variables['#cache']['tags'][] = 'config:system.menu.main';
    $variables['#cache']['tags'][] = 'config:system.menu.utility';
    $variables['#cache']['tags'][] = 'config:system.menu.footer';

    // Designed content types print their own page header.
    $node = $this->routeMatch->getParameter('node');
    $variables['calder_designed_page'] = $this->routeMatch->getRouteName() === 'entity.node.canonical'
      && $node instanceof NodeInterface
      && in_array($node->bundle(), self::BUNDLES, TRUE);
    $variables['calder_path'] = '/' . \Drupal::service('extension.list.theme')->getPath('calder');

    // Listing pages get an eyebrow and an introduction under the title.
    $view_id = $this->routeMatch->getParameter('view_id');
    $variables['calder_intro'] = self::LISTING_INTROS[$view_id] ?? NULL;
  }

  /**
   * Builds the `calder` variable for designed content types.
   */
  #[Hook('preprocess_node')]
  public function preprocessNode(array &$variables): void {
    $node = $variables['node'];
    if (!$node instanceof NodeInterface || !in_array($node->bundle(), self::BUNDLES, TRUE)) {
      return;
    }
    $view_mode = $variables['view_mode'];
    // Type-specific values (such as a story's own mission) override the base.
    $method = 'data' . str_replace('_', '', ucwords($node->bundle(), '_'));
    $data = array_merge($this->base($node), $this->{$method}($node, $view_mode === 'full'));

    if ($node->bundle() !== 'person') {
      $data['card'] = $this->cardProps($data);
    }
    if ($view_mode === 'full') {
      $data['connections'] = $this->connections($node);
      // Connections come from other nodes, so any content change can affect them.
      $variables['#cache']['tags'][] = 'node_list';
    }
    $variables['calder'] = $data;
  }

  /**
   * Fields every node shares.
   */
  private function base(NodeInterface $node): array {
    return [
      'bundle' => $node->bundle(),
      'type_label' => self::TYPE_LABELS[$node->bundle()],
      'title' => $node->label(),
      'url' => $node->toUrl()->toString(),
      'summary' => $this->value($node, 'field_summary'),
      'mission' => self::BUNDLE_MISSIONS[$node->bundle()] ?? 'neutral',
      'body' => $this->text($node, 'field_body'),
      'image' => $this->image($node, 'field_image'),
    ];
  }

  private function dataPerson(NodeInterface $node, bool $full): array {
    $roles = [];
    foreach ($node->get('field_roles') as $item) {
      $roles[] = [
        'key' => $item->value,
        'label' => $this->allowedLabel($node, 'field_roles', $item->value),
        'mission' => self::ROLE_MISSIONS[$item->value],
      ];
    }
    $keys = array_column($roles, 'key');
    $first = $this->value($node, 'field_first_name');
    $last = $this->value($node, 'field_last_name');
    $credentials = $this->value($node, 'field_credentials');
    $is_doctor = (bool) array_intersect(['MD', 'PhD', 'DO', 'DNP'], array_map('trim', explode(',', (string) $credentials)));
    $rank = $this->value($node, 'field_academic_rank');

    $education = [];
    foreach ($this->values($node, 'field_education') as $line) {
      [$label, $detail] = array_pad(explode('|', $line, 2), 2, '');
      $education[] = ['label' => $label, 'value' => $detail];
    }

    return [
      // Clinicians are color coded as care, researchers-only as discover.
      'mission' => $roles[0]['mission'] ?? 'neutral',
      'first_name' => $first,
      'last_name' => $last,
      'name' => $node->label(),
      'display_name' => $node->label() . ($credentials ? ', ' . $credentials : ''),
      'formal_name' => ($is_doctor ? 'Dr. ' : '') . $last,
      'credentials' => $credentials,
      'initials' => mb_substr((string) $first, 0, 1) . mb_substr((string) $last, 0, 1),
      'pronouns' => $this->value($node, 'field_pronouns'),
      'job_title' => $this->value($node, 'field_job_title'),
      'academic_title' => $this->value($node, 'field_academic_title'),
      'academic_rank' => $rank && $rank !== 'none' ? $this->allowedLabel($node, 'field_academic_rank', $rank) : NULL,
      'roles' => $roles,
      'is_clinician' => in_array('clinician', $keys, TRUE),
      'departments' => $this->links($node, 'field_departments'),
      'specialties' => $this->labels($node, 'field_specialties'),
      'conditions' => $this->links($node, 'field_conditions'),
      'locations' => $this->links($node, 'field_locations', ['field_address']),
      'languages' => $this->labels($node, 'field_languages'),
      'accepting' => in_array('clinician', $keys, TRUE) ? (bool) $this->value($node, 'field_accepting_new') : NULL,
      'phone' => $this->value($node, 'field_phone') ?: $this->firstLocationPhone($node),
      'quote' => $this->value($node, 'field_pull_quote'),
      'education' => $education,
      'research_interests' => $this->values($node, 'field_research_interests'),
      'photo' => $this->image($node, 'field_photo'),
    ];
  }

  private function dataDepartment(NodeInterface $node, bool $full): array {
    return [
      'motif' => $this->value($node, 'field_motif') ?: 'home',
      'phone' => $this->value($node, 'field_phone'),
      'highlights' => $this->pairs($node, 'field_highlights'),
    ];
  }

  private function dataCondition(NodeInterface $node, bool $full): array {
    return [
      'also_called' => $this->values($node, 'field_also_called'),
      'symptoms' => $this->text($node, 'field_symptoms'),
      'diagnosis' => $this->text($node, 'field_diagnosis'),
      'treatment' => $this->text($node, 'field_treatment'),
      'seek_care' => $this->value($node, 'field_seek_care'),
      'departments' => $this->links($node, 'field_departments'),
      'motif' => $this->departmentMotif($node),
    ];
  }

  private function dataLocation(NodeInterface $node, bool $full): array {
    $type = $this->value($node, 'field_location_type');
    return [
      'location_type' => $type,
      'location_type_label' => $type ? $this->allowedLabel($node, 'field_location_type', $type) : NULL,
      'address' => $this->value($node, 'field_address'),
      'phone' => $this->value($node, 'field_phone'),
      'hours' => $this->values($node, 'field_hours'),
      'parking' => $this->value($node, 'field_parking'),
      'services' => $this->values($node, 'field_services'),
      'amenities' => $this->values($node, 'field_amenities'),
      'departments' => $this->links($node, 'field_departments'),
      'motif' => match ($type) {
        'childrens' => 'child',
        'outpatient' => 'care',
        default => 'heart',
      },
    ];
  }

  private function dataClinicalTrial(NodeInterface $node, bool $full): array {
    $status = $this->value($node, 'field_trial_status');
    $phase = $this->value($node, 'field_phase');
    $investigator = $node->get('field_investigator')->entity;
    return [
      'protocol_id' => $this->value($node, 'field_protocol_id'),
      'official_title' => $this->value($node, 'field_official_title'),
      'status' => $status,
      'status_label' => $status ? $this->allowedLabel($node, 'field_trial_status', $status) : NULL,
      'status_tone' => self::STATUS_TONES[$status] ?? 'neutral',
      'phase_label' => $phase && $phase !== 'na' ? $this->allowedLabel($node, 'field_phase', $phase) : NULL,
      'conditions' => $this->links($node, 'field_conditions'),
      'departments' => $this->links($node, 'field_departments'),
      'locations' => $this->links($node, 'field_locations'),
      'investigator' => $investigator instanceof NodeInterface ? $this->personSummary($investigator) : NULL,
      'age_range' => $this->value($node, 'field_age_range'),
      'eligibility' => $this->text($node, 'field_eligibility'),
      'what_to_expect' => $this->text($node, 'field_what_to_expect'),
      'contact' => [
        'name' => $this->value($node, 'field_contact_name'),
        'email' => $this->value($node, 'field_contact_email'),
        'phone' => $this->value($node, 'field_phone'),
      ],
    ];
  }

  private function dataProgram(NodeInterface $node, bool $full): array {
    $type = $this->value($node, 'field_program_type');
    $director = $node->get('field_director')->entity;
    return [
      'program_type' => $type,
      'program_type_label' => $type ? $this->allowedLabel($node, 'field_program_type', $type) : NULL,
      'credential' => $this->value($node, 'field_credential'),
      'duration' => $this->value($node, 'field_duration'),
      'deadline' => $this->value($node, 'field_deadline'),
      'highlights' => $this->pairs($node, 'field_highlights'),
      'director' => $director instanceof NodeInterface ? $this->personSummary($director) : NULL,
      'departments' => $this->links($node, 'field_departments'),
      'how_to_apply' => $this->text($node, 'field_how_to_apply'),
    ];
  }

  private function dataLab(NodeInterface $node, bool $full): array {
    $lead = $node->get('field_lead')->entity;
    $members = [];
    foreach ($node->get('field_members')->referencedEntities() as $member) {
      $members[] = $this->personSummary($member);
    }
    return [
      'lead' => $lead instanceof NodeInterface ? $this->personSummary($lead) : NULL,
      'members' => $members,
      'other_members' => $this->value($node, 'field_other_members'),
      'research_focus' => $this->values($node, 'field_research_focus'),
      'recent_work' => $this->values($node, 'field_recent_work'),
      'departments' => $this->links($node, 'field_departments'),
    ];
  }

  private function dataEvent(NodeInterface $node, bool $full): array {
    $item = $node->get('field_event_date')->first();
    $start = $item?->start_date;
    $end = $item?->end_date;
    $format = $this->value($node, 'field_event_format');
    $speakers = [];
    foreach ($node->get('field_speakers')->referencedEntities() as $speaker) {
      $speakers[] = $this->personSummary($speaker);
    }
    $tz = date_default_timezone_get();
    $start_ts = $start?->getTimestamp();
    $end_ts = $end?->getTimestamp();
    $audience = $this->labels($node, 'field_audience');
    return [
      // Events for patients and the public read as care; the rest as learn.
      'mission' => array_intersect($audience, ['Patients & families', 'Community']) ? 'care' : 'learn',
      'date' => $start_ts ? [
        'month' => $this->dateFormatter->format($start_ts, 'custom', 'M', $tz),
        'day' => $this->dateFormatter->format($start_ts, 'custom', 'j', $tz),
        'weekday' => $this->dateFormatter->format($start_ts, 'custom', 'l', $tz),
        'long' => $this->dateFormatter->format($start_ts, 'custom', 'l, F j, Y', $tz),
        'time' => $this->timeRange($start_ts, $end_ts, $tz),
        'iso' => $this->dateFormatter->format($start_ts, 'custom', 'c', $tz),
        'is_past' => $end_ts && $end_ts < \Drupal::time()->getRequestTime(),
      ] : NULL,
      'format' => $format,
      'format_label' => $format ? $this->allowedLabel($node, 'field_event_format', $format) : NULL,
      'audience' => $audience,
      'location' => $this->links($node, 'field_locations', ['field_address'])[0] ?? NULL,
      'venue' => $this->value($node, 'field_venue'),
      'speakers' => $speakers,
      'credit' => $this->value($node, 'field_credit'),
      'cost' => $this->value($node, 'field_cost'),
      'registration_label' => $this->value($node, 'field_registration_label'),
      'departments' => $this->links($node, 'field_departments'),
    ];
  }

  private function dataNews(NodeInterface $node, bool $full): array {
    $mission_term = $node->get('field_mission')->entity;
    $type = $this->value($node, 'field_story_type');
    $people = [];
    foreach ($node->get('field_people')->referencedEntities() as $person) {
      $people[] = $this->personSummary($person);
    }
    return [
      'mission' => $mission_term ? (self::MISSION_TERMS[$mission_term->label()] ?? 'neutral') : 'neutral',
      'mission_label' => $mission_term?->label(),
      'story_type_label' => $type ? $this->allowedLabel($node, 'field_story_type', $type) : NULL,
      'date' => $this->dateFormatter->format($node->getCreatedTime(), 'custom', 'F j, Y'),
      'date_iso' => $this->dateFormatter->format($node->getCreatedTime(), 'custom', 'c'),
      'quote' => $this->value($node, 'field_pull_quote'),
      'quote_attribution' => $this->value($node, 'field_quote_attribution'),
      // The pull quote sits between the opening paragraphs and the rest.
      'body_parts' => $this->splitBody($node, 2),
      'people' => $people,
      'departments' => $this->links($node, 'field_departments'),
    ];
  }

  /**
   * Passes the view's layout (from its CSS class) to the views templates.
   */
  #[Hook('preprocess_views_view')]
  public function preprocessViewsView(array &$variables): void {
    $variables['calder_layout'] = $this->viewLayout($variables['view']);
  }

  #[Hook('preprocess_views_view_unformatted')]
  public function preprocessViewsViewUnformatted(array &$variables): void {
    $variables['calder_layout'] = $this->viewLayout($variables['view']);
  }

  private function viewLayout($view): string {
    $class = (string) $view->display_handler->getOption('css_class');
    return preg_match('/calder-view--([a-z-]+)/', $class, $m) ? $m[1] : 'list';
  }

  /**
   * Maps a node's data onto the content card's props.
   */
  private function cardProps(array $d): array {
    $card = [
      'mission' => $d['mission'],
      'eyebrow' => $d['type_label'],
      'title' => $d['title'],
      'url' => $d['url'],
      'text' => $d['summary'],
      'meta' => [],
    ];
    switch ($d['bundle']) {
      case 'clinical_trial':
        $card['status'] = ['label' => $d['status_label'], 'tone' => $d['status_tone']];
        if ($d['investigator']) {
          $card['meta'][] = ['icon' => 'user', 'text' => 'Led by ' . $d['investigator']['display_name']];
        }
        if ($d['age_range']) {
          $card['meta'][] = ['icon' => 'users', 'text' => 'Ages ' . $d['age_range']];
        }
        $card['meta'][] = ['icon' => 'identification-card', 'text' => implode(' · ', array_filter([$d['protocol_id'], $d['phase_label']]))];
        break;

      case 'program':
        $card['eyebrow'] = $d['program_type_label'];
        $card['meta'][] = ['icon' => 'graduation-cap', 'text' => implode(' · ', array_filter([$d['credential'], $d['duration']]))];
        if ($d['deadline']) {
          $card['meta'][] = ['icon' => 'calendar-check', 'text' => 'Apply by ' . $d['deadline']];
        }
        break;

      case 'event':
        $card['eyebrow'] = $d['format_label'];
        $card['date'] = $d['date'];
        if ($d['date']) {
          $card['meta'][] = ['icon' => 'clock', 'text' => $d['date']['time']];
        }
        $where = $d['location']['title'] ?? $d['venue'];
        if ($where) {
          $card['meta'][] = ['icon' => $d['format'] === 'virtual' ? 'video-camera' : 'map-pin', 'text' => $where];
        }
        if (!empty($d['date']['is_past'])) {
          $card['status'] = ['label' => 'Past event', 'tone' => 'neutral'];
        }
        break;

      case 'news':
        $card['eyebrow'] = $d['story_type_label'];
        $card['meta'][] = ['icon' => 'calendar-blank', 'text' => $d['date']];
        break;

      case 'lab':
        if ($d['lead']) {
          $card['meta'][] = ['icon' => 'user', 'text' => 'Led by ' . $d['lead']['display_name']];
        }
        break;

      case 'location':
        $card['eyebrow'] = $d['location_type_label'];
        $card['meta'][] = ['icon' => 'map-pin', 'text' => $d['address']];
        if ($d['phone']) {
          $card['meta'][] = ['icon' => 'phone', 'text' => $d['phone']];
        }
        break;

      case 'condition':
        if ($d['also_called']) {
          $card['meta'][] = ['icon' => 'quotes', 'text' => 'Also called ' . implode(', ', array_slice($d['also_called'], 0, 2))];
        }
        break;

      case 'department':
        if ($d['phone']) {
          $card['meta'][] = ['icon' => 'phone', 'text' => $d['phone']];
        }
        break;
    }
    if (in_array($d['bundle'], ['news', 'lab'], TRUE)) {
      $card['image'] = $d['image'];
    }
    return $card;
  }

  /**
   * Connections for a full page, as card render arrays grouped by type.
   */
  private function connections(NodeInterface $node): array {
    if (!$this->container->has('calder_demo.connections')) {
      return [];
    }
    /** @var \Drupal\calder_demo\Connections $service */
    $service = $this->container->get('calder_demo.connections');
    $builder = $this->entityTypeManager->getViewBuilder('node');
    $groups = [];
    foreach ($service->forNode($node) as $key => $group) {
      $first = reset($group['items']);
      $groups[] = [
        'key' => $key,
        'label' => $group['label'],
        'count' => count($group['items']),
        'bundle' => $first->bundle(),
        'mission' => self::BUNDLE_MISSIONS[$first->bundle()] ?? 'neutral',
        'cards' => $builder->viewMultiple($group['items'], 'card'),
      ];
    }
    return $groups;
  }

  /**
   * A compact person summary for bylines, directors, speakers and leads.
   */
  private function personSummary(NodeInterface $person): array {
    $first = $this->value($person, 'field_first_name');
    $last = $this->value($person, 'field_last_name');
    $credentials = $this->value($person, 'field_credentials');
    return [
      'name' => $person->label(),
      'display_name' => $person->label() . ($credentials ? ', ' . $credentials : ''),
      'initials' => mb_substr((string) $first, 0, 1) . mb_substr((string) $last, 0, 1),
      'job_title' => $this->value($person, 'field_job_title'),
      'url' => $person->toUrl()->toString(),
      'roles' => array_map(fn($i) => self::ROLE_MISSIONS[$i->value], iterator_to_array($person->get('field_roles'))),
      'photo' => $this->image($person, 'field_photo'),
    ];
  }

  /**
   * The illustration motif of a node's first department.
   */
  private function departmentMotif(NodeInterface $node): string {
    $department = $node->hasField('field_departments') ? $node->get('field_departments')->entity : NULL;
    return $department instanceof NodeInterface ? ($this->value($department, 'field_motif') ?: 'care') : 'care';
  }

  /**
   * Splits rich text after the Nth paragraph, for inserting a pull quote.
   *
   * @return array{0: ?array, 1: ?array}
   *   Two processed_text render arrays: the lead and the rest.
   */
  private function splitBody(NodeInterface $node, int $paragraphs): array {
    if (!$node->hasField('field_body') || $node->get('field_body')->isEmpty()) {
      return [NULL, NULL];
    }
    $item = $node->get('field_body')->first();
    $parts = preg_split('#(?<=</p>)#', $item->value, $paragraphs + 1);
    $lead = implode('', array_slice($parts, 0, $paragraphs));
    $rest = implode('', array_slice($parts, $paragraphs));
    $wrap = fn(string $html) => trim($html) === '' ? NULL : ['#type' => 'processed_text', '#text' => $html, '#format' => $item->format];
    return [$wrap($lead), $wrap($rest)];
  }

  private function firstLocationPhone(NodeInterface $node): ?string {
    $location = $node->get('field_locations')->entity;
    return $location instanceof NodeInterface ? $this->value($location, 'field_phone') : NULL;
  }

  /**
   * Builds a menu as [{title, url, active, children}].
   */
  private function menu(string $name, int $depth): array {
    $parameters = (new MenuTreeParameters())->setMaxDepth($depth)->onlyEnabledLinks();
    $parameters->setActiveTrail($this->activeTrail->getActiveTrailIds($name));
    $tree = $this->menuTree->load($name, $parameters);
    $tree = $this->menuTree->transform($tree, [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ]);
    $build = function (array $tree) use (&$build): array {
      $items = [];
      foreach ($tree as $element) {
        if (!$element->access?->isAllowed()) {
          continue;
        }
        $items[] = [
          'title' => $element->link->getTitle(),
          'url' => $element->link->getUrlObject()->toString(),
          'active' => $element->inActiveTrail,
          'children' => $element->subtree ? $build($element->subtree) : [],
        ];
      }
      return $items;
    };
    return $build($tree);
  }

  // ---------------------------------------------------------------------------
  // Small field helpers.
  // ---------------------------------------------------------------------------

  private function value(NodeInterface $node, string $field): mixed {
    return $node->hasField($field) && !$node->get($field)->isEmpty() ? $node->get($field)->value : NULL;
  }

  private function values(NodeInterface $node, string $field): array {
    if (!$node->hasField($field)) {
      return [];
    }
    return array_values(array_filter(array_map(fn($i) => $i->value, iterator_to_array($node->get($field)))));
  }

  private function pairs(NodeInterface $node, string $field): array {
    return array_map(function (string $line): array {
      [$value, $label] = array_pad(explode('|', $line, 2), 2, '');
      return ['value' => $value, 'label' => $label];
    }, $this->values($node, $field));
  }

  private function text(NodeInterface $node, string $field): ?array {
    if (!$node->hasField($field) || $node->get($field)->isEmpty()) {
      return NULL;
    }
    $item = $node->get($field)->first();
    return ['#type' => 'processed_text', '#text' => $item->value, '#format' => $item->format];
  }

  private function labels(NodeInterface $node, string $field): array {
    if (!$node->hasField($field)) {
      return [];
    }
    return array_map(fn($e) => $e->label(), $node->get($field)->referencedEntities());
  }

  private function links(NodeInterface $node, string $field, array $extra = []): array {
    if (!$node->hasField($field)) {
      return [];
    }
    $links = [];
    foreach ($node->get($field)->referencedEntities() as $entity) {
      $link = ['title' => $entity->label(), 'url' => $entity->toUrl()->toString()];
      foreach ($extra as $extra_field) {
        $link[str_replace('field_', '', $extra_field)] = $entity instanceof NodeInterface ? $this->value($entity, $extra_field) : NULL;
      }
      $links[] = $link;
    }
    return $links;
  }

  private function allowedLabel(NodeInterface $node, string $field, string $key): string {
    $allowed = $node->get($field)->getFieldDefinition()->getFieldStorageDefinition()->getSetting('allowed_values');
    return (string) ($allowed[$key] ?? $key);
  }

  /**
   * Image props from a media reference: {src, alt, width, height}.
   */
  private function image(NodeInterface $node, string $field): ?array {
    if (!$node->hasField($field) || $node->get($field)->isEmpty()) {
      return NULL;
    }
    $media = $node->get($field)->entity;
    $source = $media?->get('field_media_image')->first();
    $file = $source?->entity;
    if (!$file) {
      return NULL;
    }
    return [
      'src' => \Drupal::service('file_url_generator')->generateString($file->getFileUri()),
      'alt' => $source->alt ?? '',
      'width' => $source->width,
      'height' => $source->height,
    ];
  }

  /**
   * Formats a time range in AP style: "9 a.m.–1 p.m.", "10–11 a.m.", "noon–1 p.m.".
   */
  private function timeRange(int $start, ?int $end, string $tz): string {
    $parts = function (int $ts) use ($tz): array {
      $hour = (int) $this->dateFormatter->format($ts, 'custom', 'G', $tz);
      $minutes = $this->dateFormatter->format($ts, 'custom', 'i', $tz);
      if ($hour === 12 && $minutes === '00') {
        return ['noon', ''];
      }
      $clock = $this->dateFormatter->format($ts, 'custom', 'g', $tz) . ($minutes === '00' ? '' : ':' . $minutes);
      return [$clock, $hour < 12 ? 'a.m.' : 'p.m.'];
    };
    [$from, $from_meridiem] = $parts($start);
    if (!$end) {
      return trim("$from $from_meridiem");
    }
    [$to, $to_meridiem] = $parts($end);
    if ($from_meridiem === $to_meridiem && $from_meridiem !== '') {
      return "$from–$to $to_meridiem";
    }
    return trim(trim("$from $from_meridiem") . '–' . trim("$to $to_meridiem"));
  }

}
