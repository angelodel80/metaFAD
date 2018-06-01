<?php
class metafad_oaipmh_core_Application extends org_glizy_oaipmh_core_Application
{
    function _init()
    {
        org_glizy_mvc_core_Application::_init();
        $this->contentType = 'text/xml';
        __Paths::set( 'APPLICATION_PAGE_TYPE', __DIR__.'/../pageTypes/' );
    }

	public function addSet( $metadataPrexif, $classPath )
	{
		$this->sets[$metadataPrexif] = $classPath;
	}

	public function getSet($metadataPrexif)
	{
		return $this->sets[$metadataPrexif];
	}
}
