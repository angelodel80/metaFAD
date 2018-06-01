<?php
class metafad_oaipmh_components_Page extends org_glizy_mvc_components_Page
{
    protected $actionClass;

    function process()
    {
        $this->checkAcl();

        $this->sessionEx = org_glizy_ObjectFactory::createObject('org.glizy.SessionEx', $this->getId());

        $this->action = __Request::get( $this->actionName);
        $oldAction     = $this->sessionEx->get( $this->actionName );

        foreach ( $this->childComponents  as $c )
        {
            if ( is_a( $c, 'org_glizy_mvc_components_State' ) )
            {
                $c->deferredChildCreation();
            }
        }

        $this->callController();
        $this->canCallController = true;
        $this->action = strtolower( $this->action );
        org_glizy_components_Page::process();

        if ($this->action) {
            $isStateActive = false;
            $numStates = 0;
            foreach ( $this->childComponents  as $c ) {
                if ( is_a( $c, 'org_glizy_mvc_components_State' ) ) {
                    $numStates++;
                    $isStateActive = $isStateActive || $c->isCurrentState();
                }
            }

            if (!$isStateActive && $numStates) {
                $this->_application->setError( 'badVerb', $this->action );
            }
        }

        $this->sessionEx->set( $this->actionName, $oldAction );
    }

    function process_ajax()
    {
        return false;
    }

    /**
     * Render
     *
     * @return    string
     * @access    public
     */
    function render()
    {
        $this->renderChilds();

        $responseDate = gmstrftime('%Y-%m-%dT%T').'Z';
        $error = $this->_application->getError();
        $requestAttribs = '';
        $content = '';

        if ( empty( $error ) )
        {

            for ($i=0; $i<count($this->_output); $i++)
            {
                if ( $this->_output[$i]['editableRegion'] != 'content' ) continue;
                $content .= $this->_output[$i]['code'];
            }

            $params    = &org_glizy_Request::_getValuesArray();
            unset($params['__url__']);
            unset($params['__back__url__']);
            foreach($params    as $k=>$v)
            {
                if ( $v[ GLZ_REQUEST_TYPE ] == GLZ_REQUEST_GET )
                {
                    $requestAttribs .= ' '.$k.'="'.htmlentities( $v[GLZ_REQUEST_VALUE] ).'"';
                }
            }
        }
        else
        {
            $content = $error;
        }
        $requestUrl = org_glizy_Routing::$baseUrl;
        $charset = GLZ_CHARSET;
        $output = <<<EOD
<?xml version="1.0" encoding="$charset"?>
<OAI-PMH xmlns="http://www.openarchives.org/OAI/2.0/"
         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/
         http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd">
        <responseDate>$responseDate</responseDate>
        <request $requestAttribs>$requestUrl</request>
        $content
</OAI-PMH>
EOD;


        return $output;
    }
}