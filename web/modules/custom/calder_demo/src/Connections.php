<?php

declare(strict_types=1);

namespace Drupal\calder_demo;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;

/**
 * Finds the content connected to a piece of content.
 *
 * Most relationships on the site are stored once, on one side: a person lists
 * their departments, a trial lists its investigator. This service answers the
 * reverse questions ("who works in this department?", "which trials does this
 * person lead?") so every page can show its connections without anyone having
 * to maintain the same link twice.
 */
final class Connections {

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly TimeInterface $time,
  ) {}

  /**
   * Loads published nodes of a bundle that reference the given node.
   *
   * @param string $bundle
   *   The bundle to search.
   * @param string|string[] $fields
   *   One or more reference fields; a match on any of them counts.
   * @param \Drupal\node\NodeInterface $target
   *   The referenced node.
   * @param array $options
   *   - sort: field to sort by (default: title).
   *   - direction: ASC or DESC.
   *   - limit: maximum number of results.
   *   - upcoming: for events, only those that haven't ended.
   *   - conditions: extra [field => value] conditions.
   *
   * @return \Drupal\node\NodeInterface[]
   *   The matching nodes, keyed by ID.
   */
  public function referencing(string $bundle, string|array $fields, NodeInterface $target, array $options = []): array {
    $options += ['sort' => 'title', 'direction' => 'ASC', 'limit' => 0, 'upcoming' => FALSE, 'conditions' => []];
    $storage = $this->entityTypeManager->getStorage('node');
    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', $bundle)
      ->condition('status', 1);

    $fields = (array) $fields;
    $group = $query->orConditionGroup();
    foreach ($fields as $field) {
      $group->condition("$field.target_id", $target->id());
    }
    $query->condition($group);

    foreach ($options['conditions'] as $field => $value) {
      $query->condition($field, $value);
    }
    if ($options['upcoming']) {
      $query->condition('field_event_date.end_value', gmdate('Y-m-d\TH:i:s', $this->time->getRequestTime()), '>=');
    }
    $query->sort($options['sort'], $options['direction']);
    if ($options['limit']) {
      $query->range(0, $options['limit']);
    }
    return $storage->loadMultiple($query->execute());
  }

  /**
   * Returns every connection for a node, grouped and labelled for display.
   *
   * @return array<string, array{label: string, items: \Drupal\node\NodeInterface[]}>
   *   Groups keyed by machine name, in display order. Empty groups are removed.
   */
  public function forNode(NodeInterface $node): array {
    $groups = match ($node->bundle()) {
      'person' => [
        'trials' => ['Clinical trials led', $this->referencing('clinical_trial', 'field_investigator', $node, ['sort' => 'field_trial_status'])],
        'labs' => ['Research labs', $this->referencing('lab', ['field_lead', 'field_members'], $node)],
        'programs' => ['Programs directed', $this->referencing('program', 'field_director', $node)],
        'events' => ['Upcoming talks and events', $this->referencing('event', 'field_speakers', $node, ['sort' => 'field_event_date.value', 'upcoming' => TRUE])],
        'news' => ['In the news', $this->referencing('news', 'field_people', $node, ['sort' => 'created', 'direction' => 'DESC'])],
      ],
      'department' => [
        'people' => ['Our people', $this->referencing('person', 'field_departments', $node, ['sort' => 'field_last_name'])],
        'conditions' => ['Conditions we treat', $this->referencing('condition', 'field_departments', $node)],
        'trials' => ['Clinical trials', $this->referencing('clinical_trial', 'field_departments', $node, ['sort' => 'field_trial_status'])],
        'programs' => ['Education programs', $this->referencing('program', 'field_departments', $node)],
        'labs' => ['Research labs', $this->referencing('lab', 'field_departments', $node)],
        'events' => ['Upcoming events', $this->referencing('event', 'field_departments', $node, ['sort' => 'field_event_date.value', 'upcoming' => TRUE])],
        'news' => ['News', $this->referencing('news', 'field_departments', $node, ['sort' => 'created', 'direction' => 'DESC', 'limit' => 3])],
      ],
      'condition' => [
        'people' => ['Specialists who treat this condition', $this->referencing('person', 'field_conditions', $node, ['sort' => 'field_last_name'])],
        'trials' => ['Clinical trials', $this->referencing('clinical_trial', 'field_conditions', $node, ['sort' => 'field_trial_status'])],
      ],
      'location' => [
        'people' => ['Providers at this location', $this->referencing('person', 'field_locations', $node, ['sort' => 'field_last_name'])],
        'events' => ['Upcoming events here', $this->referencing('event', 'field_locations', $node, ['sort' => 'field_event_date.value', 'upcoming' => TRUE])],
        'trials' => ['Clinical trials here', $this->referencing('clinical_trial', 'field_locations', $node, ['sort' => 'field_trial_status'])],
      ],
      'clinical_trial', 'program', 'event' => [
        'news' => ['Related news', $this->referencing('news', 'field_departments', $node->get('field_departments')->entity ?? $node, ['sort' => 'created', 'direction' => 'DESC', 'limit' => 3])],
      ],
      'lab' => [
        'trials' => ['Clinical trials', $this->leadTrials($node)],
      ],
      default => [],
    };

    $result = [];
    foreach ($groups as $key => [$label, $items]) {
      // A page never lists itself as its own connection.
      unset($items[$node->id()]);
      if ($items) {
        $result[$key] = ['label' => $label, 'items' => $items];
      }
    }
    return $result;
  }

  /**
   * Trials led by a lab's lead.
   */
  private function leadTrials(NodeInterface $lab): array {
    $lead = $lab->get('field_lead')->entity;
    return $lead instanceof NodeInterface ? $this->referencing('clinical_trial', 'field_investigator', $lead) : [];
  }

}
