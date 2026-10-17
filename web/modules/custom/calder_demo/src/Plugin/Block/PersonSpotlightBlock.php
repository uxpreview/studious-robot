<?php

declare(strict_types=1);

namespace Drupal\calder_demo\Plugin\Block;

use Drupal\calder_demo\Connections;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Shows one person and how their roles connect across the institution.
 *
 * Editors pick a person; the block works out the rest from the content that
 * points at them (locations, programs they direct, labs and trials they lead).
 */
#[Block(
  id: 'calder_person_spotlight',
  admin_label: new TranslatableMarkup('Person spotlight'),
  category: new TranslatableMarkup('Calder'),
)]
final class PersonSpotlightBlock extends BlockBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly Connections $connections,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('calder_demo.connections'),
    );
  }

  public function defaultConfiguration(): array {
    return [
      'person' => '',
      'eyebrow' => 'One person, many roles',
      'heading' => 'Care, teaching and research meet in one person',
      'text' => 'The doctor you see on Monday may be teaching residents on Tuesday and leading a clinical trial on Wednesday.',
    ];
  }

  public function blockForm($form, FormStateInterface $form_state): array {
    $options = [];
    $storage = $this->entityTypeManager->getStorage('node');
    $ids = $storage->getQuery()->accessCheck(TRUE)->condition('type', 'person')->condition('status', 1)->sort('field_last_name')->execute();
    foreach ($storage->loadMultiple($ids) as $person) {
      $options[$person->uuid()] = $person->label();
    }
    $form['person'] = [
      '#type' => 'select',
      '#title' => $this->t('Person'),
      '#options' => $options,
      '#empty_option' => $this->t('- Choose a person -'),
      '#default_value' => $this->configuration['person'],
      '#required' => TRUE,
    ];
    foreach (['eyebrow' => 'Eyebrow', 'heading' => 'Heading'] as $key => $label) {
      $form[$key] = ['#type' => 'textfield', '#title' => $this->t($label), '#default_value' => $this->configuration[$key]];
    }
    $form['text'] = ['#type' => 'textarea', '#title' => $this->t('Text'), '#rows' => 3, '#default_value' => $this->configuration['text']];
    return $form;
  }

  public function blockSubmit($form, FormStateInterface $form_state): void {
    foreach (['person', 'eyebrow', 'heading', 'text'] as $key) {
      $this->configuration[$key] = $form_state->getValue($key);
    }
  }

  public function build(): array {
    // People are referenced by UUID so the choice survives export and import.
    $people = $this->configuration['person']
      ? $this->entityTypeManager->getStorage('node')->loadByProperties(['uuid' => $this->configuration['person']])
      : [];
    $person = reset($people);
    if (!$person instanceof NodeInterface) {
      return [];
    }

    $credentials = $person->get('field_credentials')->value;
    $is_doctor = (bool) array_intersect(['MD', 'PhD', 'DO', 'DNP'], array_map('trim', explode(',', (string) $credentials)));
    $last = $person->get('field_last_name')->value;
    $first = $person->get('field_first_name')->value;
    $roles = array_column($person->get('field_roles')->getValue(), 'value');
    $groups = $this->connections->forNode($person);
    $url = $person->toUrl()->toString();

    $role_items = [];
    if (in_array('clinician', $roles, TRUE)) {
      $locations = array_map(fn($l) => $l->label(), $person->get('field_locations')->referencedEntities());
      $role_items[] = [
        'mission' => 'care',
        'label' => 'Care',
        'text' => $locations ? 'Sees patients at ' . $this->listing($locations) : 'Sees patients',
        'url' => $url . '#locations',
      ];
    }
    if (in_array('faculty', $roles, TRUE)) {
      $program = isset($groups['programs']) ? reset($groups['programs']['items']) : NULL;
      $role_items[] = [
        'mission' => 'learn',
        'label' => 'Learn',
        'text' => $program ? 'Directs the ' . $program->label() : ($person->get('field_academic_title')->value ?: 'Teaches at the School of Medicine'),
        'url' => $program ? $program->toUrl()->toString() : $url,
      ];
    }
    if (in_array('researcher', $roles, TRUE)) {
      $lab = isset($groups['labs']) ? reset($groups['labs']['items']) : NULL;
      $trials = count($groups['trials']['items'] ?? []);
      $text = $lab ? 'Leads the ' . $lab->label() : 'Leads research';
      if ($trials) {
        $text .= $trials === 1 ? ' and a clinical trial' : " and $trials clinical trials";
      }
      $role_items[] = [
        'mission' => 'discover',
        'label' => 'Discover',
        'text' => $text,
        'url' => $lab ? $lab->toUrl()->toString() : $url,
      ];
    }

    return [
      '#type' => 'component',
      '#component' => 'calder:spotlight',
      '#props' => [
        'eyebrow' => $this->configuration['eyebrow'],
        'heading' => $this->configuration['heading'],
        'text' => $this->configuration['text'],
        'person' => [
          'display_name' => $person->label() . ($credentials ? ', ' . $credentials : ''),
          'formal_name' => ($is_doctor ? 'Dr. ' : '') . $last,
          'initials' => mb_substr((string) $first, 0, 1) . mb_substr((string) $last, 0, 1),
          'job_title' => $person->get('field_job_title')->value,
          'url' => $url,
          'photo' => NULL,
        ],
        'roles' => $role_items,
      ],
      '#cache' => ['tags' => ['node_list']],
    ];
  }

  /**
   * "A", "A and B", "A, B and C".
   */
  private function listing(array $items): string {
    $last = array_pop($items);
    return $items ? implode(', ', $items) . ' and ' . $last : $last;
  }

}
