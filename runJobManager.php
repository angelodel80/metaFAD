<?php
chdir(dirname($_SERVER['PHP_SELF']));

require_once("core/core.inc.php");
$application = org_glizy_ObjectFactory::createObject('org.glizycms.core.application.AdminApplication', 'application', '', 'application/');
$application->useXmlSiteMap = true;
$application->setLanguage('it');
$application->runSoft();
$application->executeCommand('metacms.jobmanager.controllers.JobManager');
