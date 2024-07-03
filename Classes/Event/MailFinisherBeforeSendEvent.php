<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "Formhandler" by JAKOTA.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace Typoheads\Formhandler\Event;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use Typoheads\Formhandler\Domain\Model\Config\Finisher\MailFinisherModel;
use Typoheads\Formhandler\Domain\Model\Config\FormModel;

final class MailFinisherBeforeSendEvent {
  /** @param mixed[] $formValues */
  public function __construct(
    private MailFinisherModel $finisherConfig,
    private FormModel $formConfig,
    private ServerRequestInterface $request,
    private FluidEmail $emailObject,
    private array $formValues,
  ) {}

  public function getEmailObject(): FluidEmail {
    return $this->emailObject;
  }

  public function getFinisherConfig(): MailFinisherModel {
    return $this->finisherConfig;
  }

  public function getFormConfig(): FormModel {
    return $this->formConfig;
  }

  /** @return mixed[] */
  public function getFormValues(): array {
    return $this->formValues;
  }

  public function getRequest(): ServerRequestInterface {
    return $this->request;
  }

  public function setEmailObject(FluidEmail $emailObject): void {
    $this->emailObject = $emailObject;
  }

  public function setFinisherConfig(MailFinisherModel $finisherConfig): void {
    $this->finisherConfig = $finisherConfig;
  }

  public function setFormConfig(FormModel $formConfig): void {
    $this->formConfig = $formConfig;
  }

  /** @param mixed[] $formValues */
  public function setFormValues(array $formValues): void {
    $this->formValues = $formValues;
  }

  public function setRequest(RequestInterface $request): void {
    $this->request = $request;
  }
}
