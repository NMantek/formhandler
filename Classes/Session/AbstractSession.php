<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "Formhandler" by JAKOTA.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace Typoheads\Formhandler\Session;

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use Typoheads\Formhandler\Domain\Model\Config\FormModel;

abstract class AbstractSession implements SingletonInterface {
  protected FormModel $formConfig;

  protected RequestInterface $request;

  protected bool $started = false;

  abstract public function exists(): bool;

  abstract public function get(string $key): mixed;

  public function init(FormModel &$formConfig, RequestInterface $request): AbstractSession {
    $this->formConfig = $formConfig;
    $this->request = $request;

    return $this;
  }

  abstract public function reset(): AbstractSession;

  abstract public function set(string $key, mixed $value): AbstractSession;

  /**
   * @param array<string, mixed> $values
   */
  abstract public function setMultiple(array $values): AbstractSession;

  abstract public function start(): AbstractSession;

  abstract protected function getLifetime(): int;
}
