<?php
require_once("core/core.inc.php");

$application = org_glizy_ObjectFactory::createObject('org.glizy.rest.core.Application', 'application');
$application->useXmlSiteMap = true;
$application->setLanguage('it');
$application->run();