<?php
class metafad_teca_MAG_views_components_StruManagement extends org_glizy_components_Component
{
    function init()
    {
        // define the custom attributes
        $this->defineAttribute('data',     true,   '',         COMPONENT_TYPE_STRING);
        $this->defineAttribute('stru',     false,   '',         COMPONENT_TYPE_STRING);
        parent::init();
    }

    function render()
    {
      $idStru = json_decode($this->getAttribute('data'))->id;
      $logicalStru = json_decode($this->getAttribute('stru'));

      $it = org_glizy_objectFactory::createModelIterator('metafad.teca.STRUMAG.models.Model')
              ->where('document_id',$idStru)->first();
      $strumagHelper = org_glizy_objectFactory::createObject('metafad.teca.MAG.helpers.StrumagHelper');
      $stru = json_decode($it->logicalSTRU);
      $physicalStru = json_decode($it->physicalSTRU);

      $strumagHelper->setElementChecked(json_decode($logicalStru));

      //STRU LOGICA
      $output = '<div class="logicStru"><h2>Struttura logica</h2>
                 <ul class="struTree">';
      if($stru)
      {
        $output .= $strumagHelper->createTree($stru,0);
      }
      $output .= '</ul></div>';
      $output .= '<div id="showElements">';
      $output .= $strumagHelper->createShowElement($idStru);
      //STRU FISICA
      $output .= $strumagHelper->getElementsStru($physicalStru);
      $output .= '</div>';
      $this->addOutputCode($output);
    }
}
