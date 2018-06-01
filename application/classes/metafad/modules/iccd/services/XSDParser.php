<?php
class metafad_modules_iccd_services_XSDParser extends GlizyObject
{
    protected $classPath;
    protected $moduleId;
    protected $moduleName;
    protected $elements;
    protected $fieldsAttributes = array();
    protected $iccdModuleType;
    protected $mediaTypes;

    function __construct($classPath, $moduleId, $moduleName)
    {
        $this->classPath = $classPath;
        $this->moduleId = $moduleId;
        $this->moduleName = $moduleName;

        $it = org_glizy_ObjectFactory::createModelIterator('metafad.modules.iccd.models.Authority');
        $it->where('authority_moduleId', $this->moduleId);
        foreach ($it as $ar)
            $ar->delete();
        //Lista dei campi sotto i quali inserire il collegamento al DAM
        //e relativo tipo di media da rendere selezionabile
        $this->mediaTypes = array(
                                'FTAX' => 'IMAGE',
                                'DRAX' => 'IMAGE',
                                'VDCX' => 'VIDEO',
                                'REGX' => 'AUDIO',
                                'FNTX' => 'OFFICE'
                             );
    }

    public function getFieldsAttributes()
    {
        return $this->fieldsAttributes;
    }

    public function getAuthorityFile()
    {
        return $this->fieldsAttributes['authorityFile'];
    }

    public function getIndex()
    {
        return $this->fieldsAttributes['index'];
    }

    public function getIccdModuleType()
    {
        return $this->iccdModuleType;
    }

    public function setIccdModuleType($iccdModuleType)
    {
        $this->iccdModuleType = $iccdModuleType;
    }

    protected function parseRequiredFile($xmlFile)
    {
        if (!$xmlFile) {
            return;
        }

        $xmlString = file_get_contents($xmlFile);

        $xml = new DomDocument();
        $xml->preserveWhiteSpace = false;
        $xml->loadXml($xmlString);

        $this->fieldsAttributes = array();
        $fields = $xml->getElementsByTagName('field');

        foreach ($fields as $field) {
            $fieldAttribute = array();
            $fieldName = $field->getAttribute('name');

            if ($field->getAttribute('required')) {
                $fieldAttribute['required'] = $field->getAttribute('required');
            }

            if ($field->getAttribute('index')) {
                $fieldAttribute['index'] = true;
            }

            if ($field->hasAttribute('label')) {
                $fieldAttribute['label'] = $field->getAttribute('label');
            }

            if ($field->hasChildNodes()) {
                foreach ($field->childNodes as $child) {
                    if ($child->nodeName == 'thesaurus') {
                        $thesaurusNode = $field->firstChild;

                        $dict = array();

                        foreach($thesaurusNode->childNodes as $child){
                            $dict[] = $child->getAttribute('value');
                        }

                        $fieldAttribute['thesaurus'] = $dict;
                    } else if ($child->nodeName == 'authorityFile') {
                        $thesaurusNode = $field->firstChild;
                        $module = $thesaurusNode->getAttribute('module');

                        $ar = org_glizy_objectFactory::createModel($module.'.models.Model');

                        $fields = array();

                        foreach ($ar->getFields() as $field) {
                            if (!$field->isSystemField) {
                                $fields[$field->name] = true;
                            }
                        }

                        $fieldAttribute['authorityFile'] = array (
                            'pageId' => $module.'.popup',
                            'controller' => $module.'.controllers.ajax.FindTerm',
                            'model' => $module.'.models.Model',
                            'fields' => $fields
                        );
                    }
                }
            }

            $this->fieldsAttributes[$fieldName] = $fieldAttribute;
        }
    }

    public function parseFile($xsdFile, $xmlFileRequired)
    {
        $this->parseRequiredFile($xmlFileRequired);

        $xmlString = file_get_contents($xsdFile);

        $xml = new DomDocument();
        $xml->preserveWhiteSpace = false;
        $xml->loadXml($xmlString);

        $xpath = new DOMXpath($xml);

        $sequence = $xpath->query('//xs:element[@name="scheda"]/xs:complexType/xs:sequence')->item(0);

        $this->elements = $this->parseSequence($sequence, 0);

        //var_dump($this->elements); die;

        return $this->elements;
    }

    public function parseComplexType($node, &$element, $level)
    {

        foreach ($node->childNodes as $child) {
            if ($child->nodeName == 'xs:sequence') {
                $children = $this->parseSequence($child, $level);
            } elseif ($child->nodeName == 'xs:attribute') {
                $name = $child->getAttribute('name');
                switch ($name) {
                    case 'alias':
                        $element['label'] = $child->getAttribute('fixed');
                        break;

                    case 'node_alternativeMandatory':
                        $element['mandatory'] = $node->parentNode->parentNode->parentNode->parentNode->getAttribute('name') . '-alternative';
                        break;

                    case 'node_contextMandatory':
                        $element['required'] = true;
                        break;
                }
            }
        }

        return $children;
    }

    public function parseSequence($node, $level)
    {
        $elements = array();

        foreach ($node->childNodes as $child) {
            if ($child->nodeName == 'xs:element') {
                $elements[] = $this->parseElement($child, $level);
            }
        }

        return $elements;
    }

    public function parseElement($node, $level)
    {
        $element = array(
            'name' => $node->getAttribute('name'),
            'minOccurs' => $node->getAttribute('minOccurs'),
            'maxOccurs' => $node->getAttribute('maxOccurs'),
            'required' => $node->getAttribute('minOccurs') == 1,
            'level' => $level,
        );

        $complexTypeNode = $node->firstChild;
        $child = $complexTypeNode->firstChild;

        if ($child->nodeName == 'xs:simpleContent') {
            $this->parseSimpleContent($child, $element);
        } else if ($complexTypeNode->nodeName == 'xs:complexType') {
            $element['children'] = $this->parseComplexType($complexTypeNode, $element, $level+1);
        }

        if ($this->fieldsAttributes[$element['name']]['required']) {
            $element['required'] = $this->fieldsAttributes[$element['name']]['required'];
        }

        return $element;
    }

    public function parseSimpleContent($node, &$element)
    {
        $extension = $node->firstChild;

        foreach ($extension->childNodes as $child) {
            $name = $child->getAttribute('name');
            switch ($name) {
                case 'alias':
                    $element['label'] = $child->getAttribute('fixed');
                    break;

                case 'len':
                    $len = $child->getAttribute('fixed');
                    $len = explode(',', $len);
                    $element['maxLength'] = $len[1];
                    break;

                case 'binding_thesId':
                    $element['thesaurus']['code'] = $child->getAttribute('fixed');
                    break;

                case 'binding_levelExpr':
                    $element['thesaurus']['level'] = $child->getAttribute('fixed');
                    break;
                case 'binding_parentExpr':
                    $element['parentExpression'] = $child->getAttribute('fixed');
                    break;
                case 'node_alternativeMandatory':
                    $element['mandatory'] = $node->parentNode->parentNode->parentNode->parentNode->parentNode->getAttribute('name') . '-alternative';
                    break;

                case 'node_contextMandatory':
                    $element['required'] = true;
                    break;

                case 'linking_sourceType':
                    $element['linking']['sourceType'] = $child->getAttribute('fixed');
                    break;

                case 'linking_sourceVersion':
                    $element['linking']['sourceVersion'] = $child->getAttribute('fixed');
                    break;

                case 'linking_importMapping':
                    $element['linking']['importMapping'] = $child->getAttribute('fixed');
                    break;

                case 'linking_follows':
                    $element['linking']['follows'] = $child->getAttribute('fixed');
                    break;

                case 'linking_followsAsChild':
                    $element['linking']['followsAsChild'] = $child->getAttribute('fixed');
                    break;
                case 'node_linkCards':
                    $element['linkCards'] = $child->getAttribute('fixed');
                    break;
            }
        }

        if (!empty($element['linking']['sourceType'])) {
            $autst = $element['linking']['sourceType'];
            $autsv = substr(str_replace('.', '', $element['linking']['sourceVersion']), 0, 3);

            $aut = org_glizy_ObjectFactory::createModel('metafad.modules.iccd.models.Authority');
            $aut->authority_moduleId = $this->moduleId;
            $aut->authority_autModuleId = $autst . $autsv;
            $aut->authority_importMapping = $element['linking']['importMapping'];
            $aut->authority_follows = $element['linking']['follows'];
            $aut->authority_followsAsChild = $element['linking']['followsAsChild'];
            $aut->save();
        }
    }

    public function makeHtmlElements($isAuthority,$linkFNTToArchive=null,$archiveModels=null,$ecommerce=null,$sbnweb=null)
    {
        $output = '<glz:JSTabGroup id="innerTabs" showNav="true">'.PHP_EOL;
        foreach ($this->elements as $element) {
            $output .= $this->makeElement($element, 0, $element,false,$linkFNTToArchive,$archiveModels,$ecommerce,$sbnweb);
        }

        $output .=<<<EOD
<glz:JSTab id="historyTab" label="{i18n:Storico}" routeUrl="linkHistory" cssClassTab="pull-right"/>
EOD;


        if (!$isAuthority) {
          $output .=<<<EOD
<glz:JSTab id="relationsTab" label="{i18n:Relazioni}" routeUrl="linkRelations" cssClassTab="pull-right"/>
EOD;
          if($ecommerce){
            $output .=<<<EOD
<glz:JSTab dropdown="true" id="ecommerce-tab" label="Opzioni Ecommerce">
  <glz:EmptyComponent skin="ecommerceOptions.html" />
  <glz:Input id="ecommerceLicenses" label="Licenze per vendita complessiva" maxLength="100" data="type=selectfrom;multiple=true;add_new_values=false;proxy=metafad.ecommerce.licenses.models.proxy.LicensesProxy;return_object=true;"/>
  <glz:List id="visibility" label="Visibilit&#224; FE" >
    <glz:ListItem key="rdv" value="Completa (RDV)" selected="true"/>
    <glz:ListItem key="rd" value="Visibile in ricerca e dettaglio, no viewer (RD)"/>
    <glz:ListItem key="r" value="Visibile in ricerca, no dettaglio e viewer (R)" />
    <glz:ListItem key="0" value="Non visibile"/>
  </glz:List>
</glz:JSTab>
EOD;
          }

        }
        //$output .= '<glz:JSTab id="edit" label="{i18n:Scheda}" cssClassTab="fake-active pull-right"></glz:JSTab>'.PHP_EOL;

        $output .= '</glz:JSTabGroup>'.PHP_EOL;

        return $output;
    }

    public function makeHtmlNoGroups()
    {
        $output = PHP_EOL;
        foreach ($this->elements as $element) {
            $output .= $this->makeElement($element, 0, $element,true);
        }
        $output .= PHP_EOL;
        return $output;
    }

    protected function getSelectType($element, &$attributes)
    {
        if ($element['mandatory']) {
            $selectType = 'FormEditSelectMandatory';
            $attributes['cssClass'] = 'form-control ' . $element['mandatory'] . '-mandatory';
        } elseif ($element['required']) {
            $selectType = 'FormEditSelectMandatory';
        } else {
            $selectType = 'selectfrom';
        }

        return $selectType;
    }

    public function createThesaurus($element, $attributes, $fieldName = null)
    {
        $thesaurusCode = $element['thesaurus']['code'];
        $thesaurusLevel = substr($element['thesaurus']['level'], 1); // elimina il dollaro dal livello es; $3 diventa 3
        $dict = $this->fieldsAttributes[$elementName]['thesaurus'];

        if(strpos($thesaurusCode,"VC_TSK") === 0)
        {
          $this->setIccdModuleType(str_replace("VC_TSK_","",$thesaurusCode));
        }

        $selectType = $this->getSelectType($element, $attributes);

        if ($fieldName) {
            $fieldStr = 'field='.$fieldName.';';
        }

        // se è un Vocabolario Aperto (VA) allora possono essere aggiunti nuovi termini
        if (substr($thesaurusCode, 0, 2) == "VA") {
            $attributes['data']= 'type=' . $selectType . ';'.$fieldStr.'multiple=false;add_new_values=true;proxy=metafad.modules.thesaurus.models.proxy.ThesaurusProxy;proxy_params={##module##:##'.$this->classPath.'##,##code##:##'.$thesaurusCode.'##,##level##:##'.$thesaurusLevel.'##};selected_callback=metafad.modules.thesaurus.controllers.ajax.AddTerm';
        } else { // Vocabolario Chiuso (VC)
            if (count($dict) == 1) {
                $attributes['value'] = $dict[0];
            } else {
                $attributes['data']= 'type=' . $selectType . ';'.$fieldStr.'multiple=false;add_new_values=false;proxy=metafad.modules.thesaurus.models.proxy.ThesaurusProxy;proxy_params={##module##:##'.$this->classPath.'##}';
            }
        }

        $thesaurusProxy = __objectFactory::createObject('metafad.modules.thesaurus.models.proxy.ThesaurusProxy');
        $thesaurusProxy->findOrCreate($thesaurusCode, $thesaurusCode);

        $thesaurusFormsProxy = __objectFactory::createObject('metafad.modules.thesaurus.models.proxy.ThesaurusFormsProxy');
        $thesaurusFormsProxy->findOrCreate($thesaurusCode, $this->moduleId, $this->moduleName, $element['name'], $thesaurusLevel);

        return $attributes;
    }

    public function makeElement($element, $level, $parent, $noGroup=false, $linkFNTToArchive=null,$archiveModels=null,$ecommerce=null,$sbnweb=null)
    {
        $elementName = $element['name'];
        $label = '{i18n:'.$elementName.'}';

        if ($this->fieldsAttributes[$elementName]['authorityFile']) {
            $attr = $this->fieldsAttributes[$elementName]['authorityFile'];

            $attributesInput = array(
                'id' => '__'.$elementName,
                'label' => $label,
                'maxLength' => $element['maxLength'],
                'data' => 'type=modalPage;pageid='.str_replace('.', '_', $attr['pageId']).';controller='.str_replace('.', '_', $attr['controller'])
            );

            $authorityInput = org_glizy_helpers_Html::renderTag('glz:Input', $attributesInput).PHP_EOL;
        }

        if ($element['children']) {
            $attributes = array(
                'id' => $elementName,
                'label' => $label
            );

            // se è un campo ripetibile
            if (($element['minOccurs'] == 0 && $element['maxOccurs'] == 1) || $element['maxOccurs'] > 1 || $element['maxOccurs'] == 'unbounded') {
                $att = array(
                    'type=FormEditRepeatMandatory',
                    'collapsable=false',
                    'repeatMin='.$element['minOccurs']
                );
                if ($element['maxOccurs'] != 'unbounded') {
                    $att[] = 'repeatMax='.$element['maxOccurs'];
                }
                if ($element['minOccurs'] == 0 && $element['maxOccurs'] == 1) {
                    $att[] = 'noEmptyMessage=true';
                    $att[] = 'customAddRowLabel=Aggiungi';
                }
                $attributes['data'] = implode(';', $att);
            }

            if ($element['required']) {
                $attributes['required'] = 'true';
            }

            $content = PHP_EOL;

            if ($authorityInput) {
                $content .= str_repeat('  ', $level+1).$authorityInput;
            }

            foreach ($element['children'] as $child) {
                $content .= str_repeat('  ', $level+1).$this->makeElement($child, $level+1, $element,false,$linkFNTToArchive,$archiveModels,$ecommerce);
            }

            if ($element['level'] == 0 && !$noGroup) {
                $output = '<glz:JSTab dropdown="true" id="' . $elementName . '-tab" label="' . $label . '">'.PHP_EOL;
                if($attributes['id'] == 'CD' && $sbnweb)
                {
                  $output .= '<cmp:LinkToSbn id="linkToSbn" cssClass="linkToSbn"/>'.PHP_EOL;
                }
            }

            if ($element['mandatory']) {
                $attributes['cssClass'] = 'GFEFieldset ui-sortable ' . $element['mandatory'] . '-mandatory';
            }

            $output .= org_glizy_helpers_Html::renderTag('glz:Fieldset', $attributes, true, $content).PHP_EOL;

            if ($element['level'] == 0 && !$noGroup) {
                $output .= '</glz:JSTab>' . PHP_EOL;
            }
        } else {
            $attributes = array(
                'id' => $elementName,
                'label' => $label
            );

            if ($element['required']) {
                $attributes['required'] = 'true';
            }

            if ($element['maxOccurs'] > 1 || $element['maxOccurs'] == 'unbounded') {
                $attributes['data'] = 'type=repeat;collapsable=false;repeatMin='.$element['minOccurs'].($element['maxOccurs'] == 'unbounded' ? '' : ';repeatMax='.$element['maxOccurs']);

                $content = PHP_EOL;

                if ($authorityInput) {
                    $content .= str_repeat('  ', $level+2).$authorityInput;
                }

                $attributesInput = array(
                    'id' => $elementName.'-element',
                    'label' => $label,
                    'maxLength' => $element['maxLength']
                );

                if ($attributesInput['maxLength'] >= 1000) {
                    $attributesInput['type'] = 'multiline';
                    $attributesInput['rows'] = '10';
                    $attributesInput['cols'] = '70';
                    $attributesInput['wrap'] = 'on';
                    $attributesInput['htmlEditor'] = 'true';
                }

                if ($this->fieldsAttributes[$parent['name']]['authorityFile']) {
                    $authorityFields = $this->fieldsAttributes[$parent['name']]['authorityFile']['fields'];
                    if ($authorityFields[$elementName]) {
                        $attributesInput['disabled'] = 'true';
                    }
                }

                if ($element['thesaurus']) {
                    $attributesInput = $this->createThesaurus($element, $attributesInput, $elementName);
                }
                elseif( $element['linkCards'] )
                {
                  $attributesInput['data'] = 'type=selectfrom;multiple=false;add_new_values=true;return_object=true;proxy=metafad.modules.iccd.models.proxy.IccdFormProxy';
                }

                $content .= str_repeat('  ', $level+2).org_glizy_helpers_Html::renderTag('glz:Input', $attributesInput).PHP_EOL;
                $output = org_glizy_helpers_Html::renderTag('glz:Fieldset', $attributes, true, $content).PHP_EOL;
            } else {
                $attributes['maxLength'] = $element['maxLength'];

                if ($element['thesaurus']) {
                    $attributes = $this->createThesaurus($element, $attributes);
                } elseif ($element['mandatory'] && !$element['linkCards']) {
                    $attributes['data'] = 'type=FormEditMandatory';
                    $attributes['cssClass'] = 'form-control ' . $element['mandatory'] . '-mandatory';
                }
                elseif( $element['linkCards'] )
                {
                  $attributes['data'] = 'type=selectfrom;multiple=false;add_new_values=true;return_object=true;proxy=metafad.modules.iccd.models.proxy.IccdFormProxy';
                  $attributes['cssClass'] = 'form-control ' . $element['mandatory'] . '-mandatory';
                }
                elseif($elementName == 'FNTN' && $linkFNTToArchive)
                {
                  $attributes['data'] = 'type=FormEditSelectMandatory;multiple=false;add_new_values=false;proxy=metafad.gestioneDati.boards.models.proxy.ArchiveToIccdProxy;proxy_params={##model##:##archivi.models.ComplessoArchivistico##};return_object=true;';
                }

                if ($this->fieldsAttributes[$parent['name']]['authorityFile']) {
                    $authorityFields = $this->fieldsAttributes[$parent['name']]['authorityFile']['fields'];
                    if ($authorityFields[$elementName]) {
                        $attributes['disabled'] = 'true';
                    }
                }

                if ($attributes['maxLength'] >= 1000) {
                    $attributes['type'] = 'multiline';
                    $attributes['rows'] = '10';
                    $attributes['cols'] = '70';
                    $attributes['wrap'] = 'on';
                    $attributes['htmlEditor'] = 'true';
                }

                $output = org_glizy_helpers_Html::renderTag('glz:Input', $attributes).PHP_EOL;
            }
        }
        //Aggiungo il campo per la selezione del media dal DAM
        if(array_key_exists($elementName,$this->mediaTypes))
        {
          $output .= '<glz:Panel cssClass="linkedMediaRepeaterICCD">'.PHP_EOL.
                       '<glz:Panel cssClass="col-sm-3 media-label"><glz:Text>Media</glz:Text></glz:Panel>'.PHP_EOL.
                       '<glz:Panel cssClass="col-sm-9">'.PHP_EOL.
                        '<glz:Input data="type=mediapicker;externalfiltersor=[{&quot;type&quot;:&quot;'.$this->mediaTypes[$elementName].'&quot;},{&quot;type&quot;:&quot;CONTAINER&quot;}];preview=true" id="'.substr($elementName,0,3).'-image" required="false" />'.PHP_EOL;
          if($ecommerce)
          {
            $output .= '<glz:Input id="linkedMediaEcommerce" cssClassLabel="col-sm-12 control-label" label="Ecommerce" maxLength="100" data="type=selectfrom;multiple=true;add_new_values=false;proxy=metafad.ecommerce.licenses.models.proxy.LicensesProxy;return_object=true;"/>'.PHP_EOL;
          }
          $output .= '</glz:Panel>'.PHP_EOL.'</glz:Panel>';

        }
        if($elementName == 'FNTP' && $linkFNTToArchive)
        {
          $params = '';
          $count = 1;
          foreach($archiveModels as $m)
          {
            $params .= '##model'.$count.'##:##'.$m.'##,';
            $count++;
          }
          $params = rtrim($params,",");
          $output = '<glz:Input id="FNT-unit" label="FNT - Unita\'" required="true" maxLength="50" data="type=FormEditSelectMandatory;multiple=false;add_new_values=false;proxy=metafad.gestioneDati.boards.models.proxy.ArchiveFromParentProxy;return_object=true;"/>'.PHP_EOL . $output;
        }
        return $output;
    }
}
