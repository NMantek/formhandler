<?php

/*
 * This file is part of TYPO3 CMS-based extension "Formhandler" by JAKOTA.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace Typoheads\Formhandler\ViewHelpers;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

final class GetTurnstileSitekeyViewHelper extends AbstractViewHelper {
  public function render(): string {
    $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('formhandler');

    if (is_array($extensionConfiguration) && isset($extensionConfiguration['turnstile']) && is_array($extensionConfiguration['turnstile'])) {
      return $extensionConfiguration['turnstile']['sitekey'];
    }

    return '';
  }
}
