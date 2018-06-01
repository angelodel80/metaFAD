<?php
class metafad_gestioneDati_boards_views_components_AddButton extends org_glizy_components_Component
{
    function init()
    {
        // define the custom attributes
        $this->defineAttribute('label',     true,   '',         COMPONENT_TYPE_STRING);
        $this->defineAttribute('routeUrl',    false,    '',     COMPONENT_TYPE_STRING);
        $this->defineAttribute('add', false, 'Nuova scheda', COMPONENT_TYPE_STRING);
        $this->defineAttribute('dropdown', false, true, COMPONENT_TYPE_BOOLEAN);
        $this->defineAttribute('cssClass', false, '', COMPONENT_TYPE_STRING);
        $this->defineAttribute('buttonId', false, '', COMPONENT_TYPE_STRING);
        $this->defineAttribute('noLink', false, false, COMPONENT_TYPE_STRING);
        $this->defineAttribute('templateEnabled', false, true, COMPONENT_TYPE_BOOLEAN);

        parent::init();
    }

    function render()
    {
        $routeUrl = $this->getAttribute('routeUrl');
        if ($this->getAttribute('dropdown')) {
            $output = '<div id="dataGridAddButton" class="' . $this->getAttribute('cssClass') . '"><div class="btn-group btn-right-accessory">'.
                      '<a class="btn btn-info btn-flat btn-add dropdown-toggle" data-toggle="dropdown" href="#">'.
                      '<i class="fa fa-caret-down"></i> '.
                      $this->getAttribute('label').
                      '</a>'.
                      '<ul class="dropdown-menu forced-left-position">'.
                      '<li>'.__Link::makeLink($routeUrl, array('label' => 'Crea scheda vuota')).'</li>';

            if ($this->getAttribute('templateEnabled')) {
                $output .= '<li>'.__Link::makeLink($routeUrl. 'Template', array('label' => 'Crea scheda modello')).'</li>';
            }

        } else {
          $output = '<div id="dataGridAddButton" class="'.$this->getAttribute('cssClass').'">';
        }

        if ($this->getAttribute('templateEnabled')) {
            $it = org_glizy_ObjectFactory::createModelIterator($this->getAttribute('recordClassName'))
                ->where('isTemplate', 1);
            $arrayId = array();
            foreach ($it as $ar) {
              if(!in_array($ar->getId(),$arrayId))
              {
                  $output .= '<li>'.__Html::renderTag(
                      'a',
      				array(
      				    'href' => __Link::makeURL($routeUrl) . $ar->getId(),
      					'title' => glz_encodeOutput( $queryVars['title'] )
      				),
      				true,
      				'Crea scheda da: ' .$ar->templateTitle
      			).'</li>';

              $arrayId[] = $ar->getId();
              }
            }
        }

        if($this->getAttribute('dropdown'))
        {
          $output .= '</ul></div>';
        }
        if($this->getAttribute('noLink'))
        {
          $output .= __Link::makeLink('', array('label' => '<span>'.$this->getAttribute('add').'</span>', 'cssClass' => 'btn btn-info btn-flat btn-add', 'icon' => 'plusIcon fa fa-plus', 'title' => $this->getAttribute('add')),array(),'',false).'</div>';
        }
        else
        {
          $output .= __Link::makeLink($routeUrl, array('label' => '<span>'.$this->getAttribute('add').'</span>', 'cssClass' => 'btn btn-info btn-flat btn-add', 'icon' => 'plusIcon fa fa-plus' ,'title' => $this->getAttribute('add')),array(),'',false).'</div>';
        }
        $dataGridId = $this->getAttribute('dataGridAjaxId');
        $output .= <<<EOD
<script type="text/javascript">
    jQuery(function(){
        var table = jQuery('#$dataGridId').data('dataTable');
        setTimeout(function(){
            jQuery('#dataGridAddButton').prependTo("#{$dataGridId}_wrapper .filter-row");
            
        }, 100);
    });
</script>
EOD;
        $this->addOutputCode($output);
    }
}
