<?php
abstract class metafad_modules_iccd_models_ActiveRecordDocument extends metafad_modules_iccd_models_ActiveRecordDocumentCommon
{
    function __construct($connectionNumber=0)
    {
        parent::__construct($connectionNumber);

        if (__Config::get('metafad.be.hasSBN')) {
            $this->addField(org_glizy_dataAccessDoctrine_DbField::create(array('name' => 'BID', 'type' => 'string', 'index' => true)));
        }
    }
    
    public function setupTable($moduleId) 
    {
        $this->_className = $moduleId.'.models.Model';
        $this->setTableName($moduleId, org_glizy_dataAccessDoctrine_DataAccess::getTablePrefix($connectionNumber));
        $this->setType($moduleId);
    }

    public function getSolrDocument() {
        $solrModel = array(
            '__id' => 'id',
            $this->_className  => 'document_type_t',
            'updateDateTime' => 'update_at_s',
            'document' => 'doc_store',
            'isValid' => 'isValid_i',
        );

        return array_merge(parent::getSolrDocument(), $solrModel);
    }

    public function getFESolrDocument()
    {
        $solrModel = array(
            '__id' => 'id',
            $this->_className => 'document_type_t',
            'updateDateTime' => 'update_at_s',
            'document' => 'doc_store',
            'isValid' => 'isValid_i',
        );

        return $solrModel;
    }

    public function getBeMappingAdvancedSearch()
    {

        $solrModel = array(
            '__id' => 'id',
            $this->_className => 'document_type_t',
            'updateDateTime' => 'update_at_s',
            'document' => 'doc_store',
            'isValid' => 'isValid_i',
        );

        return $solrModel;
    }
}