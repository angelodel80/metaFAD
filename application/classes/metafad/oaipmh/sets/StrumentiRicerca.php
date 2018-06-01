<?php
class metafad_oaipmh_sets_StrumentiRicerca extends metafad_oaipmh_core_AbstractMapping
{
    public function getSetInfo()
    {
        $info = parent::getSetInfo();
        $info[ 'setSpec' ] = 'StrumentiRicerca';
        $info[ 'setName' ] = 'Strumenti di ricerca';
        $info[ 'setDescription' ] = 'Strumenti di ricerca';
        $info[ 'setCreator' ] = 'Meta srl';
        $info[ 'model' ] = 'archivi.models.SchedaStrumentoRicerca';
        return $info;
    }

    function getMetadata($identifier)
    {
        $CATExp = __ObjectFactory::createObject('metafad.modules.exporter.services.catexporter.CatExporter');
        $output = $CATExp->CreaSR($identifier);
        return $output;
    }
}
