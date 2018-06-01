<?php

/**
 * Created by PhpStorm.
 * User: marco
 * Date: 13/02/17
 * Time: 14.16
 */
class metafad_modules_importer_xmlArchiveEADEAC_services_Importers
{
    /**
     * Importa gli EAD specificati nel file con la chiave di istituto specificata.
     * <br>
     * jsonMaps è un array con le seguenti chiavi obbligatorie:
     * <ol>
     *   <li>common => filepath per il mapping comune</li>
     *   <li>CA => filepath per il mapping delle CA</li>
     *   <li>UA => filepath per il mapping delle UA</li>
     *   <li>UD => filepath per il mapping delle UD</li>
     * </ol>
     * @param $filePath string Nome del file da importare
     * @param string $instituteKey Chiave dell'istituto a cui apparterrà l'intero fondo
     * @param null|array $jsonMaps Se non specificato, verrà usato un array di default
     * @param org_glizy_log_LogBase $logger
     * @param bool $createWrapperFondo Default false: crea un fondo che conterrà l'importazione
     */
    public static function importEAD($filePath, $instituteKey = "societa-napoletana-di-storia-patria", $jsonMaps = null, $logger = null, $createWrapperFondo = false){
        $neededKeys = array("common", "CA", "UA", "UD");

        $jsonMaps = $jsonMaps ?: array(
            "common" => __DIR__ . "/../jsonSchemas/xDams/_commonArchive.json",
            "CA" => __DIR__ . "/../jsonSchemas/xDams/ICAR_ca_schema.json",
            "UA" => __DIR__ . "/../jsonSchemas/xDams/ICAR_ua_schema.json",
            "UD" => __DIR__ . "/../jsonSchemas/xDams/ICAR_ud_schema.json"
        );

        array_map(function($key) use ($jsonMaps){if (!key_exists($key, $jsonMaps)) throw new Exception("La chiave $key non è stata specificata per la creazione della pipeline");}, $neededKeys);

        if ($createWrapperFondo){
            $link = metafad_modules_importer_xmlArchiveEADEAC_utils_Generator::generateFondo($instituteKey);
        } else {
            $link = array("id" => 0, "text" => "");
        }
        $pipeline = metafad_modules_importer_xmlArchiveEADEAC_utils_PipelineProvider::getEADPipeline($filePath, $instituteKey, $jsonMaps, $link['id'], $link['text']);

        self::executeImport($pipeline, $logger);
    }


    /**
     * Importa gli EAC specificati nel file con la chiave di istituto specificata.
     * <br>
     * jsonMaps è un array con le seguenti chiavi obbligatorie:
     * <ol>
     *   <li>entitaPersona => filepath per il mapping delle entità di tipo Persona</li>
     *   <li>entitaFamiglia => filepath per il mapping delle entità di tipo Famiglia</li>
     *   <li>entitaEnte => filepath per il mapping delle entità di tipo Ente</li>
     *   <li>antroponimo => filepath per il mapping degli antroponimi</li>
     *   <li>ente => filepath per il mapping degli enti (voci d'indice)</li>
     * </ol>
     * @param $filePath
     * @param string $instituteKey
     * @param null|array $jsonMaps
     * @param org_glizy_log_LogBase $logger
     */
    public static function importEAC($filePath, $instituteKey = "societa-napoletana-di-storia-patria", $jsonMaps = null, $logger = null){
        $neededKeys = array("entitaPersona", "entitaFamiglia", "entitaEnte", "antroponimo", "ente", "void");

        $jsonMaps = $jsonMaps ?: array(
            "entitaPersona" => __DIR__ . "/../jsonSchemas/xDams/persona_schema.json",
            "entitaFamiglia" => __DIR__ . "/../jsonSchemas/xDams/famiglia_schema.json",
            "entitaEnte" => __DIR__ . "/../jsonSchemas/xDams/ente_schema.json",
            "antroponimo" => __DIR__ . "/../jsonSchemas/xDams/antroponimo_schema.json",
            "ente" => __DIR__ . "/../jsonSchemas/xDams/enteVI_schema.json",
            "void" => __DIR__ . "/../jsonSchemas/xDams/void.json"
        );

        array_map(function($key) use ($jsonMaps){if (!key_exists($key, $jsonMaps)) throw new Exception("La chiave $key non è stata specificata per la creazione della pipeline");}, $neededKeys);

        $pipeline = metafad_modules_importer_xmlArchiveEADEAC_utils_PipelineProvider::getEACPipeline($filePath, $instituteKey, $jsonMaps);

        self::executeImport($pipeline, $logger);
    }

    private static function fillSingleOpParams($opStdClass, $opName, $paramsToMerge, $overwrite = false){
        if (!is_object($opStdClass)){
            return;
        }
        foreach ($opStdClass as $k => $v){
            if ($k == "obj" && $v == $opName){
                $opStdClass->params = $opStdClass->params ?: new stdClass();
                foreach ($paramsToMerge as $key => $value){
                    $opStdClass->params->$key = $overwrite ? $value : ($opStdClass->params->$key ?: $value);
                }
            } else if (!is_array($v)) {
                self::fillSingleOpParams($v, $opName, $paramsToMerge);
            } else {
                foreach ($v as $obj){
                    self::fillSingleOpParams($obj, $opName, $paramsToMerge);
                }
            }
        }
    }

    private static function fillOperationParams($jsonPipeline, $opName, $paramsToMerge, $overwrite = false){
        $pipeline = json_decode($jsonPipeline);

        if ($pipeline === null) {
            throw new Exception("La stringa pipeline ha qualche problema nella decodifica da JSON: " . json_last_error_msg());
        }

        foreach ($pipeline as $k => $v){
            self::fillSingleOpParams($v, $opName, $paramsToMerge, $overwrite);
        }

        return $pipeline;
    }

    /**
     * @param $pipeline
     * @param org_glizy_log_LogBase $logger
     * @throws Exception
     */
    private static function executeImport($pipeline, $logger = null)
    {
        $gotException = false;

        /**
         * @var metafad_common_importer_MainRunner $runner
         */
        $runner = __ObjectFactory::createObject("metafad.common.importer.MainRunner");
        try{
            $params = new stdClass();
            $params->logger = $logger;
            $ret = $runner->executeFromStdClasses(self::fillOperationParams($pipeline, "metafad_common_importer_operations_LogInput", $params));
        } catch (Exception $ex){
            $ret = metafad_common_helpers_ImporterCommons::getThrowableString($ex);
            $gotException = true;
        }
        if ($logger && $gotException){
            $logger->debug($ret);
        } else if ($gotException) {
            throw new Exception("Errore riportato durante l'esecuzione: \r\n" . $ret);
        }
    }

}