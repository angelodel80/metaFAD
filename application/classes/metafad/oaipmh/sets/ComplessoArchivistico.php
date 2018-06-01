<?php
class metafad_oaipmh_sets_ComplessoArchivistico extends metafad_oaipmh_core_AbstractMapping
{
    public function getSetInfo()
    {
        $info = parent::getSetInfo();
        $info[ 'setSpec' ] = 'ComplessoArchivistico';
        $info[ 'setName' ] = 'Complesso Archivistico';
        $info[ 'setDescription' ] = 'Complesso Archivistico';
        $info[ 'setCreator' ] = 'Meta srl';
        $info[ 'model' ] = 'archivi.models.ComplessoArchivistico';
        return $info;
    }

    function getMetadata($identifier)
    {
        $CATExp = __ObjectFactory::createObject('metafad.modules.exporter.services.catexporter.CatExporter');
        $output = $CATExp->CreaCAT($identifier);
        return $output;
    }
}
