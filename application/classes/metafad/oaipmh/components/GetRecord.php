<?php
class metafad_oaipmh_components_GetRecord extends org_glizy_components_Component
{
    protected $ar;
    protected $setClass;

    public static function is_valid_uri($url)
	{
        return((bool) preg_match("'^[^:]+:(?:[a-z_0-9-]+[\.]{1})*.*$'i", $url));
	}

    function render($outputMode=NULL, $skipChilds=false)
    {
        if ( __Request::exists( 'identifier' ) )
        {
            $identifier = __Request::get( 'identifier' );
            if (!self::is_valid_uri($identifier))
            {
                $this->_application->setError('badArgument', 'identifier', $identifier );
                return;
            }
        }
        else
        {
            $this->_application->setError( 'missingArgument', 'identifier' );
            return;
        }

        if ( __Request::exists( 'metadataPrefix' ) )
        {
            $metadataPrefix = __Request::get( 'metadataPrefix' );
            $metadata = $this->_application->getMetadataFormat();
            if ( isset( $metadata[$metadataPrefix] ) )
            {
                //TODO
                //$inc_record = $metadata[$metadataPrefix]['myhandler'];
            }
            else
            {
                $this->_application->setError( 'cannotDisseminateFormat', 'metadataPrefix', $metadataPrefix );
                return;
            }
        }
        else
        {
            $this->_application->setError( 'missingArgument', 'metadataPrefix' );
            return;
        }

        $id = str_replace( __Config::get( 'oaipmh.oaiPrefix' ), '', $identifier );
        if ($id == '')
        {
            $this->_application->setError( 'idDoesNotExist', '', $identifier);
            return;
        }

        list($setClassName, $docId) = explode(':', $id);

        if (!($setClassName && $docId)) {
            $this->_application->setError( 'badArgument', 'identifier', $identifier );
            return;
        }

        try {
            $this->setClass = org_glizy_ObjectFactory::createObject('metafad.oaipmh.sets.'.$setClassName);
        } catch (Exception $e) {
            $this->_application->setError( 'badArgument', 'set', $setClassName );
            return;
        }

        if ($this->setClass) {
            $setInfo = $this->setClass->getSetInfo();
            if ($this->setClass->loadRecord($docId)) {
                $output = '<GetRecord>';
                $output .= '<record>';

                // header
                $datestamp = org_glizy_oaipmh_OaiPmh::formatDatestamp( $this->arPico->picoqueue_date );
                $status_deleted = $this->arPico->picoqueue_action == 'delete' ? true : false;

                $output .= '<header'.($status_deleted ? ' status="deleted"' : '' ).'>';
                $output .= '<identifier>'.org_glizy_oaipmh_OaiPmh::encode( $identifier ).'</identifier>';
                $output .= '<datestamp>'.org_glizy_oaipmh_OaiPmh::encode( $datestamp ).'</datestamp>';
                $output .= '<setSpec>'.org_glizy_oaipmh_OaiPmh::encode( $setInfo['setSpec'] ).'</setSpec>';
                $output .= '</header>';
                $output .= '<metadata>';
                $output .= $this->setClass->getRecord($docId);
                $output .= '</metadata>';
                $output .= '</record>';
                $output .= '</GetRecord>';
                $this->addOutputCode( $output );
            } else {
                $this->_application->setError( 'idDoesNotExist', '', $identifier);
                return;
            }
        } else {
            $this->_application->setError( 'idDoesNotExist', '', $identifier);
            return;
        }
    }
}