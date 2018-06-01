<?php
class metafad_gestioneDati_boards_views_components_LinkToSbn extends org_glizy_components_Component
{
    function init()
    {
      $this->defineAttribute('type',    false,    'BID',     COMPONENT_TYPE_STRING);
      $this->defineAttribute('recordId',    false,    NULL,     COMPONENT_TYPE_STRING);
      parent::init();
    }

    function render()
    {
      $type = $this->getAttribute('type');
      $recordId = $this->getAttribute('recordId');
      $output = '<div class="'.$this->getAttribute('cssClass').'" id="'.$this->getAttribute('id').'" > Scheda SBN collegata: ';

      $module = ($type == 'BID') ? 'metafad.sbn.modules.sbnunimarc' : 'metafad.sbn.modules.authoritySBN';

      if($recordId)
      {
        $sbn = org_glizy_objectFactory::createModelIterator($module.'.model.Model')
                 ->where('id',$recordId)->first();
        $text = ($type == 'BID') ? $sbn->title[0] : $sbn->personalName[0];
        $output .= '<a target="_blank" href="'.__Link::makeUrl('actionsMVC',array('pageId'=> $module,'action'=>'show','id'=>$recordId)).'">'.
                    $recordId
                   .'</a> <span>'.$text.'</span>';

        $output .= '</div>';
        $this->addOutputCode($output);
      }
      else
      {
        $this->addOutputCode('');
      }
    }

}
