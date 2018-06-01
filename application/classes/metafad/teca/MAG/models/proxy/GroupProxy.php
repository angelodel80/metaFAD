<?php

class metafad_teca_MAG_models_proxy_GroupProxy extends GlizyObject
{
    protected $application;

    function __construct()
    {
        $this->application = org_glizy_ObjectValues::get('org.glizy', 'application');
    }

    public function findTerm($fieldName, $model, $query, $term, $proxyParams)
    {
        $docStruProxy = $this->application->retrieveService('metafad.teca.MAG.models.proxy.DocStruProxy');
        $id = $proxyParams->id;
        $type = $proxyParams->type;

        $rootId = $docStruProxy->getRootNodeByDocumentId($id)->docstru_rootId;
        $rootNodeDocumentID =  $docStruProxy->getRootNode($rootId)->docstru_FK_document_id;

        $ar = org_glizy_ObjectFactory::createModel('metafad.teca.MAG.models.Model');
        $ar->load($rootNodeDocumentID, 'PUBLISHED_DRAFT');

        $groups = $ar->{'GEN_'.$type.'_group'};

        $result = array();
        //
        foreach($groups as $group) {
          $value = $group->{'GEN_'.$type.'_group_ID'};
          if($term != '')
          {
            if(strpos($value,$term) !== false)
            {
              $result[] = array(
                  'id' => $value,
                  'text' => $value
              );
            }
          }
          else {
            $result[] = array(
                'id' => $value,
                'text' => $value
            );
          }
        }

        return $result;
    }
}
