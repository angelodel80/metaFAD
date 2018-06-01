<?php
class metafad_ecommerce_requests_views_components_RequestObjects extends org_glizy_components_Component
{
    function init()
    {
      // define the custom attributes
      $this->defineAttribute('record_id',     false,   null,         COMPONENT_TYPE_STRING);
      parent::init();
    }

    function render()
    {
      $helper = org_glizy_objectFactory::createModel('metafad.ecommerce.requests.views.helpers.ObjectInfoHelper');
      $output = '<div id="'.$this->getAttribute('id').'" class="form-group"><div class="col-sm-12">';
      $id = $this->getAttribute('record_id');
      if($id)
      {
        $output .= $helper->getInfoFromMetaindex($id);
      }
      $output .= '</div></div>';
      $this->addOutputCode($output);
    }
}
