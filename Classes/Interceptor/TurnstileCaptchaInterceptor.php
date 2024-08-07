<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "Formhandler" by JAKOTA.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace Typoheads\Formhandler\Interceptor;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Typoheads\Formhandler\Definitions\Severity;
use Typoheads\Formhandler\Domain\Model\Config\FormModel;
use Typoheads\Formhandler\Domain\Model\Config\Interceptor\AbstractInterceptorModel;
use Typoheads\Formhandler\Domain\Model\Config\Interceptor\TurnstileCaptchaInterceptorModel;
use Typoheads\Formhandler\Http\TurnstileVerifyRequester;

class TurnstileCaptchaInterceptor extends AbstractInterceptor {
  public function __construct(
    private TurnstileVerifyRequester $requestClient
  ) {}

  public function process(FormModel &$formConfig, AbstractInterceptorModel &$interceptorConfig): bool {
    if (!$interceptorConfig instanceof TurnstileCaptchaInterceptorModel) {
      return false;
    }

    $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('formhandler');

    $turnstilePrivateKey = $extensionConfiguration['turnstile']['privatekey'] ?? '';
    $turnstileWidgetToken = $formConfig->captchaFieldValues['turnstile'] ?? '';

    try {
      $validationResult = $this->requestClient->request($turnstilePrivateKey, $turnstileWidgetToken, GeneralUtility::getIndpEnv('REMOTE_ADDR'));

      if (isset($validationResult['success']) && true === $validationResult['success']) {
        return true;
      }

      $formConfig->formErrors[] = 'captcha_failed';

      return false;
    } catch (\Exception $error) {
      $formConfig->formErrors[] = 'captcha_failed';
      $formConfig->debugMessage('turnstile_captcha_failed', [$error->getMessage()], Severity::Error);

      return false;
    }
  }
}
