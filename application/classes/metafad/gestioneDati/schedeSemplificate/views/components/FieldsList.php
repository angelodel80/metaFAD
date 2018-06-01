<?php
class metafad_gestioneDati_schedeSemplificate_views_components_FieldsList extends org_glizy_components_Component
{
    protected $count;
    protected $fieldsArray;
    protected $countParent;
    private $custom;

    function init(){
        $this->defineAttribute('moduleName',    false,    '',     COMPONENT_TYPE_STRING);
        $this->defineAttribute('fieldJson',    false,    '',     COMPONENT_TYPE_STRING);
        parent::init();
    }

    function render(){
        $moduleName = $this->getAttribute('moduleName');
        $this->countParent = 1;
        if($moduleName == '')
        {
          $moduleName = __Request::get('moduleNameForm');
        }

        $fieldJson = json_decode($this->getAttribute('fieldJson'))->fields;
        $fieldsArray = array();
        if($fieldJson)
        {
          foreach ($fieldJson as $f) {
            $fieldsArray[$f->field][$f->type] = true;
          }
        }
        $this->fieldsArray = $fieldsArray;

        $output = '<div id="'.$this->getAttribute('id').'">';
        if($moduleName)
        {
          $moduleService = __ObjectFactory::createObject('metafad.modules.iccd.services.ModuleService');

          $modules = org_glizy_Modules::getModules();
          $m = $modules[$moduleName];
          if($m->adminFile)
          {
            $this->custom = true;
            $adminFile = $m->adminFile;
            $elements = $moduleService->getElements($moduleName, $adminFile, true);
          }
          else
          {
            $elements = $moduleService->getElements($moduleName);
          }
      	  $elements = json_decode(json_encode($elements), true);

          //Primo livello - TAB
          foreach ($elements as $el) {
            $this->count = 1;
            $checked = ($fieldsArray[$el['name']]['tab']) ? 'checked' : '';
            $output .= '<div>
                          <h2 class="form-section">
                            <input class="checkbox-section" data-field="'.$el['name'].'" data-type="tab" type="checkbox" name="'.$el['name'].'" '.$checked.'/> '
                            .((!$this->custom) ? $el['name'].' - '.$el['label'] : $el['label']).'
                          </h2>';
            if($el['children'])
            {
              $output .= '<div class="children container-fluid">';
              $output .= '<div class="row table-section-header">
                            <div class="col-sm-8"></div>
                            <div class="col-sm-2">Visibile</div>
                            <div class="col-sm-2">Obbligatorio</div>
                          </div>';
              $output .= $this->exploreChildren($el['children'],false);
              $output .= '</div>';
            }
            $output .= '</div>';
          }
        }
        $output .= '</div>';
        $this->addOutputCode($output);
    }

    function exploreChildren($elements,$isChildren)
    {
      $output = '';
      $elementsCount = count($elements);
      $count = 1;
      foreach ($elements as $el) {
        $checkedVisible = ($this->fieldsArray[$el['name']]['visible']) ? 'checked' : '';
        $checkedMandatory = ($this->fieldsArray[$el['name']]['mandatory']) ? 'checked' : '';

        $classEven = ($this->count%2 === 0) ?'row-even':'';
        $lastChildrenClass = ($elementsCount == $count && $isChildren) ?'last-children':'';
        $whoIsParent = ($isChildren) ? 'data-parentid="'.($this->countParent - 1).'"' : '';
        $hasChildren = ($el['children']) ? true : false;
        $class = ($hasChildren) ? 'form-subsection':'' ;
        $classChildren = ($hasChildren) ? 'has-children':'' ;
        $dataParent = ($hasChildren) ? 'data-parent="'.$this->countParent.'"' : '';
        if($hasChildren)
        {
          $this->countParent++;
        }
        $output .= '<div class="row '.$classChildren.' '.$classEven.' '.$lastChildrenClass.'" '.$dataParent.' '.$whoIsParent.'>
                      <div class="'.$class.' col-sm-8">'. ((!$this->custom) ? $el['name'] . ' - ' . $el['label'] : $el['label']) .'</div>
                      <div class="col-sm-2"><input class="checkbox-subsection" type="checkbox" data-field="'.$el['name'].'" data-type="visible" name="'.$el['name'].'-visible" '.$checkedVisible.'/></div>
                      <div class="col-sm-2"><input class="checkbox-subsection" type="checkbox" data-field="'.$el['name'].'" data-type="mandatory" name="'.$el['name'].'-mandatory" '.$checkedMandatory.'/></div>
                    </div>';
        if($hasChildren)
        {
          $this->count++;
          $output .= $this->exploreChildren($el['children'],true);
        }
        else
        {
          $this->count++;
        }
        $count++;
      }
      return $output;
    }
}
