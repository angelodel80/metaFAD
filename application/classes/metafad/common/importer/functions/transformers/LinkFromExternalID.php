<?php

/**
 * Created by PhpStorm.
 * User: marco
 * Date: 07/12/16
 * Time: 11.52
 */
class metafad_common_importer_functions_transformers_LinkFromExternalID implements metafad_common_importer_functions_transformers_TransformerInterface
{
    private $models = ["archivi.models.Model"];

    /**
     * metafad_common_importer_functions_transformers_LinkFromExternalID constructor.
     * Si aspetta:
     * - params->models = array dei nomi dei model a cui accedere per iterare
     * @param $params
     */
    public function __construct($params)
    {
        $this->models = $params && is_array($params->models) && $params->models ? $params->models : $this->models;
    }

    public function transformItems($array)
    {
        /**
         * @var $archProxy archivi_models_proxy_ArchiviProxy
         */
        $archProxy = __ObjectFactory::createObject("archivi.models.proxy.ArchiviProxy");

        return array_map(
            function ($extId) use ($archProxy) {
                $links = $archProxy->getLinkObjectsByExternalId($extId);

                return count($links) ? current($links) : null;
            }, $array);
    }
}