<?php
ob_start();
require_once("core/core.inc.php");

$configHost = $_SERVER["SERVER_NAME"];
$application = org_glizy_ObjectFactory::createObject('org.glizycms.core.application.AdminApplication', 'application', '', 'application', $configHost, false);
$application->useXmlSiteMap = true;
$application->setLanguage('it');
$application->runAjax();
