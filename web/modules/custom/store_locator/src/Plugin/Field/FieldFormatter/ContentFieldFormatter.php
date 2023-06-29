<?php declare(strict_types=1);

namespace Drupal\store_locator\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\Annotation\FieldFormatter;
use Drupal\Core\Field\Plugin\Field\FieldType\IntegerItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'Random_default' formatter.
 *
 * @FieldFormatter(
 *   id = "content_field",
 *   label = @Translation("Content field formatter"),
 *   field_types = {
 *     "integer"
 *   }
 * )
 */
class ContentFieldFormatter extends FormatterBase implements ContainerFactoryPluginInterface
{

  protected $entityTypeManager;

  /**
   * @var EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    EntityTypeManagerInterface $entityTypeManager,
    EntityDisplayRepositoryInterface $entityDisplayRepository
  )
  {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->entityTypeManager = $entityTypeManager;
    $this->entityDisplayRepository = $entityDisplayRepository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository')
    );
  }

  public static function defaultSettings()
  {
    return [
      'view_mode' => 'full',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements['view_mode'] = [
      '#type' => 'textfield',
      '#title' => t('View mode'),
      '#required' => TRUE,
      '#default_value' => $this->getSetting('view_mode'),
    ];

    return $elements;
  }

  public function viewElements(FieldItemListInterface $items, $langcode)
  {

    $elements = [];

    $viewMode = $this->getSetting('view_mode');

    /** @var IntegerItem $item */
    foreach ( $items as $item ) {
      $viewBuilder = $this->entityTypeManager->getViewBuilder($item->getEntity()->getEntityTypeId());
      $elements[] = $viewBuilder->view($item->getEntity(), $viewMode);
    }

    return $elements;
  }
}
