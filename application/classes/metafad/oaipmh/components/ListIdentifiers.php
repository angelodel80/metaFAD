<?php
class metafad_oaipmh_components_ListIdentifiers extends org_glizy_components_Component
{
    protected $operationType = 'ListIdentifiers';

    function render($outputMode=NULL, $skipChilds=false)
    {
        $from = '';
        $until = '';
        $set = '';
        $limitStart = 0;

        $filters = array();

        if ( __Request::exists( 'resumptionToken' ) ) {
            if ( !__Request::exists( 'from' ) && !__Request::exists( 'until' ) && !__Request::exists( 'set' ) && !__Request::exists( 'metadataPrefix' ) ) {
                // TODO controllare la data di scadenza del token
                $tokenId = __Request::get( 'resumptionToken' );
                $fileName =  __Paths::get( 'CACHE' ).$tokenId;
                if ( file_exists( $fileName ) ) {
                    $info = unserialize( file_get_contents( $fileName ) );
                    $limitStart = $info[ 'limitEnd' ];
                    $filters = $info[ 'filters' ];
                    $metadataPrefix = $info[ 'metadataPrefix' ];
                } else {
                    $this->_application->setError( 'badResumptionToken', '', $tokenId );
                    return;
                }
            } else {
                $this->_application->setError( 'exclusiveArgument' );
                return;
            }
        } else {
            // controlla i parametri ricevuti
            if ( __Request::exists( 'from' ) ) {
                $from = __Request::get( 'from' );
                if ( !org_glizy_oaipmh_OaiPmh::checkDateFormat($from) ) {
                    $this->_application->setError( 'badGranularity', 'from', $from);
                    return;
                }
            }

            if ( __Request::exists( 'until' ) ) {
                $until = __Request::get( 'until' );
                if (!org_glizy_oaipmh_OaiPmh::checkDateFormat($until)) {
                    $this->_application->setError( 'badGranularity', 'until', $until);
                    return;
                }
            }

            if ( __Request::exists( 'set' ) ) {
                $set = __Request::get( 'set' );
            }

            if ( __Request::exists( 'metadataPrefix' ) ) {
                $metadataPrefix = __Request::get( 'metadataPrefix' );
            } else {
                $this->_application->setError( 'missingArgument', 'metadataPrefix' );
                return;
            }

            if ($from || $until) {
                $from = $from ? $from : '*';
                $until = $until ? $until : '*';
                $filters[] = 'update_at_s:['.$from.' TO '.$until.']';
            }
        }

        $models = array();
        $set = $this->_application->getSet($metadataPrefix);
        $setClass = org_glizy_ObjectFactory::createObject($set);
	    $setInfo = $setClass->getSetInfo();
	    $model = $setClass->getModelName();
	    $setSpec = $setInfo['setSpec'];

        $limitLength = __Config::get( 'oaipmh.maxIds' );

        $filters[] = 'document_type_t:"'.$model.'"';

        if ($model == 'archivi.models.ProduttoreConservatore') {
            $filters[] = 'prodCons_ss:"'.str_replace('Soggetto', '', $setSpec).'"';
        } else if ($model == 'archivi.models.ComplessoArchivistico') {
            $filters[] = '(visibility_s:r OR visibility_s:rd OR visibility_s:rdv)';
        }

        // si esportano solo le schede validate
        $filters[] = 'isValid_i:1';

        $postBody = array(
            'q' => implode(' AND ', $filters),
            'start' => $limitStart,
            'rows' => $limitLength,
            'wt' => 'json'
        );

        $request = org_glizy_objectFactory::createObject('org.glizy.rest.core.RestRequest', __Config::get('metafad.solr.url').'select?', 'POST', http_build_query($postBody));
        $request->setTimeout(1000);
        $request->setAcceptType('application/json');
        $request->execute();
        $result = json_decode($request->getResponseBody());

        $num_rows = $result->response->numFound;

        if ( $num_rows > 0 ) {
            $idPrefix = __Config::get( 'oaipmh.oaiPrefix' ).$setInfo['setSpec'].':';

            $output = '<'.$this->operationType.'>';
            $output .= org_glizy_oaipmh_OaiPmh::createResumptionToken( $this->operationType,
                array(
                    'numRows' => $num_rows,
                    'limitStart' => $limitStart,
                    'limitEnd' => $limitStart + $limitLength,
                    'filters' => $filters,
                    'metadataPrefix' => $metadataPrefix,
                )
            );

            foreach ($result->response->docs as $doc) {
                $output .= $this->makeResult($idPrefix, $doc, $setSpec, $setClass);
            }

            $output .= '</'.$this->operationType.'>';
            $this->addOutputCode( $output );
        } else {
            $this->_application->setError( 'noRecordsMatch' );
        }
    }

    protected function makeResult($idPrefix, $doc, $set, $setClass)
    {
        $identifier = $idPrefix.$doc->id;
        $datestamp = org_glizy_oaipmh_OaiPmh::formatDatestamp( $doc->update_at_s );

        $output  = '<header>';
        $output .= '<identifier>'.org_glizy_oaipmh_OaiPmh::encode( $identifier ).'</identifier>';
        $output .= '<datestamp>'.org_glizy_oaipmh_OaiPmh::encode( $datestamp ).'</datestamp>';
        $output .= '<setSpec>'.org_glizy_oaipmh_OaiPmh::encode( $set ).'</setSpec>';
        $output .= '</header>';

        return $output;
    }
}