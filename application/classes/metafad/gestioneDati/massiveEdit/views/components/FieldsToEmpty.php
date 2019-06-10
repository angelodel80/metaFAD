<?php
class metafad_gestioneDati_massiveEdit_views_components_FieldsToEmpty extends org_glizy_components_Component
{
    protected $count;
    protected $fieldsArray;
    protected $countParent;
    private $custom;

    function init()
    {
        $this->defineAttribute('moduleName', false, '', COMPONENT_TYPE_STRING);
        $this->defineAttribute('fieldJson', false, '', COMPONENT_TYPE_STRING);
        parent::init();
    }

    function render()
    {
        $moduleName = str_replace('.models.Model','',$this->getAttribute('moduleName'));
        $this->countParent = 1;
        if ($moduleName == '') {
            $moduleName = __Request::get('moduleNameForm');
        }

        $fieldJson = json_decode($this->getAttribute('fieldJson'))->fields;
        $fieldsArray = array();
        if ($fieldJson) {
            foreach ($fieldJson as $f) {
                $fieldsArray[$f->field][$f->type] = true;
            }
        }
        $this->fieldsArray = $fieldsArray;

        $output = '<fieldset id="delete-fieldset"><div class="border-legend"></div><legend style="font-weight:normal"><i id="fieldset-icon" class="fa fa-plus"></i> Campi da svuotare</legend>';

        $output .= '<div id="' . $this->getAttribute('id') . '" class="hide">';
        if ($moduleName) {
            $moduleService = __ObjectFactory::createObject('metafad.modules.iccd.services.ModuleService');

            $modules = org_glizy_Modules::getModules();
            $m = $modules[$moduleName];
            if ($m->adminFile) {
                $this->custom = true;
                $adminFile = $m->adminFile;
                $elements = $moduleService->getElements($moduleName, $adminFile, true);
            } else {
                $elements = $moduleService->getElements($moduleName);
            }
            $elements = json_decode(json_encode($elements), true);

          //Primo livello - TAB
            foreach ($elements as $el) {
                $this->count = 1;
                $checked = ($fieldsArray[$el['name']]['tab']) ? 'checked' : '';
                $output .= '<div>
                          <h2 class="form-section">'
                    . ((!$this->custom) ? $el['name'] . ' - ' . $el['label'] : $el['label']) . '
                          </h2>';
                if ($el['children']) {
                    $output .= '<div class="children container-fluid">';
                    $output .= '<div class="row table-section-header">
                            <div class="col-sm-8"></div>
                            <div class="col-sm-4">Svuota campo</div>
                          </div>';
                    $output .= $this->exploreChildren($el['children'], false);
                    $output .= '</div>';
                }
                $output .= '</div>';
            }
        }
        $output .= '</div></fieldset>';

        $output .= "<script>
        $(document).ready(function(){
            $('#delete-fieldset').on('click',function(){
                $('#" . $this->getAttribute('id') . "').toggleClass('hide');
                $('#fieldset-icon').toggleClass('fa-plus');
                $('#fieldset-icon').toggleClass('fa-minus');
            });
        });
        </script>";
        $this->addOutputCode($output);
    }

    function exploreChildren($elements, $isChildren)
    {
        $output = '';
        $elementsCount = count($elements);
        $count = 1;
        foreach ($elements as $el) {
            $checkedVisible = ($this->fieldsArray[$el['name']]['visible']) ? 'checked' : '';
            $checkedMandatory = ($this->fieldsArray[$el['name']]['mandatory']) ? 'checked' : '';

            $classEven = ($this->count % 2 === 0) ? 'row-even' : '';
            $lastChildrenClass = ($elementsCount == $count && $isChildren) ? 'last-children' : '';
            $whoIsParent = ($isChildren) ? 'data-parentid="' . ($this->countParent - 1) . '"' : '';
            $hasChildren = ($el['children']) ? true : false;
            $class = ($hasChildren) ? 'form-subsection' : '';
            $classChildren = ($hasChildren) ? 'has-children' : '';
            $dataParent = ($hasChildren) ? 'data-parent="' . $this->countParent . '"' : '';
            if ($hasChildren ) {
                $this->countParent++;
            }
            if ($hasChildren && $el['minOccurs'] == 1 && $el['maxOccurs'] == 1) {
                $output .= '<div class="row ' . $classChildren . ' ' . $classEven . ' ' . $lastChildrenClass . '" ' . $dataParent . ' ' . $whoIsParent . '>
                      <div class="' . $class . ' col-sm-8">' . ((!$this->custom) ? $el['name'] . ' - ' . $el['label'] : $el['label']) . '</div>
                    </div>';
                $this->count++;
                $output .= $this->exploreChildren($el['children'], true);
            } else {
                $output .= '<div class="row ' . $classChildren . ' ' . $classEven . ' ' . $lastChildrenClass . '" ' . $dataParent . ' ' . $whoIsParent . '>
                      <div class="' . $class . ' col-sm-8">' . ((!$this->custom) ? $el['name'] . ' - ' . $el['label'] : $el['label']) . '</div>
                      <div class="col-sm-2 form-group"><input id="empty-' . $el['name'] . '" name="empty-' . $el['name'] . '" class="checkbox-subsection js-empty-field form-control" type="checkbox" value="false" /></div>
                    </div>';
                $this->count++;
            }

            $count++;
        }
        return $output;
    }
}
