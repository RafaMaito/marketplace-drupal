<?php

namespace Drupal\commerce\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'commerce_entity_select' widget.
 */
#[FieldWidget(
  id: "commerce_entity_select",
  label: new TranslatableMarkup("Entity select"),
  field_types: ["entity_reference"],
  multiple_values: TRUE,
)]
class EntitySelectWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'hide_single_entity' => TRUE,
      'autocomplete_threshold' => 7,
      'autocomplete_size' => 60,
      'autocomplete_placeholder' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $formState) {
    $element = [];
    $element['hide_single_entity'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Hide if there's only one available entity."),
      '#default_value' => $this->getSetting('hide_single_entity'),
      '#access' => $this->fieldDefinition->isRequired(),
    ];
    $element['autocomplete_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Autocomplete threshold'),
      '#description' => $this->t('Number of available entities after which the autocomplete is used.'),
      '#default_value' => $this->getSetting('autocomplete_threshold'),
      '#min' => 2,
      '#required' => TRUE,
    ];
    $element['autocomplete_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Autocomplete size'),
      '#description' => $this->t('Size of the input field in characters.'),
      '#default_value' => $this->getSetting('autocomplete_size'),
      '#min' => 1,
      '#required' => TRUE,
    ];
    $element['autocomplete_placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Autocomplete placeholder'),
      '#default_value' => $this->getSetting('autocomplete_placeholder'),
      '#description' => $this->t('Text that will be shown inside the autocomplete field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $hide_single_entity = $this->getSetting('hide_single_entity');
    if ($this->fieldDefinition->isRequired() && $hide_single_entity) {
      $summary[] = $this->t("Hide if there's only one available entity");
    }
    $summary[] = $this->t('Autocomplete threshold: @threshold entities.', [
      '@threshold' => $this->getSetting('autocomplete_threshold'),
    ]);
    $summary[] = $this->t('Autocomplete size: @size characters', [
      '@size' => $this->getSetting('autocomplete_size'),
    ]);
    $placeholder = $this->getSetting('autocomplete_placeholder');
    if (!empty($placeholder)) {
      $summary[] = $this->t('Autocomplete placeholder: @placeholder', [
        '@placeholder' => $placeholder,
      ]);
    }
    else {
      $summary[] = $this->t('No autocomplete placeholder');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $settings = $this->getSettings();
    $multiple = $this->fieldDefinition->getFieldStorageDefinition()->isMultiple();
    $values = $items->getValue();
    if ($multiple) {
      $default_value = array_column($values, 'target_id');
    }
    else {
      $default_value = !empty($values) ? $values[0]['target_id'] : NULL;
    }

    $field = $this->fieldDefinition;
    $element['target_id'] = [
      '#type' => 'commerce_entity_select',
      '#title' => $field->getLabel(),
      '#target_type' => $this->getFieldSetting('target_type'),
      '#multiple' => $multiple,
      '#default_value' => $default_value,
      '#hide_single_entity' => $settings['hide_single_entity'],
      '#autocomplete_threshold' => $settings['autocomplete_threshold'],
      '#autocomplete_size' => $settings['autocomplete_size'],
      '#autocomplete_placeholder' => $settings['autocomplete_placeholder'],
      '#required' => $field->isRequired(),
    ];

    if (!$field->isRequired() && $field->getSetting('optional_label')) {
      $checkbox_parents = array_merge($form['#parents'], [$field->getName(), 'has_value']);
      $checkbox_path = array_shift($checkbox_parents);
      $checkbox_path .= '[' . implode('][', $checkbox_parents) . ']';

      $element['has_value'] = [
        '#type' => 'checkbox',
        '#title' => $field->getSetting('optional_label'),
        '#default_value' => !empty($element['target_id']['#default_value']),
      ];
      $element['target_id']['#weight'] = 10;
      $element['target_id']['#description'] = '';
      $element['container'] = [
        '#type' => 'container',
        '#states' => [
          'visible' => [
            ':input[name="' . $checkbox_path . '"]' => ['checked' => TRUE],
          ],
        ],
      ];
      $element['container']['target_id'] = $element['target_id'];
      unset($element['target_id']);
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    if (isset($values['container']['target_id']) && !empty($values['has_value'])) {
      $values['target_id'] = $values['container']['target_id'];
      unset($values['container']);
    }
    return $values['target_id'] ?? [];
  }

}
