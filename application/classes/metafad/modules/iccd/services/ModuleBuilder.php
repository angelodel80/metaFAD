<?php
class metafad_modules_iccd_services_ModuleBuilder extends GlizyObject
{
    protected $moduleName;
    protected $classPath;
    protected $moduleId;

    function __construct($moduleName)
    {
        $this->moduleName = $moduleName;
        $this->classPath = preg_replace('/\W+/', '', $moduleName);
        $this->moduleId = str_replace('.', '_', $this->classPath);
    }

    public function getModuleId()
    {
        return $this->moduleId;
    }

    public function getClassPath()
    {
        return $this->classPath;
    }

    public function createModule($options,$reindex=true,$fe=false,$be=true)
    {
        $elements = $options['elements'];
        $htmlElements = $options['htmlElements'];
        $htmlNoGroups = $options['htmlNoGroups'];
        $fieldsAttributes = $options['fieldsAttributes'];
        $siteMapParentNode = $options['siteMapParentNode'];
        $sbnweb = $options['sbnweb'];
        $isAuthority = $options['isAuthority'];
        $titleField = $options['titleField'];
        $searchable = $options['searchable'];
        $gridFields = $options['gridFields'];
        $iccdModuleType = $options['iccdModuleType'];
        $feMapping = $options['feMapping'];
        $beMapping = $options['beMapping'];
        $linkFNTToArchive = $options['linkFNTToArchive'];

        if ($options['index']) {
            foreach ($options['index'] as $fieldToIndex) {
                $fieldsAttributes[$fieldToIndex]['index'] = true;
            }
        }

        $aclPageTypes = array(
            $this->moduleId,
            $this->moduleId.'_delete',
            $this->moduleId.'_preview'
        );

        if ($isAuthority) {
            $aclPageTypes[] = $this->moduleId.'_popup';
        }

        $aclPageTypes = implode(',', $aclPageTypes);

        $moduleVO = new org_glizy_ModuleVO();
        $moduleVO->id = $this->moduleId;
        $moduleVO->name = $this->classPath.'.views.FrontEnd';
        $moduleVO->description = ''; // TODO
        $moduleVO->version = '1.0.0';
        $moduleVO->pageType = $this->classPath.'.views.FrontEnd';
        $moduleVO->model = $this->classPath.'.models.Model';
        $moduleVO->classPath = $this->classPath;
        $moduleVO->author = 'META srl';
        $moduleVO->authorUrl = 'http://www.gruppometa.it';
        $moduleVO->pluginUrl = 'http://www.metafadcms.it';
        $moduleVO->iccdModuleType = $iccdModuleType;
        $moduleVO->siteMapAdmin =<<<EOD

<glz:Page id="{$moduleVO->id}" value="{i18n:$moduleVO->pageType}" pageType="{$this->classPath}.views.Admin" parentId="$siteMapParentNode" adm:acl="*"/>
<glz:Page id="{$moduleVO->id}_preview" pageType="{$this->classPath}.views.AdminPreview" parentId="" adm:acl="*" />
EOD;

        //Se scheda autorithy, aggiungo riferimento a AdminPopup
        if ($isAuthority) {
            $moduleVO->isAuthority = 'true';
            $moduleVO->siteMapAdmin .= '<glz:Page pageType="'.$moduleVO->classPath.'.views.AdminPopup" id="'.$moduleVO->id.'_popup" visible="true" parentId="" />';
        } else {
            $moduleVO->isAuthority = 'false';
            $moduleVO->siteMapAdmin .=<<<EOD

<glz:Page id="{$this->classPath}_export" value="{i18n:$moduleVO->pageType}" pageType="{$this->classPath}.views.AdminExport" parentId="export/patrimonio" icon="fa fa-angle-double-right" adm:acl="*" />
EOD;
        }
        $moduleVO->canDuplicated = false;
        $moduleVO->isICCDModule = 'true';

        $modulePath = __Paths::get( 'APPLICATION_TO_ADMIN' ).'classes/userModules/'.$this->classPath.'/';

        @mkdir($modulePath);
        @mkdir($modulePath.'config');
        @mkdir($modulePath.'locale');
        @mkdir($modulePath.'js');
        @mkdir($modulePath.'models');
        @mkdir($modulePath.'views');

        $jsonElements = json_encode($elements);
        file_put_contents($modulePath.'models/elements.json', $jsonElements);

        $this->createRouting($modulePath, $moduleVO);
        $this->createLocale($this->moduleName, $modulePath, $moduleVO, $fieldsAttributes, $elements);
        $this->createModelFile($modulePath, $moduleVO, $elements, $fieldsAttributes, $titleField, $searchable, $gridFields,$isAuthority, $iccdModuleType, $feMapping, $beMapping, $options['findTermFields']);
        $this->createAdminFile($modulePath, $moduleVO, $htmlElements, $sbnweb, $gridFields, $iccdModuleType,$isAuthority,$linkFNTToArchive);
        $this->createAdminExportFile($modulePath, $moduleVO, $gridFields);

        if ($isAuthority) {
            @mkdir($modulePath.'controllers');
            @mkdir($modulePath.'controllers/ajax');

            $this->createAdminPopupFile($modulePath, $moduleVO, $htmlElements, $gridFields);
            $this->createControllerFindTerm($modulePath, $moduleVO, $options['findTermFields']);
        }

        $this->createAdminPreview($modulePath, $moduleVO, $htmlNoGroups, $gridFields);

        if ($options['createFrontEndFile']) {
            $this->createFrontEndFile($modulePath, $moduleVO, $elements);
        }

        $this->createJS($modulePath, $moduleVO, $elements);
        $this->createModuleFile($modulePath, $moduleVO);
        $this->createSkins($modulePath, $moduleVO, $elements);
        $this->addModuleToStartup($modulePath, $moduleVO);

        if($reindex)
        {
          if($be)
          {
            $this->solrReindex($moduleVO->classPath.'.models.Model');
          }
          if(__Config::get('metafad.be.hasFE') && $fe)
          {
            $this->solrReindexFE($moduleVO->classPath.'.models.Model');
          }
        }
        org_glizy_cache_CacheFile::cleanPHP();
    }

    public function solrReindex($model)
    {
      //Recupero tutti i dati precedentemente salvati per questo
      //tipo di scheda, sia draft che published
      $it = org_glizy_objectFactory::createModelIterator($model)
            ->setOptions(array('type' => 'PUBLISHED_DRAFT'));

      $uniqueIccdProxy = __ObjectFactory::createObject('metafad.gestioneDati.boards.models.proxy.UniqueIccdIdProxy');

      $total = $it->count();
      foreach ($it as $ar) {
        $data = (object)$ar->getValuesAsArray();
        $data->__model = $model;
        $data->__id = $ar->document_id;

        $cl = new stdClass();
        $cl->className = $ar->getClassName(false);
        $cl->isVisible = $ar->isVisible();
        $cl->isTranslated = $ar->isTranslated();
        $cl->hasPublishedVersion = $ar->hasPublishedVersion();
        $cl->hasDraftVersion = $ar->hasDraftVersion();
        $cl->document_detail_status = $ar->getStatus();
        $data->document = json_encode($cl);

        $data->uniqueIccdId = $uniqueIccdProxy->createUniqueIccdId($data);

        $evt = array('type' => 'insertRecord', 'data' => array('data' => $data, 'option' => array('commit' => true,'reindex' => true,'total'=>$total)));
        $this->dispatchEvent($evt);
      }
    }

    public function solrReindexFE($model)
    {
      //Recupero tutti i dati precedentemente salvati per questo
      //tipo di scheda, solo published
      $it = org_glizy_objectFactory::createModelIterator($model);

      //Controllo per evitare di entrare nell'iterator per le schede senza mapping
      $solrModel = org_glizy_objectFactory::createModel($model);
      $solrDocument = $solrModel->getFESolrDocument();

      $type = 'iccd';

      //TODO DA RIMUOVERE -> Temporaneo fino a che non faremo l'indice FE
      if(strpos($model,'AUT') === 0 || strpos($model,'BIB') === 0)
      {
        $type = 'iccdaut';
		$isAut = true;
      }
      else if(!$solrDocument['feMapping'])
      {
        return;
      }

      foreach ($it as $ar) {
        $data = (object)$ar->getValuesAsArray();
        //Escludo i template della reindicizzazione
        if($data->isTemplate)
        {
          continue;
        }
        $data->__model = $model;
        $data->__id = $ar->document_id;

        $evt = array('type' => 'insertRecordFE', 'data' => array('data' => $data, 'option' => array('commit' => true,'aut' => $isAut)));
        $this->dispatchEvent($evt);

		if(__Config::get('metafad.fe.hasMetaindice'))
		{
	        $metaindice = org_glizy_ObjectFactory::createObject('metafad.solr.helpers.MetaindiceHelper');
	        $metaindice->mapping($data,$type);
		}
      }
    }

    protected function createJS($modulePath, $moduleVO, $elements)
    {
        $filename = $modulePath.'js/vocabularyLevel.js';
        $elementWithParent = array();
        //Estraggo tutti gli elementi che hanno parentExpression settato
        $this->getElementsWithParentExpression($elements,$elementWithParent);

        if ($elementWithParent) {
            $elementsList = array();

            foreach ($elementWithParent as $name => $element) {
                $elName = end(explode("/",$element));
                $elementsList[] = "['".$elName."','".$name."']";
            }

            $elementsArray = '['.implode(',', $elementsList).']';

            $content .=<<<EOD
$(document).ready(function(){
    Glizy.events.on("glizycms.formEdit.onReady", function(){
        $.each($elementsArray, function(i, item){
            if ($('#'+item[0]).val()) {
                initializeSelectAfterParent($('#'+item[0]), item[1]);
            }

            $('input[name='+item[0]+']').on('change', function(){
                initializeSelectAfterParent($(this), item[1]);
            });
        });
    });

    function initializeSelectAfterParent(el,name)
    {
        if (el.select2('data')) {
            var parentKey = el.select2('data').id;
            var rsec = el.closest('fieldset').find('input[name='+name+']');
            var proxyParams = rsec.data('proxy_params');
            proxyParams = proxyParams.replace("##}","##,##parentKey##:##"+parentKey+"##}");
            var instance = rsec.data('instance');
            var value = instance.getValue();
            instance.\$element.data('proxy_params',proxyParams);
            instance.initialize(instance.\$element);
            if (value) {
                instance.setValue(value);
            }
        }
    }
});
EOD;
        }

        @file_put_contents($filename, $content);
    }

    protected function getElementsWithParentExpression($elements,&$elementWithParent)
    {
      foreach ($elements as $element) {
        if($element['parentExpression'] != null)
        {
          $elementWithParent[$element['name']] = $element['parentExpression'];
        }
        if($element['children'] != null)
        {
          $this->getElementsWithParentExpression($element['children'],$elementWithParent);
        }
      }
    }

    protected function createRouting($modulePath, $moduleVO)
    {
        $filename = $modulePath.'config/routing.xml';

        $content = <<<EOD
<?xml version="1.0" encoding="utf-8"?>
<glz:Routing>
    <glz:Route name="{$moduleVO->classPath}" value="{pageId={$moduleVO->pageType}}/{static=state=show}/{integer=document_id}/{value=catalogdetail_title}" />
</glz:Routing>
EOD;
        @file_put_contents($filename, $content);
    }

    // TODO prendere i dati da $elements invece che da $fieldsAttributes
    protected function createLocale($moduleName, $modulePath, $moduleVO, $fieldsAttributes, $elements)
    {
        $filename = $modulePath.'locale/it.php';

        $labelsMapping = '';
        foreach ($fieldsAttributes as $fieldName => $fieldAttributes) {
            if ($fieldAttributes['label']) {
                $labelsMapping .= '    "'.$fieldName.'" => "'.$fieldName.' - '.$fieldAttributes['label'].'",'.PHP_EOL;
            }
        }

        $this->getChildrenForLocale($elements, $labelsMapping);

        $content = <<<EOD
<?php
\$strings = array (
    "{$moduleVO->pageType}" => "$moduleName",
$labelsMapping
);
org_glizy_locale_Locale::append(\$strings);
EOD;
        @file_put_contents($filename, $content);
    }

    protected function createModelFile($modulePath, $moduleVO, $elements, $fieldsAttributes, $titleField, $searchable, $gridFields, $isAuthority, $iccdModuleType, $feMapping, $beMapping, $findTermFields)
    {
        $baseClass = $isAuthority ? 'metafad.modules.iccd.models.ActiveRecordDocumentAUT' : 'metafad.modules.iccd.models.ActiveRecordDocument';
        $filename = $modulePath.'models/Model.xml';
        $feMapping = ($feMapping) ? "'".json_encode($feMapping)."'" : "''";
        $beMapping_encoded = ($beMapping) ? "'".json_encode($beMapping)."'" : "''";
        $modelFields = $this->makeModel($elements, $fieldsAttributes);

        $searchFields = '';
        foreach ($searchable as $searchField) {
            if ($beMapping && property_exists($beMapping, $searchField)) {
                $f = str_replace(array(":",'(',')'),'_',strtolower($searchField));
                $f = str_replace(' ','_',$f);
                $f = str_replace('__','_',$f);
                $searchFields .= "\t\t\t'".$searchField."' => '".$f."_txt',".PHP_EOL;
            } else {
                $searchFields .= "\t\t\t'".$searchField."' => '".$searchField."_t',".PHP_EOL;
            }
        }

        $solrModelCustom = '';
        foreach ($gridFields as $gridField) {
            $solrModelCustom .= "\t\t\t'".$gridField."' => '".$gridField."_s,".$gridField."_t',".PHP_EOL;
        }

        if ($findTermFields) {
            $findTermFields = '"'.implode('","', $findTermFields).'"';
$findTermFieldsCode = <<<EOD
public function getFindTermFields()
    {
        return array($findTermFields);
    }
EOD;
        }

        $content = <<<EOD
<?xml version="1.0" encoding="utf-8"?>
<model:Model
    xmlns:glz="http://www.glizy.org/dtd/1.0/"
    xmlns:model="http://www.glizy.org/dtd/1.0/model/"
    model:tableName="{$moduleVO->classPath}"
    model:usePrefix="true"
    model:type="document"
    model:baseClass="$baseClass">

    <model:Script parent="model">
    <![CDATA[
    public function getTitle()
    {
        return \$this->$titleField ? \$this->$titleField : null;
    }

    public function getSolrDocument()
    {
        \$solrModel = array(
            '__id' => 'id',
            '{$moduleVO->model}' => 'document_type_t',
            'updateDateTime' => 'update_at_s',
            'document' => 'doc_store',
            'isValid' => 'isValid_i',
$solrModelCustom
        );

        \$solrModel = array_merge(parent::getSolrDocument(), \$solrModel);

        return \$solrModel;
    }

    public function getFESolrDocument()
    {
        \$solrModel = array(
            '__id' => 'id',
            '{$moduleVO->model}' => 'document_type_t',
            'updateDateTime' => 'update_at_s',
            'document' => 'doc_store',
            'isValid' => 'isValid_i',
            'feMapping' => $feMapping
        );

        return \$solrModel;
    }

    public function getBeMappingAdvancedSearch()
    {
        \$solrModel = array(
            '__id' => 'id',
            '{$moduleVO->model}' => 'document_type_t',
            'updateDateTime' => 'update_at_s',
            'document' => 'doc_store',
            'isValid' => 'isValid_i',
            'beMapping' => $beMapping_encoded
        );

        return \$solrModel;
    }

    public function getBeAdvancedSearchFields()
    {
        \$searchFields = array(
$searchFields
        );

        return \$searchFields;
    }

    public function getRecordId()
    {
        \$uniqueIccdIdProxy = org_glizy_ObjectFactory::createObject('metafad.gestioneDati.boards.models.proxy.UniqueIccdIdProxy');
        return \$uniqueIccdIdProxy->createUniqueIccdId(\$this);
    }

    $findTermFieldsCode

    ]]>
    </model:Script>

    <model:Define>
$modelFields
    </model:Define>
</model:Model>
EOD;
        @file_put_contents($filename, $content);
    }

    protected function makeModel($elements, $fieldsAttributes)
    {
        $output = '';

        foreach ($elements as $element) {
            $output .= $this->makeModelField($element, $fieldsAttributes);
        }

        return $output;
    }

    protected function makeModelField($element, $fieldsAttributes)
    {
        $output = '';
        $elementName = $element['name'];

        $attributes = array(
            'name' => $elementName,
        );

        if ($element['required'] == 'true') {
            $attributes['validator'] = 'notempty';
        }

        if ($fieldsAttributes[$elementName]['authorityFile']) {
            $attributes['option'] = $fieldsAttributes[$elementName]['authorityFile']['model'];
        }

        if ($fieldsAttributes[$elementName]['index']) {
            $attributes['index'] = 'true';
        }

        if ($element['children']) {
            // se è un campo ripetibile
            if (($element['minOccurs'] == 0 && $element['maxOccurs'] == 1) || $element['maxOccurs'] > 1 || $element['maxOccurs'] == 'unbounded') {
                $attributes['type'] = 'object';
                $attributes['readFormat'] = 'false';
            } else {
                foreach ($element['children'] as $child) {
                    $output .= $this->makeModelField($child, $fieldsAttributes);
                }
                return $output;
            }
        } else {
            if ($element['maxLength'] >= 1000) {
                $type = 'text';
            } else {
                $type = 'string';
            }

            // se è un campo ripetibile
            if ($element['maxOccurs'] > 1 || $element['maxOccurs'] == 'unbounded') {
                $attributes['type'] = 'object';
                $attributes['readFormat'] = 'false';
            } else {
                $attributes['type'] = $type;
                $attributes['length'] = $element['maxLength'];
            }
        }

        $output = org_glizy_helpers_Html::renderTag('model:Field', $attributes).PHP_EOL;

        return $output;
    }

    protected function createAdminFile($modulePath, $moduleVO, $htmlElements, $sbnweb, $gridFields,$iccdModuleType,$isAuthority,$linkFNTToArchive)
    {
        $filename = $modulePath.'views/Admin.xml';
        if (!$isAuthority)
        {
          $showImagesComponent = <<<EOD
<glz:template name="extra_components">
  <cmp:LinkedImages id="linkedImages" model="{$moduleVO->classPath}"/>
  <glz:JSscript folder="metafad/gestioneDati/showImages/js"/>
</glz:template>
EOD;
          $showImages = <<<EOD
          <glz:Link id="link-show-images" icon="fa fa-picture-o" label="Mostra immagini" editableRegion="actions" cssClass="link showImages"/>
EOD;
        } else {
            $templateParams = <<<EOD
<glz:template name="filterByInstitute" value="false"/>
<glz:template name="documentRenderCell" value="metafad.common.views.renderer.authority.CellEditDraftDelete"/>
EOD;
        }

        if($linkFNTToArchive)
        {
          $FNTjs = <<<EOD
<glz:JSscript folder="metafad/gestioneDati/boards/js"/>
EOD;
        }
        if ($sbnweb) {
            $sbnwebElements = <<<EOD
            <glz:Hidden data="type=modalPageSBN;pageid=metafad.gestioneDati.sbnweb_popup;formtype=$iccdModuleType" id="popup"/>
            <glz:Link id="link-show-sbn" icon="fa fa-upload" label="Collega scheda SBN WEB" editableRegion="actions" cssClass="link SBN"/>
            <glz:JSscript folder="metafad/gestioneDati/sbnweb/js"/>
            <glz:Hidden id="BID"/>
EOD;
        }

        if ($isAuthority && $iccdModuleType != 'BIB') {
            //Parametro versione scheda per servizio SBN AUT
            $version = (strpos($moduleVO->classPath,'300') !== false)?'3':'4';
            $sbnwebElements = <<<EOD
            <glz:Hidden data="type=modalPageSBNAUT;pageid=metafad.gestioneDati.sbnaut_popup;formtype=$iccdModuleType;version=$version" id="popup"/>
            <glz:Hidden id="VID"/>
            <glz:Link id="link-show-sbn" icon="fa fa-upload" label="Collega scheda SBN" editableRegion="actions" cssClass="link SBN"/>
            <glz:JSscript folder="metafad/gestioneDati/sbnaut/js"/>
EOD;
        }

        $dataGridColumns = $this->createDataGridColums($gridFields);

        if (!$isAuthority) {
            $dataGridColumns .=<<<EOD
        <com:DataGridColumn columnName="digitale_i" width="20px" checkbox="true" sortable="false" searchable="false"
                            headerText="{i18n:Digitale}" cssClass="center"
                            renderCell="org.glizycms.core.application.renderer.CellIsChecked"/>
EOD;
        }

        $content = <<<EOD
<?xml version="1.0" encoding="utf-8"?>
<glz:include
    xmlns:glz="http://www.glizy.org/dtd/1.0/"
    xmlns:com="metafad.common.views.components.*"
    xmlns:cms="org.glizycms.views.components.*"
    xmlns:cmp="metafad.gestioneDati.boards.views.components.*"
    xmlns:mvc="org.glizy.mvc.components.*"
    xmlns:c="metafad.modules.iccd.views.components.*"
	  src="metafad.views.TemplateModuleAdmin">

$templateParams
    <glz:template name="model" value="{$moduleVO->model}"/>
    <glz:template name="show_external_id" value="false"/>
    <glz:template name="controller_name" value="metafad.gestioneDati.boards.controllers.*"/>
    <glz:template name="autocompleteController" value="metafad.common.controllers.ajax.AutoComplete"/>
    <glz:template name="grid_fields">
$dataGridColumns
        <glz:DataGridColumn columnName="isValid_i" width="20px" sortable="false" searchable="false"
                            headerText="{i18n:Validata}" cssClass="center"
                            renderCell="org.glizycms.core.application.renderer.CellIsChecked"/>
        <glz:DataGridColumn columnName="document_detail_status" width="20px" sortable="false" searchable="false"
                            headerText="{i18n:Pubblicata}" cssClass="center"
                            renderCell="metafad.common.views.renderer.CellDocPublished"/>
    </glz:template>

    $showImagesComponent

    <glz:template name="form_fields">
        <glz:Hidden id="isTemplate"/>
        <glz:Hidden data="type=modalPagePreview;pageid={$moduleVO->id}_preview;" id="popupPreview"/>
        <glz:Input id="templateTitle" label="{i18n:Titolo template}" required="true"/>
        <glz:JSscript folder="metafad/modules/iccd/js"/>
        <glz:JSscript folder="userModules/{$moduleVO->classPath}/js"/>
        $showImages

        $sbnwebElements

        $FNTjs

        $htmlElements
    </glz:template>

    <glz:template name="custom_states">
      <mvc:State name="history" label="{i18n:Storico}" url="linkHistory">
        <glz:Hidden controllerName="##controller_name##" />
        <glz:JSTabGroup id="innerTabs">
          <glz:JSTab id="historyTab" label="{i18n:Storico}" cssClassTab="pull-right">
            <cms:FormEdit addValidationJs="false">
              <cmp:ShowHistory id="history" model="{$moduleVO->model}"/>
              <cms:FormButtonsPanel>
                <glz:HtmlButton label="{i18n:Confronta}" type="button" cssClass="btn btn-primary js-glizycms-history" data="action=add" />
              </cms:FormButtonsPanel>
            </cms:FormEdit>
          </glz:JSTab>
EOD;
      if(!$isAuthority)
      {
        $content .=<<<EOD
<glz:JSTab id="relationsTab" label="{i18n:Relazioni}" routeUrl="linkRelations" cssClassTab="pull-right"/>
EOD;
      }
      $content .=<<<EOD

          <glz:JSTab id="editTab" label="{i18n:Scheda}" routeUrl="linkEdit" cssClassTab="pull-right"/>
        </glz:JSTabGroup>
      </mvc:State>
EOD;
      if(!$isAuthority)
      {
        $content .=<<<EOD
      <mvc:State name="relations" label="{i18n:Relazioni}" url="linkRelations">
        <glz:Hidden controllerName="##controller_name##" />
        <glz:JSTabGroup id="innerTabs">
          <glz:JSTab id="relationsTab" label="{i18n:Relazioni}" cssClassTab="pull-right">
            <cmp:ComplexRelation id="complexRelation" />
            <glz:DataProvider id="RelationDP" query="getRelations" recordClassName="metafad.gestioneDati.boards.models.Relations" />
            <glz:RecordSetList id="relations" processCell="metafad.gestioneDati.boards.views.renderer.Relations" dataProvider="{RelationDP}" skin="relations.html">
            </glz:RecordSetList>
            <glz:DataProvider id="InverseRelationDP" query="getInverseRelations" recordClassName="metafad.gestioneDati.boards.models.Relations" />
            <glz:RecordSetList id="inverseRelations" processCell="metafad.gestioneDati.boards.views.renderer.InverseRelations" dataProvider="{InverseRelationDP}" skin="relationsParent.html">
            </glz:RecordSetList>
            <cmp:RozRelations id="rozRelation" />
          </glz:JSTab>
          <glz:JSTab id="historyTab" label="{i18n:Storico}" routeUrl="linkHistory" cssClassTab="pull-right"/>
          <glz:JSTab id="editTab" label="{i18n:Scheda}" routeUrl="linkEdit" cssClassTab="pull-right"/>
        </glz:JSTabGroup>
      </mvc:State>
EOD;
      }

      if ($isAuthority) {
          $htmlElementsReadOnly = str_replace('<glz:JSTab id="historyTab" label="{i18n:Storico}" routeUrl="linkHistory" cssClassTab="pull-right"/>', '', $htmlElements);
          $htmlElementsReadOnly = str_replace('<glz:Input id=', '<glz:Input readOnly="true" id=', $htmlElementsReadOnly);
          $htmlElementsReadOnly = preg_replace('/<glz:Fieldset (.+) data="/', '<glz:Fieldset $1 data="readOnly=true;', $htmlElementsReadOnly);

          $content .=<<<EOD

      <mvc:State name="show,showDraft" label="{i18n:Show}">
        <c:FormEdit id="editForm" newCode="true" controllerName="##controller_name##">
          <glz:Hidden id="__model" value="{$moduleVO->model}" />
          $htmlElementsReadOnly
          <cms:FormButtonsPanel id="formButtons">
              <glz:HtmlButton label="{i18n:Indietro}" type="button" routeUrl="link" cssClass="btn btn-flat js-glizycms-cancel" />
          </cms:FormButtonsPanel>
        </c:FormEdit>
      </mvc:State>
EOD;
    }

$content .=<<<EOD

  </glz:template>
EOD;

$content .=<<<EOD

</glz:include>
EOD;

        @file_put_contents($filename, $content);
    }

    protected function createAdminPopupFile($modulePath, $moduleVO, $htmlElements, $gridFields)
    {
        $filename = $modulePath.'views/AdminPopup.xml';

        $fields = array();

        foreach ($gridFields as $fieldName) {
            $fields[] = "$('#".$fieldName."').val()";
        }

        $text = implode(" + ' - ' + ", $fields);

        $content = <<<EOD
<?xml version="1.0" encoding="utf-8"?>
<mvc:Page id="Page"
  xmlns:glz="http://www.glizy.org/dtd/1.0/"
  xmlns:cms="org.glizycms.views.components.*"
  xmlns:mvc="org.glizy.mvc.components.*"
  xmlns:r="org.glizycms.roleManager.views.*"
  xmlns:c="metafad.modules.iccd.views.components.*"
  xmlns:cmp="metafad.gestioneDati.boards.views.components.*"
  defaultEditableRegion="content"
  templateType="php"
  templateFileName="Popup.php">

<glz:Import src="_common.xml"/>

<mvc:State name="index">
    <c:FormEdit id="editForm" newCode="true" controllerName="metafad.gestioneDati.boards.controllers.*">
        <glz:Hidden id="__id"/>
        <glz:Hidden id="__model" value="{$moduleVO->model}"/>

        $htmlElements
        <glz:Panel cssClass="formButtons">
            <glz:HtmlButton label="{i18n:GLZ_SAVE}" type="button" cssClass="btn btn-primary js-glizycms-save"
                            data="action=save" acl="*,edit"/>
            <glz:HtmlButton label="{i18n:GLZ_CANCEL}" type="button" routeUrl="link"
                            cssClass="btn js-glizycms-cancel button-margin" data="action=close"/>
        </glz:Panel>
    </c:FormEdit>
    <glz:JSscript><![CDATA[
    $( document ).ready( function(){
        $('.js-glizycms-save').data('trigger',function (e, data) {

            var msg = {
                type: 'save',
                id: $('#__id').val(),
                text: $text,
                values: data
            }

            Glizy.events.broadcast("glizy.FormEdit.modalPage.message", msg);
        });

        $('.js-glizycms-cancel').click(function (e) {
            parent.postMessage('{"type":"cancel"}', parent.location.origin);
        });
    });
    ]]></glz:JSscript>
    </mvc:State>
</mvc:Page>
EOD;

        //echo($filename); echo($content); die;
        @file_put_contents($filename, $content);
    }

    protected function createAdminPreview($modulePath, $moduleVO, $htmlElements, $gridFields)
    {
        $filename = $modulePath.'views/AdminPreview.xml';

        $fields = array();

        foreach ($gridFields as $fieldName) {
            $fields[] = "$('#".$fieldName."').val()";
        }

        $text = implode(" + ' - ' + ", $fields);

        $htmlElements = str_replace('<glz:Input', '<print:Input', $htmlElements);

        $content = <<<EOD
<?xml version="1.0" encoding="utf-8"?>
<glz:include
    xmlns:glz="http://www.glizy.org/dtd/1.0/"
    xmlns:print="metafad.print.views.components.*"
    src="metafad.views.TemplateModuleAdminPreview">

    <glz:template name="model" value="{$moduleVO->model}"/>
    <glz:template name="form_fields">
        $htmlElements
    </glz:template>
</glz:include>
EOD;

        @file_put_contents($filename, $content);
    }

    protected function createDataGridColums($gridFields)
    {
        $dataGridColumns = array();
        foreach($gridFields as $k => $gridField) {
            $renderCell = 'renderCell="metafad.common.views.renderer.ShortField"';
            $solrField = $gridField.'_s';
            $headerText = ($gridField != 'uniqueIccdId') ? $gridField : 'NCT';
            $dataGridColumns[] = "\t\t".'<glz:DataGridColumn columnName="'.$solrField.'" headerText="{i18n:'.$headerText.'}" '.$renderCell.'/>';
        }

        return implode(PHP_EOL, $dataGridColumns);
    }

    protected function createAdminExportFile($modulePath, $moduleVO, $gridFields)
    {
        $filename = $modulePath.'views/AdminExport.xml';

        $dataGridColumns = $this->createDataGridColums($gridFields);

        $content = <<<EOD
<?xml version="1.0" encoding="utf-8"?>
<glz:include
    xmlns:glz="http://www.glizy.org/dtd/1.0/"
    src="metafad.views.TemplateModuleAdminExport">

    <glz:template name="model" value="{$moduleVO->model}"/>
    <glz:template name="autocompleteController" value="metafad.common.controllers.ajax.AutoComplete"/>
    <glz:template name="grid_fields">
$dataGridColumns
    </glz:template>
</glz:include>
EOD;

        @file_put_contents($filename, $content);
    }

    protected function createControllerFindTerm($modulePath, $moduleVO, $findTermFields)
    {
        $filename = $modulePath.'controllers/ajax/FindTerm.php';

        $fields = array();

        foreach ($findTermFields as $fieldName) {
            if ($fieldName == 'BIBM' || $fieldName == 'BIBG') {
                $fields[] = 'mb_substr($ar->'.$fieldName.', 0, 50,"UTF-8")';
            } else {
                $fields[] = '$ar->'.$fieldName;
            }
        }

        $text = implode(".' - '.", $fields);

        $content = <<<EOD
<?php
class {$moduleVO->classPath}_controllers_ajax_FindTerm extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute(\$fieldName, \$model, \$term, \$id)
    {
        \$result = \$this->checkPermissionForBackend('visible');
        if (is_array(\$result)) {
            return \$result;
        }

        \$it = org_glizy_objectFactory::createModelIterator('{$moduleVO->model}');

        if (\$id) {
          \$it->where('document_id',\$id);
        }

        if (\$term != '') {
            \$it->where('{$findTermFields[0]}', '%'.\$term.'%', 'ILIKE');
        }

        \$result = array();

        foreach(\$it as \$ar) {
            \$result[] = array(
                'id' => \$ar->getId(),
                'text' => $text,
                'values' => \$ar->getValuesAsArray(false, false, false, false)
            );
        }

        \$this->directOutput = false;
        return \$result;
    }
}
EOD;

        @file_put_contents($filename, $content);
    }


    protected function createModuleFile($modulePath, $moduleVO)
    {
        $filename = $modulePath.'Module.php';

        $canDuplicated = $moduleVO->canDuplicated ? 'true' : 'false';

        $content = <<<EOD
<?php
class {$moduleVO->id}_Module
{
    static function registerModule()
    {
        glz_loadLocale('$moduleVO->classPath');

        \$moduleVO = org_glizy_Modules::getModuleVO();
        \$moduleVO->id = '$moduleVO->id';
        \$moduleVO->name = __T('$moduleVO->name');
        \$moduleVO->description = '$moduleVO->description';
        \$moduleVO->version = '$moduleVO->version';
        \$moduleVO->classPath = '$moduleVO->classPath';
        \$moduleVO->pageType = '$moduleVO->pageType';
        \$moduleVO->model = '$moduleVO->model';
        \$moduleVO->author = '$moduleVO->author';
        \$moduleVO->authorUrl = '$moduleVO->authorUrl';
        \$moduleVO->pluginUrl = '$moduleVO->pluginUrl';
        \$moduleVO->iccdModuleType = '$moduleVO->iccdModuleType';
        \$moduleVO->siteMapAdmin = '$moduleVO->siteMapAdmin';
        \$moduleVO->canDuplicated = $canDuplicated;
        \$moduleVO->isICCDModule = $moduleVO->isICCDModule;
        \$moduleVO->isAuthority = $moduleVO->isAuthority;

        org_glizy_Modules::addModule( \$moduleVO );
    }
}
EOD;
        @file_put_contents($filename, $content);
    }

    protected function createFrontEndFile($modulePath, $moduleVO, $elements)
    {
        $filename = $modulePath.'views/FrontEnd.xml';

        $htmlElements = $this->makeFrontEndHtmlElements($elements);

        $content = <<<EOD
<?xml version="1.0" encoding="utf-8"?>
<glz:Page id="Page"
    xmlns:glz="http://www.glizy.org/dtd/1.0/"
    xmlns:c="{$moduleVO->classPath}.views.components.*"
    xmlns:cms="org.glizycms.views.components.*"
    templateType="php"
    templateFileName="page.php"
    defaultEditableRegion="content"
    adm:editComponents="text">
    <glz:Import src="Common.xml" />
    <glz:DataProvider id="ModuleDP" recordClassName="{$moduleVO->model}" order="OGTD" />
    <glz:StateSwitch defaultState="list" rememberState="false">
        <glz:State name="list">
            <glz:LongText id="text" label="{i18n:MW_PARAGRAPH_TEXT}" forceP="true" adm:rows="20" adm:cols="75" adm:htmlEditor="true" />
            <glz:SearchFilters id="filters" cssClass="search">
                <glz:Input id="filterTitle" label="{i18n:Definizione}" bindTo="OGTD" value="{filters}" />
                <glz:Panel cssClass="formButtons">
                    <glz:HtmlButton label="{i18n:MW_SEARCH}" value="SEARCH" target="{filters}" cssClass="submitButton" />
                    <glz:HtmlButton label="{i18n:MW_NEW_SEARCH}" value="RESET" target="{filters}" cssClass="submitButton" />
                </glz:Panel>
            </glz:SearchFilters>

            <glz:RecordSetList id="list" dataProvider="{ModuleDP}" routeUrl="{$moduleVO->classPath}" title="{i18n:MW_SEARCH_RESULT}" filters="{filters}" paginate="{paginate}" skin="mabi_{$classPath}_list.html" />
            <glz:PaginateResult id="paginate" cssClass="pagination" />
        </glz:State>
        <glz:State name="show">
            <glz:Modifier target="pagetitle" attribute="visible" value="false" />
            <glz:RecordDetail id="entry" dataProvider="{ModuleDP}" idName="document_id" skin="mabi_{$classPath}_entry.html">

$htmlElements

            </glz:RecordDetail>
            <glz:Link id="backbtn" editableRegion="afterContent" cssClass="moreLeft" label="{i18n:MW_BACK_TO_SEARCH}" />
        </glz:State>
    </glz:StateSwitch>
</glz:Page>
EOD;
        @file_put_contents($filename, $content);
    }

    protected function makeFrontEndHtmlElements($elements)
    {
        $output = '';

        foreach ($elements as $element) {
            $output .= $this->makeFrontEndElement($element, 0);
        }

        return $output;
    }

    protected function makeFrontEndElement($element, $level)
    {
        $attributes = array('id' => $element['name']);

        if ($element['children']) {
            // se è un campo ripetibile
            if ($element['maxOccurs'] > 1 || $element['maxOccurs'] == 'unbounded') {
                $content = PHP_EOL;

                foreach ($element['children'] as $child) {
                    $content .= '  '.$this->makeFrontEndElement($child, $level+1);
                }

                $output = org_glizy_helpers_Html::renderTag('glz:Repeater', $attributes, true, $content).PHP_EOL;
            }
            else {
                foreach ($element['children'] as $child) {
                    $output .= $this->makeFrontEndElement($child, $level+1);
                }
            }
        } else {
            if ($element['maxOccurs'] > 1 || $element['maxOccurs'] == 'unbounded') {
                $attributesInput = array(
                    'id' => $element['name'].'-element',
                );

                $content = PHP_EOL.'  '.org_glizy_helpers_Html::renderTag('glz:Text', $attributesInput).PHP_EOL;
                $output = org_glizy_helpers_Html::renderTag('glz:Repeater', $attributes, true, $content).PHP_EOL;
            } else {
                $output = org_glizy_helpers_Html::renderTag('glz:Text', $attributes).PHP_EOL;
            }
        }

        return $output;
    }

    public function createSkins($modulePath, $moduleVO, $elements)
    {
        $this->createListSkin($modulePath, $moduleVO);
        $this->createEntrySkin($modulePath, $moduleVO, $elements);
    }

    public function createListSkin($modulePath, $moduleVO)
    {
        $filename = __Paths::get('STATIC_DIR').'metafad/templates/Default/skins/mabi_'.$moduleVO->classPath.'_list.html';

        $content = <<<EOD
<div class="searchResults" tal:condition="php: !is_null(Component.records)">
    <h3 tal:content="structure Component/title"/>
    <span tal:omit-tag="" tal:condition="php: Component.records.count() > 0">
        <div tal:repeat="item Component/records" tal:attributes="class item/__cssClass__">
            <div class="right">
                <h4><a href="" tal:attributes="href item/__url__; title item/OGTD" tal:content="structure item/OGTD"></a></h4>
            </div>
            <div class="clear"></div>
        </div>
        <div class="clear"></div>
    </span>
    <span tal:omit-tag="" tal:condition="php: Component.records.count() == 0">
        <div class="around" >
            <p tal:content="php:__T('Definizione')"></p>
        </div>
    </span>
    <div class="clear"></div>
</div>
EOD;
        @file_put_contents($filename, $content);
    }

    protected function makeFrontEndSkinElements($elements)
    {
        $output = '';

        foreach ($elements as $element) {
            $output .= $this->makeFrontEndSkinElement($element);
        }

        return $output;
    }

    protected function makeFrontEndSkinElement($element)
    {
        $elementName = $element['name'];

        $attributes = array('id' => $elementName);

        if ($element['children']) {
            // se è un campo ripetibile
            if ($element['maxOccurs'] > 1 || $element['maxOccurs'] == 'unbounded') {
                $content = PHP_EOL;

                foreach ($element['children'] as $child) {
                    $content .= '  '.$this->makeFrontEndSkinElement($child);
                }

                //$output = org_glizy_helpers_Html::renderTag('glz:Repeater', $attributes, true, $content).PHP_EOL;
            }
            else {
                foreach ($element['children'] as $child) {
                    $output .= $this->makeFrontEndSkinElement($child);
                }
            }
        } else {
            if ($element['maxOccurs'] > 1 || $element['maxOccurs'] == 'unbounded') {
                $attributesInput = array(
                    'id' => $elementName.'-element',
                );

                $content = PHP_EOL.'  '.org_glizy_helpers_Html::renderTag('glz:Text', $attributesInput).PHP_EOL;
                //$output = org_glizy_helpers_Html::renderTag('glz:Repeater', $attributes, true, $content).PHP_EOL;
            } else {
                $output = <<<EOD
<span tal:omit-tag="" tal:condition="php: Component.$elementName!=''">
    <dt tal:content="structure php: __T('$elementName')"></dt>
    <dd tal:content="structure Component/$elementName"></dd>
</span>
EOD;
                $output .= PHP_EOL;
            }
        }

        return $output;
    }

    public function createEntrySkin($modulePath, $moduleVO, $elements)
    {
        $filename = __Paths::get('STATIC_DIR').'metafad/templates/Default/skins/mabi_'.$moduleVO->classPath.'_entry.html';

        $skinElements = $this->makeFrontEndSkinElements($elements);

        $content = <<<EOD
$skinElements
EOD;

        @file_put_contents($filename, $content);
    }

    protected function addModuleToStartup($modulePath, $newModuleVO)
    {
        $sitemapCustom = __Paths::get( 'APPLICATION_TO_ADMIN' ).'startup/modules_custom.php';
		if ( file_exists( $sitemapCustom ) )
		{
			$output = file_get_contents( $sitemapCustom );
		}
		else
		{
			$output = <<<EOD
<?php
\$application = org_glizy_ObjectValues::get('org.glizy', 'application' );
if (\$application) {
    __Paths::addClassSearchPath( __Paths::get( 'APPLICATION_CLASSES' ).'userModules/' );
//modules_custom.php
}
EOD;
		}
		// cancella entry già presenti
		$output = preg_replace( "/\/\/\sstart\s".$newModuleVO->id."\/\/([^\/])*\/\/\send\s".$newModuleVO->id."\/\//i", "", $output );

		// aggiunge la nuova entry
		$output = str_replace( '//modules_custom.php', '// start '.$newModuleVO->id.'//'.GLZ_COMPILER_NEWLINE2.$newModuleVO->id.'_Module::registerModule();'.GLZ_COMPILER_NEWLINE2.'// end '.$newModuleVO->id.'//'.GLZ_COMPILER_NEWLINE2.'//modules_custom.php', $output );

        // elimina le righe vuote
        $output = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $output);

		$r = file_put_contents( $sitemapCustom, $output );
	}

    protected function getChildrenForLocale($elements,&$labelsMapping)
    {
        foreach ($elements as $e) {
            $labelsMapping .= '    "'.$e['name'].'" => "'.$e['name'].' - '.$e['label'].'",'.PHP_EOL;
            if ($e['children']) {
                $this->getChildrenForLocale($e['children'],$labelsMapping);
            }
        }
    }
}
