<?php
class metafad_oaipmh_sets_SoggettoProduttore extends metafad_oaipmh_core_AbstractMapping
{
    public function getSetInfo()
    {
        $info = parent::getSetInfo();
        $info[ 'setSpec' ] = 'SoggettoProduttore';
        $info[ 'setName' ] = 'Soggetto Produttore';
        $info[ 'setDescription' ] = 'Soggetto Produttore';
        $info[ 'setCreator' ] = 'Meta srl';
        $info[ 'model' ] = 'archivi.models.ProduttoreConservatore';
        return $info;
    }

    function getMetadata($identifier)
    {
        $CATExp = __ObjectFactory::createObject('metafad.modules.exporter.services.catexporter.CatExporter');
        $output = $CATExp->CreaSP($identifier);
        return $output;
    }
}
