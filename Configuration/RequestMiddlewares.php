<?php

// Copyright JAKOTA Design Group GmbH. All rights reserved.
declare(strict_types=1);
use Typoheads\Formhandler\Middleware\AjaxMiddleware;

return [
  'frontend' => [
    'typoheads/formhandler/ajax' => [
      'target' => AjaxMiddleware::class,
      'before' => [
        'typo3/cms-redirects/redirecthandler',
      ],
      'after' => [
        '',
      ],
    ],
  ],
];
