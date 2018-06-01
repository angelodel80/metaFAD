<?php
require_once "import_glizy.php";

ini_set('memory_limit','2048M');

//TODO: mettici un filename
$fileJson = "./pipelineExamples/importTEI.json";


/**
 * @var metafad_common_importer_MainRunner $runnerino
 */
$runnerino = __ObjectFactory::createObject("metafad.common.importer.MainRunner");

$runnerino->executeFromJson($fileJson);
//$runnerino->executeFromJsonString($fileJson);
