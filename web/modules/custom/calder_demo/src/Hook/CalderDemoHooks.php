<?php

declare(strict_types=1);

namespace Drupal\calder_demo\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\node\NodeInterface;

/**
 * Hook implementations for the Calder demo.
 */
final class CalderDemoHooks {

  /**
   * Fills the hidden search keywords field.
   *
   * Find a Doctor and the trials finder search one field, so a visitor can type
   * a name, a specialty, a condition or a condition's other name ("AFib") and
   * get a match. Editors never have to maintain the keywords themselves.
   */
  #[Hook('node_presave')]
  public function nodePresave(NodeInterface $node): void {
    if (!$node->hasField('field_keywords')) {
      return;
    }
    $words = [$node->label()];

    $add_labels = function (string $field) use ($node, &$words): void {
      if ($node->hasField($field)) {
        foreach ($node->get($field)->referencedEntities() as $entity) {
          $words[] = $entity->label();
          if ($entity->hasField('field_also_called')) {
            foreach ($entity->get('field_also_called') as $item) {
              $words[] = $item->value;
            }
          }
        }
      }
    };
    $add_values = function (string $field) use ($node, &$words): void {
      if ($node->hasField($field)) {
        foreach ($node->get($field) as $item) {
          $words[] = $item->value;
        }
      }
    };

    switch ($node->bundle()) {
      case 'person':
        $add_values('field_job_title');
        $add_values('field_credentials');
        $add_labels('field_specialties');
        $add_labels('field_conditions');
        $add_labels('field_departments');
        break;

      case 'clinical_trial':
        $add_values('field_official_title');
        $add_values('field_protocol_id');
        $add_labels('field_conditions');
        $add_labels('field_investigator');
        break;
    }

    $node->set('field_keywords', implode(' | ', array_unique(array_filter(array_map('trim', $words)))));
  }


  /**
   * Lets listings filter by location, department and condition from a list.
   *
   * Core ships an "entity reference" Views filter that can show a select list
   * of the referenced items, but leaves reference fields on the plain numeric
   * filter. Switching these fields over gives visitors dropdowns of names.
   */
  #[Hook('views_data_alter')]
  public function viewsDataAlter(array &$data): void {
    foreach (['field_locations', 'field_departments', 'field_conditions'] as $field) {
      if (isset($data['node__' . $field][$field . '_target_id']['filter'])) {
        $data['node__' . $field][$field . '_target_id']['filter']['id'] = 'entity_reference';
      }
    }
  }

  /**
   * Friendlier filters on the Calder listings.
   *
   * - Dropdowns say "Any specialty" instead of "- Any -".
   * - "Accepting new patients" is a single checkbox instead of Any/Yes/No.
   */
  #[Hook('form_views_exposed_form_alter')]
  public function exposedFormAlter(array &$form, FormStateInterface $form_state): void {
    $view = $form_state->get('view');
    if (!$view || $view->storage->get('tag') !== 'Calder') {
      return;
    }
    $any = [
      'specialty' => 'Any specialty',
      'location' => 'Any location',
      'language' => 'Any language',
      'department' => 'Any department',
      'role' => 'Any role',
      'status' => 'Any status',
      'condition' => 'Any condition',
      'type' => 'All programs',
      'audience' => 'Everyone',
      'mission' => 'All topics',
    ];
    foreach ($any as $key => $label) {
      if (isset($form[$key]['#options']['All'])) {
        $form[$key]['#options']['All'] = $label;
      }
    }
    if (isset($form['accepting'])) {
      // An unchecked box sends nothing, so the filter falls back to "any".
      // Views pre-fills the filter's default ("All"), which a checkbox would
      // read as checked, so only a real "1" counts.
      $input = $form_state->getUserInput();
      if (($input['accepting'] ?? NULL) !== '1') {
        unset($input['accepting']);
        $form_state->setUserInput($input);
      }
      $form['accepting'] = [
        '#type' => 'checkbox',
        '#title' => $form['accepting']['#title'] ?? 'Accepting new patients',
        '#return_value' => '1',
        '#default_value' => isset($input['accepting']),
      ];
      // The label lives on the checkbox itself; drop the duplicate heading.
      unset($form['#info']['filter-field_accepting_new_value']['label']);
    }
  }


  /**
   * Shows the site search box on the search page itself.
   *
   * Drupal CMS's search view expects its form to be placed as a separate
   * block. The Calder theme doesn't place blocks, so render it with the view.
   */
  #[Hook('views_pre_view')]
  public function viewsPreView($view, string $display_id, array &$args): void {
    if ($view->id() === 'search') {
      $view->display_handler->setOption('exposed_block', FALSE);
    }
  }

}
