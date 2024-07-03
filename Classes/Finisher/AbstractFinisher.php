<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "Formhandler" by JAKOTA.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace Typoheads\Formhandler\Finisher;

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use Typoheads\Formhandler\Domain\Model\Config\Finisher\AbstractFinisherModel;
use Typoheads\Formhandler\Domain\Model\Config\FormModel;

abstract class AbstractFinisher implements SingletonInterface {
  abstract public function process(FormModel &$formConfig, AbstractFinisherModel &$finisherConfig, RequestInterface $request): void;
}
