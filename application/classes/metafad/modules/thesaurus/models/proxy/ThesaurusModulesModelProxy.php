<?php

/*// TODO: sostituire con org_glizycms_helpers_Modules
class metafad_modules_importer_services_ModuleService extends GlizyObject
{
    public function getFields($pageId)
    {
        $editForm = $this->getEditForm($pageId);

        $fields = array();

        for ($i = 0; $i < count($editForm->childComponents); $i++)
        {
            $c = $editForm->childComponents[$i];
            $id = $c->getAttribute('id');

            if ( ( is_subclass_of($c, 'org_glizy_components_HtmlFormElement') || is_a($c, 'org_glizy_components_Fieldset') ) && substr($id, 0, 2) != '__' ) {
                $fields[$id] = $c;
            }
        }

        return $fields;
    }

    public function getModelPath($pageId)
    {
        $editForm = $this->getEditForm($pageId);

        for ($i = 0; $i < count($editForm->childComponents); $i++)
        {
            $c = $editForm->childComponents[$i];
            $id = $c->getAttribute('id');

            if ($id == '__model') {
                return $c->getAttribute('value');
            }
        }

        return null;
    }

    protected function getEditForm($pageId)
    {
        $application = org_glizy_ObjectValues::get('org.glizy', 'application');

        $originalRootComponent = $application->getRootComponent();
        $siteMap = $application->getSiteMap();
        $siteMapNode = $siteMap->getNodeById($pageId);
        $pageType = $siteMapNode->getAttribute('pageType');

        $path = org_glizy_Paths::get('APPLICATION_PAGETYPE');
        $templatePath = org_glizycms_Glizycms::getSiteTemplatePath();
        $options = array(
            'skipImport' => true,
            'pathTemplate' => $templatePath,
            'mode' => 'edit'
        );

        $pageTypeObj = &org_glizy_ObjectFactory::createPage($application, $pageType, $path, $options);

        $rootComponent = $application->getRootComponent();
        $rootComponent->init();
        $application->_rootComponent = &$originalRootComponent;

        __Request::set('action', 'edit');
        $rootComponent->process();

        return $rootComponent->getComponentById('editForm');
    }
}

class __ModulesService extends metafad_modules_importer_services_ModuleService{
    //Shortcut
}*/

class metafad_modules_thesaurus_models_proxy_ThesaurusModulesModelProxy extends GlizyObject
{
    //Questo controlla la leggibilità umana, se il campo non è di sistema e se fa capo al thesaurusproxy
    private static function toBeKept($value){
        return
            ($value->id != $value->label || __T($value->id) != $value->id) &&
            stripos($value->data, 'proxy=metafad.modules.thesaurus.models.proxy.ThesaurusProxy');
    }

    public function findTerm($fieldName, $model, $query, $term, $proxyParams)
    {
        $pageId = $proxyParams->modelName;
        $helper = __ObjectFactory::createObject('org.glizycms.helpers.Modules');
        if (!$pageId)
        {
           return '';
        }

        if (strpos($pageId, '/') !== false){
            //Formato <module>/<pageTypePath>
            $array = explode('/', $pageId);
            $pageId = $array[1];
            $fields = $helper->getFields($pageId, true);
        } else {
            //Formato <module>: quindi dentro ci sarà scritto un unico modello.
            $fields = $helper->getFields($pageId, true);
        }

        //RESTITUISCO TUTTI I CAMPI DEL MODEL CHE NON SONO DI SISTEMA E CHE SONO UMANAMENTE LEGGIBILI
        foreach ($fields as $key => $value) {
          if($term != '')
          {
            if(self::toBeKept($value) && stripos($value->id . ' - ' . __T($value->id) . $value->label, $term) !== false )
            {
              $result[] = array(
                  'id' => $value->id,
                  'text' => $value->id . ' - ' . ((__T($value->id) != $value->id) ? __T($value->id) : $value->label),
              );
            }
          }
          else if(self::toBeKept($value))
          {
            $result[] = array(
                'id' => $value->id,
                'text' => $value->id . ' - ' . ((__T($value->id) != $value->id) ? __T($value->id) : $value->label),
            );
          }
          else if(1 == 0){ //Debug purposes: this guard is true => debugging is running
              $result[] = array(
                'id' => $value->id,
                'text' => "(Ignoro in produzione) {$value->id}"
              );
          }
        }

        if($result == null)
        {
          return '';
        }
        else {
            return $result;
        }
    }
}


