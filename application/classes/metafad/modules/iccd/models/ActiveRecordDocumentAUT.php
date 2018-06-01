<?php
abstract class metafad_modules_iccd_models_ActiveRecordDocumentAUT extends metafad_modules_iccd_models_ActiveRecordDocumentCommon
{
    function __construct($connectionNumber=0)
    {
        parent::__construct($connectionNumber);

        if (__Config::get('metafad.be.hasSBN')) {
            $this->addField(org_glizy_dataAccessDoctrine_DbField::create(array('name' => 'VID', 'type' => 'string', 'index' => true)));
        }
    }

    public function getSolrDocument() {
        return parent::getSolrDocument();
    }
    
}