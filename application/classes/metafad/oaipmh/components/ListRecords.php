<?php
class metafad_oaipmh_components_ListRecords extends metafad_oaipmh_components_ListIdentifiers
{
    protected $operationType = 'ListRecords';

    protected function makeResult($idPrefix, $doc, $set, $setClass)
    {
        $identifier = $idPrefix.$doc->id;
        $datestamp = org_glizy_oaipmh_OaiPmh::formatDatestamp( $doc->update_at_s );
        $setClass->loadRecord( $doc->id );

        $output = '<record>';
        $output .= '<header>';
        $output .= '<identifier>'.org_glizy_oaipmh_OaiPmh::encode( $identifier ).'</identifier>';
        $output .= '<datestamp>'.org_glizy_oaipmh_OaiPmh::encode( $datestamp ).'</datestamp>';
        $output .= '<setSpec>'.org_glizy_oaipmh_OaiPmh::encode( $set ).'</setSpec>';
        $output .= '</header>';
        $output .= '<metadata>';
        $output .= $setClass->getRecord($doc->id);
        $output .= '</metadata>';
        $output .= '</record>';

        return $output;
    }
}
