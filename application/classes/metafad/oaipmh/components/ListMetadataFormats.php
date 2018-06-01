<?php
class metafad_oaipmh_components_ListMetadataFormats extends org_glizy_components_Component
{
    /**
     * Render
     *
     * @return    void
     * @access    public
     */
    function render($outputMode=NULL, $skipChilds=false)
    {
        $metadataFormat = $this->_application->getMetadataFormat();
        $output = '<ListMetadataFormats>';
        foreach( $metadataFormat as $v )
        {
            $output .= '<metadataFormat>';
            $output .= '<metadataPrefix>'.org_glizy_oaipmh_OaiPmh::encode($v[ 'prefix' ] ).'</metadataPrefix>';
            $output .= '<schema>'.org_glizy_oaipmh_OaiPmh::encode($v[ 'schema' ] ).'</schema>';
            $output .= '<metadataNamespace>'.org_glizy_oaipmh_OaiPmh::encode($v[ 'namespace' ] ).'</metadataNamespace>';
            $output .= '</metadataFormat>';
        }
        $output .= '</ListMetadataFormats>';
        $this->addOutputCode( $output );
    }

}