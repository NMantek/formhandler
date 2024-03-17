<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "Formhandler" by JAKOTA.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace Typoheads\Formhandler\Domain\Model\Config\Validator;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use Typoheads\Formhandler\Domain\Model\Config\FormModel;
use Typoheads\Formhandler\Domain\Model\Config\Validator\Field\FieldModel;
use Typoheads\Formhandler\Utility\Utility;
use Typoheads\Formhandler\Validator\DefaultValidator;

/** Documentation:Start:Validators/DefaultValidator.rst.
 *
 *.. _defaultvalidator:
 *
 *================
 *DefaultValidator
 *================
 *
 *This is the default :ref:`Validator <Validators>`, it supports all available :ref:`error checks <Error-Checks>`.
 *
 *..  code-block:: typoscript
 *
 *    Example Code:
 *
 *    validators {
 *      DefaultValidator {
 *        model = DefaultValidator
 *        config {
 *          messageLimit = 1
 *          messageLimits {
 *            email = 2
 *          }
 *          restrictErrorChecks = LengthMax,LengthMin,Required,Email
 *          disableErrorCheckFields = firstName
 *          disableErrorCheckFields = {
 *            firstName = LengthMax,LengthMin
 *            email = Required
 *          }
 *          fields {
 *            firstName.errorChecks {
 *              lengthMax {
 *                model = LengthMax
 *                lengthMax = 20
 *              }
 *              lengthMin {
 *                model = LengthMin
 *                lengthMin = 10
 *              }
 *            }
 *            email.errorChecks {
 *              required {
 *                model = Required
 *              }
 *              email {
 *                model = Email
 *              }
 *            }
 *          }
 *        }
 *      }
 *    }
 *
 ***Properties**
 *
 *.. list-table::
 *   :align: left
 *   :width: 100%
 *   :widths: 20 80
 *   :header-rows: 0
 *   :stub-columns: 0
 *
 *   * - **messageLimit**
 *     - Sets the default limits of error messages for all and fields.
 *   * -
 *     -
 *   * - *Mandatory*
 *     - False
 *   * - *Data Type*
 *     - Integer
 *   * - *Default*
 *     - 1
 *
 *.. list-table::
 *   :align: left
 *   :width: 100%
 *   :widths: 20 80
 *   :header-rows: 0
 *   :stub-columns: 0
 *
 *   * - **messageLimits**
 *     - List containing the field name path and limit of error messages for specific fields.
 *   * -
 *     -
 *   * - *Mandatory*
 *     - False
 *   * - *Data Type*
 *     - Array<String, Integer>
 *   * - *Default*
 *     - Empty Array
 *
 *.. list-table::
 *   :align: left
 *   :width: 100%
 *   :widths: 20 80
 *   :header-rows: 0
 *   :stub-columns: 0
 *
 *   * - **restrictErrorChecks**
 *     - | Comma separated list containing the :ref:`error checks <Error-Checks>` to perform.
 *       | If this is set, only these :ref:`error checks <Error-Checks>` will be executed, all other configured :ref:`error checks <Error-Checks>` will be ignored.
 *   * -
 *     -
 *   * - *Mandatory*
 *     - False
 *   * - *Data Type*
 *     - String
 *   * - *Default*
 *     - Empty String
 *
 *.. list-table::
 *   :align: left
 *   :width: 100%
 *   :widths: 20 80
 *   :header-rows: 0
 *   :stub-columns: 0
 *
 *   * - **disableErrorCheckFields**
 *     - | Comma separated list containing the field name path to disable :ref:`error checks <Error-Checks>` for.
 *       | If this is set, all configured :ref:`error checks <Error-Checks>` will be ignored, these fields.
 *       |
 *       | Or a list containing the field name path as key and a comma separated list of :ref:`error checks <Error-Checks>` as values.
 *       |
 *       | This setting can be altered by :ref:`condition blocks <ConditionBlock>` depending on user input.
 *   * -
 *     -
 *   * - *Mandatory*
 *     - False
 *   * - *Data Type*
 *     - String|Array<String, :ref:`Error-Checks`>
 *   * - *Default*
 *     - Empty String
 *
 *.. list-table::
 *   :align: left
 *   :width: 100%
 *   :widths: 20 80
 *   :header-rows: 0
 *   :stub-columns: 0
 *
 *   * - **fields**
 *     - List containing the :ref:`fields <Field>` and :ref:`error checks <Error-Checks>`.
 *   * -
 *     -
 *   * - *Mandatory*
 *     - False
 *   * - *Data Type*
 *     - Array<String, :ref:`Field`>
 *   * - *Default*
 *     - Empty Array
 *
 *.. toctree::
 *   :maxdepth: 2
 *   :hidden:
 *
 *   Field
 *
 *Documentation:End
 */
class DefaultValidatorModel extends AbstractValidatorModel {
  public readonly int $messageLimit;

  /** @var array<string, int> */
  public readonly array $messageLimits;

  private readonly Utility $utility;

  /**
   * @param array<string, mixed> $settings
   */
  public function __construct(FormModel &$formConfig, string $step, array $settings) {
    $this->utility = GeneralUtility::makeInstance(Utility::class);

    foreach (GeneralUtility::trimExplode(',', strval($settings['restrictErrorChecks'] ?? ''), true) as $restrictErrorCheck) {
      $this->restrictErrorChecks[] = $this->utility->classString($restrictErrorCheck, '\\Typoheads\\Formhandler\\Validator\\ErrorCheck\\');
    }

    if (isset($settings['disableErrorCheckFields'])) {
      if (is_string($settings['disableErrorCheckFields'])) {
        foreach (GeneralUtility::trimExplode(',', $settings['disableErrorCheckFields'], true) as $field) {
          $formConfig->disableErrorCheckFields[$step.'.'.strval($field)] = [];
        }
      } elseif (is_array($settings['disableErrorCheckFields'])) {
        $this->disableErrorCheckFields($formConfig, $settings['disableErrorCheckFields'], $step);
      }
    }

    $this->messageLimit = intval($settings['messageLimit'] ?? 1);

    if (isset($settings['messageLimits']) && is_array($settings['messageLimits'])) {
      $this->messageLimits = $this->messageLimits($settings['messageLimits'], $step);
    } else {
      $this->messageLimits = [];
    }

    if (isset($settings['fields']) && is_array($settings['fields'])) {
      foreach ($settings['fields'] as $fieldName => $fieldSettings) {
        /** @var FieldModel $fieldModel */
        $fieldModel = GeneralUtility::makeInstance(FieldModel::class, $fieldName, $this, $fieldSettings);

        $this->fields[] = $fieldModel;
      }
    }
  }

  public function class(): string {
    return DefaultValidator::class;
  }

  private function disableErrorCheckFields(FormModel &$formConfig, array $disableErrorCheckFields, string $fieldNamePath): void {
    $fieldNamePath = $fieldNamePath.'.';
    foreach ($disableErrorCheckFields as $field => $disableErrorCheck) {
      if (is_array($disableErrorCheck)) {
        $this->disableErrorCheckFields($formConfig, $disableErrorCheck, $fieldNamePath.$field);
      } else {
        if (empty($disableErrorCheck)) {
          $formConfig->disableErrorCheckFields[$fieldNamePath.$field] = [];

          continue;
        }
        foreach (GeneralUtility::trimExplode(',', $disableErrorCheck, true) as $errorCheck) {
          $formConfig->disableErrorCheckFields[$fieldNamePath.$field][] = $this->utility->classString($errorCheck, '\\Typoheads\\Formhandler\\Validator\\ErrorCheck\\');
        }
      }
    }
  }

  /**
   * @param array<string, mixed> $messageLimits
   *
   * @return array<string, int>
   */
  private function messageLimits(array $messageLimits, string $fieldNamePath): array {
    $fieldNamePath = $fieldNamePath.'.';
    $messageLimitFields = [];
    foreach ($messageLimits as $field => $messageLimit) {
      if (is_array($messageLimit)) {
        $messageLimitFields = $this->messageLimits($messageLimit, $fieldNamePath.$field);
      } else {
        $messageLimitFields[$fieldNamePath.$field] = intval($messageLimit ?? 1);
      }
    }

    return $messageLimitFields;
  }
}
