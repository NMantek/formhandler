<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or exit;

ExtensionManagementUtility::addStaticFile(
  'formhandler',
  'Configuration/TypoScript/Json',
  'Activate JSON Output (optional)'
);
