<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "Formhandler" by JAKOTA.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace Typoheads\Formhandler\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Log extends AbstractEntity {
  protected string $formName = '';

  protected int $formPageId = 0;

  protected string $ip = '';

  protected bool $isSpam = false;

  protected string $keyHash = '';

  protected string $params = '';

  protected string $uniqueHash = '';

  public function getFormName(): string {
    return $this->formName;
  }

  public function getFormPageId(): int {
    return $this->formPageId;
  }

  public function getIp(): string {
    return $this->ip;
  }

  public function getIsSpam(): bool {
    return $this->isSpam;
  }

  public function getKeyHash(): string {
    return $this->keyHash;
  }

  public function getParams(): string {
    return $this->params;
  }

  public function getUniqueHash(): string {
    return $this->uniqueHash;
  }

  public function setFormName(string $formName): void {
    $this->formName = $formName;
  }

  public function setFormPageId(int $formPageId): void {
    $this->formPageId = $formPageId;
  }

  public function setIp(string $ip): void {
    $this->ip = $ip;
  }

  public function setIsSpam(bool $isSpam): void {
    $this->isSpam = $isSpam;
  }

  public function setKeyHash(string $keyHash): void {
    $this->keyHash = $keyHash;
  }

  public function setParams(string $params): void {
    $this->params = $params;
  }

  public function setUniqueHash(string $uniqueHash): void {
    $this->uniqueHash = $uniqueHash;
  }
}
