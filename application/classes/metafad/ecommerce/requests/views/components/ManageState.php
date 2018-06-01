<?php
class metafad_ecommerce_requests_views_components_ManageState extends org_glizy_components_Component
{
    function init()
    {
      // define the custom attributes
      $this->defineAttribute('linkCssClass',     false,   'stateLink',         COMPONENT_TYPE_STRING);
      $this->defineAttribute('caretCssClass',     false,   'stateCaret',         COMPONENT_TYPE_STRING);
      parent::init();
    }

    function render()
    {
      $id = __Request::get('id');
      if($id)
      {
        $ar = org_glizy_objectFactory::createModel('metafad.ecommerce.requests.models.Model');
        if($ar->load($id))
        {
          $thisState = $ar->request_state;
        }
      }

      if(!$thisState) $thisState = 'toRead';

      $output = '<div id="'.$this->getAttribute('id').'" class="form-group"><div class="col-sm-12">';

      $flowsFile = glz_findClassPath('metafad/ecommerce/requests/json/flows.json', false, false);
      $flows = json_decode(file_get_contents($flowsFile));

      $stateList = $flows->{$ar->request_type};

      foreach ($stateList as $key => $value)
      {
        $activeClass = ($key == $thisState) ? ' stateLinkActive': '' ;
        $output .= '<div class="'.$this->getAttribute('caretCssClass').'" ><i class="fa fa-caret-right" aria-hidden="true"></i></div>';
        $output .= '<div class="'.$this->getAttribute('linkCssClass').$activeClass.'" ><a data-value="'.$key.'">'.$value.'</a></div>';
      }
      $output .= '</div></div>';
      $this->addOutputCode($output);
    }
}
