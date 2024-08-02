<?php

declare(strict_types=1);

use Typoheads\Formhandler\Controller\AdministrationController;
use Typoheads\Formhandler\Definitions\FormhandlerExtensionConfig;

return [
  'web_formhandler' => [
    'parent' => 'web',
    'access' => 'user',
    'iconIdentifier' => 'formhandler',
    'labels' => 'LLL:EXT:formhandler/Resources/Private/Language/locallang_mod.xlf',
    'path' => '/module/formhandler/',
    'extensionName' => FormhandlerExtensionConfig::EXTENSION_TITLE,
    'controllerActions' => [
      AdministrationController::class => [
        'index', 'detail', 'selectFields',
      ],
    ],
    'inheritNavigationComponentFromMainModule' => false,
    'navigationComponent' => '',
  ],
];
