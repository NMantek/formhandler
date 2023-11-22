#!/bin/bash

echo "Please input version number like 8.3.9:"
read version

mkdir -p ./.ter/formhandler

cp -R ./Classes ./.ter/formhandler/
cp -R ./Configuration ./.ter/formhandler/
cp -R ./pi1 ./.ter/formhandler/
cp -R ./Resources ./.ter/formhandler/
cp -R ./composer.json ./.ter/formhandler/
cp -R ./ext_* ./.ter/formhandler/
cp -R ./LICENSE ./.ter/formhandler/
cp -R ./README.md ./.ter/formhandler/
mkdir -p ./.ter/formhandler/Resources/PHP/parsecsv
cp -R ./vendor/parsecsv/php-parsecsv/parsecsv.lib.php ./.ter/formhandler/Resources/PHP/parsecsv/parsecsv.lib.php
cp -R ./vendor/parsecsv/php-parsecsv/src/ ./.ter/formhandler/Resources/PHP/parsecsv/src
cp -R ./vendor/tecnickcom/tcpdf ./.ter/formhandler/Resources/PHP/

line="class BackendCsv extends AbstractComponent {"
rep="require_once(\\\TYPO3\\\CMS\\\Core\\\Utility\\\ExtensionManagementUtility::extPath('formhandler') . 'Resources\/PHP\/parsecsv\/parsecsv.lib.php');\n\nclass BackendCsv extends AbstractComponent {"
sed -i '' -e "s/${line}/${rep}/g" ./.ter/formhandler/Classes/Generator/BackendCsv.php

line="class Csv extends AbstractGenerator {"
rep="require_once(\\\TYPO3\\\CMS\\\Core\\\Utility\\\ExtensionManagementUtility::extPath('formhandler') . 'Resources\/PHP\/parsecsv\/parsecsv.lib.php');\n\nclass Csv extends AbstractGenerator {"
sed -i '' -e "s/${line}/${rep}/g" ./.ter/formhandler/Classes/Generator/Csv.php

line="class TemplateTCPDF extends \\\TCPDF {"
rep="require_once(\\\TYPO3\\\CMS\\\Core\\\Utility\\\ExtensionManagementUtility::extPath('formhandler') . 'Resources\/PHP\/tcpdf\/tcpdf.php');\n\nclass TemplateTCPDF extends \\\TCPDF {"
sed -i '' -e "s/${line}/${rep}/g" ./.ter/formhandler/Classes/Utility/TemplateTCPDF.php

line="'version' => ''"
rep="'version' => '${version}'"
sed -i '' -e "s/${line}/${rep}/g" ./.ter/formhandler/ext_emconf.php

cd ./.ter && zip -r -q ./formhandler_${version}.zip ./formhandler && cd - 

rm -r ./.ter/formhandler
