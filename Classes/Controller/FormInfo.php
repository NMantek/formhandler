<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Controller;

class FormInfo extends Form {
  public function process(): string {
    $this->init();
    $content = $this->processNotSubmitted();

    $info = [
      'name' => $this->settings['name'] ?? null,
      'formID' => $this->settings['formID'] ?? null,
      'formValuesPrefix' => $this->settings['formID'] ?? null,
      'finishers' => $this->getFinishers(),
      'validators' => $this->getValidators(),
      'fields' => $this->getFields($content),
    ];

    header('Content-Type: application/json');
    echo json_encode($info);

    exit;
  }

  /**
   * @return array<int, array<string, mixed>>
   */
  private function getFields(string $content): array {
    $content = str_replace(' & ', ' &amp; ', $content);

    $fields = [];

    $document = new \DOMDocument();
    $document->loadHTML('<?xml encoding="utf-8" ?>'.$content);

    // Find all select boxes
    foreach ($document->getElementsByTagName('select') as $selectField) {
      $field = [
        'element' => 'select',
        'value' => $selectField->getAttribute('value'),
        'id' => $selectField->getAttribute('id'),
        'name' => $selectField->getAttribute('name'),
        'option' => [],
      ];

      foreach ($selectField->getElementsByTagName('option') as $option) {
        $field['option'][] = [
          'value' => $option->getAttribute('value'),
          'text' => $option->textContent,
        ];
      }

      $fields[] = $field;
    }

    // Find all input fields
    foreach ($document->getElementsByTagName('input') as $inputField) {
      $fields[] = [
        'element' => 'input',
        'type' => $inputField->getAttribute('type'),
        'value' => $inputField->getAttribute('value'),
        'id' => $inputField->getAttribute('id'),
        'name' => $inputField->getAttribute('name'),
      ];
    }

    // Find all textareas
    foreach ($document->getElementsByTagName('textarea') as $textarea) {
      $fields[] = [
        'element' => 'textarea',
        'value' => $textarea->getAttribute('value'),
        'id' => $textarea->getAttribute('id'),
        'name' => $textarea->getAttribute('name'),
      ];
    }

    return $fields;
  }

  /**
   * @return array<int, string>
   */
  private function getFinishers(): array {
    $finishers = [];
    foreach ((array) ($this->settings['finishers.'] ?? []) as $finisher) {
      if (is_array($finisher)) {
        $finishers[] = strval($finisher['class']);
      }
    }

    return $finishers;
  }

  /**
   * @return array<int, array<string, mixed>>
   */
  private function getValidators(): array {
    $validators = [];

    /**
     * @var array<string, array<string, mixed>|string> $validatorElement
     */
    foreach ((array) ($this->settings['validators.'] ?? []) as $validatorElement) {
      $validator = ['class' => $validatorElement['class'], 'fields' => []];

      /**
       * @var array<string, array<string, mixed>|string> $fieldConfig
       */
      foreach ((array) ($validatorElement['config.']['fieldConf.'] ?? []) as $fieldName => $fieldConfig) {
        $config = ['field' => rtrim($fieldName, '.'), 'checks' => []];

        foreach ((array) ($fieldConfig['errorCheck.'] ?? []) as $errorCheck) {
          if (is_array($errorCheck)) {
            /** @var array<string, string> $check */
            $check = array_pop($config['checks']);
            $check = array_merge($check, $errorCheck);
          } else {
            $check = ['type' => strval($errorCheck)];
          }

          $config['checks'][] = $check;
        }

        $validator['fields'][] = $config;
      }

      $validators[] = $validator;
    }

    return $validators;
  }
}
