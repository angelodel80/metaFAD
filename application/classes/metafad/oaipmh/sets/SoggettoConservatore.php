<?php
class metafad_oaipmh_sets_SoggettoConservatore extends metafad_oaipmh_core_AbstractMapping
{
    public function getSetInfo()
    {
        $info = parent::getSetInfo();
        $info[ 'setSpec' ] = 'SoggettoConservatore';
        $info[ 'setName' ] = 'Soggetto Conservatore';
        $info[ 'setDescription' ] = 'Soggetto Conservatore';
        $info[ 'setCreator' ] = 'Meta srl';
        $info[ 'model' ] = 'archivi.models.ProduttoreConservatore';
        return $info;
    }

    function getMetadata($identifier)
    {
        $CATExp = __ObjectFactory::createObject('metafad.modules.exporter.services.catexporter.CatExporter');
        $output = $CATExp->CreaSC($identifier);
        return $output;
    }
}
