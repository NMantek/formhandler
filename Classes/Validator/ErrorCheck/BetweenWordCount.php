<?php
declare(strict_types=1);

namespace Typoheads\Formhandler\Validator\ErrorCheck;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * Validates that a specified field is a string and has a word count between two specified values
 */
class BetweenWordCount extends AbstractErrorCheck
{
    public function init(array $gp, array $settings): void
    {
        parent::init($gp, $settings);
        $this->mandatoryParameters = ['minValue', 'maxValue'];
    }

    public function check(): string
    {
        $checkFailed = '';
        $min = (int)($this->utilityFuncs->getSingle($this->settings['params'], 'minValue'));
        $max = (int)($this->utilityFuncs->getSingle($this->settings['params'], 'maxValue'));
        if (isset($this->gp[$this->formFieldName]) &&
            (str_word_count($this->gp[$this->formFieldName]) < (int)$min ||
                str_word_count($this->gp[$this->formFieldName]) > (int)$max)
        ) {
            $checkFailed = $this->getCheckFailed();
        }
        return $checkFailed;
    }
}
