<?php

/**
 * Created by PhpStorm.
 * User: marco
 * Date: 13/02/17
 * Time: 11.37
 */
class metafad_modules_importer_xmlArchiveEADEAC_utils_Generator
{
    /**
     * @param $instituteKey
     * @return null|array
     */
    public static function generateFondo($instituteKey)
    {
        metafad_usersAndPermissions_Common::setInstituteKey($instituteKey);

        $archiviProxy = new archivi_models_proxy_ArchiviProxy();
        $data = new stdClass();
        $data->__model = "archivi.models.ComplessoArchivistico";
        $data->__id = "";
        $data->livelloDiDescrizione = "fondo";
        $data->pageId = "archivi-complessoarchivistico";
        $data->root = true;
        $data->instituteKey = $instituteKey;

        $data->acronimoSistema = "PDN";
        $data->denominazione = "(SNSP) Fondo Manoscritti";

        $ret = $archiviProxy->save($data);
        $id = $ret['set']['__id'];
        $link = (array)$archiviProxy->getLinkObjectById($id, "archivi.models.ComplessoArchivistico");
        return $link;
    }
}