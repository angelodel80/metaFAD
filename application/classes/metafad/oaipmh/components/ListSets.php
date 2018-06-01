<?php
class metafad_oaipmh_components_ListSets extends org_glizy_components_Component
{
    /**
     * Render
     *
     * @return    void
     * @access    public
     */
    function render($outputMode=NULL, $skipChilds=false)
    {
        $sets = $this->_application->getSets();

        if ( !count( $sets ) ) {
            $this->_application->setError( 'noSetHierarchy' );
        } else {
            $metadataFormat = $this->_application->getMetadataFormat();
            $output = '<ListSets>';
            foreach( $sets as $v ) {
                $setClass = org_glizy_ObjectFactory::createObject($v);
                if ( $setClass ) {
                    $info = $setClass->getSetInfo();
                    $output .= '<set>';
                    $output .= '<setSpec>'.org_glizy_oaipmh_OaiPmh::encode($info[ 'setSpec' ] ).'</setSpec>';
                    $output .= '<setName>'.org_glizy_oaipmh_OaiPmh::encode($info[ 'setName' ] ).'</setName>';

                    if ( !empty( $info[ 'setDescription' ] ) ) {
                        $output .= '<setDescription>';
                        $output .= '<oai_dc:dc xmlns:oai_dc="http://www.openarchives.org/OAI/2.0/oai_dc/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/oai_dc/ http://www.openarchives.org/OAI/2.0/oai_dc.xsd">';
                        $output .= '<dc:description>'.org_glizy_oaipmh_OaiPmh::encode($info[ 'setDescription' ] ).'</dc:description>';
                        $output .= '<dc:creator>'.org_glizy_oaipmh_OaiPmh::encode($info[ 'setCreator' ] ).'</dc:creator>';
                        $output .= '</oai_dc:dc>';
                        $output .= '</setDescription>';
                    }
                    $output .= '</set>';
                }
            }
            $output .= '</ListSets>';
            $this->addOutputCode( $output );
        }
    }
}