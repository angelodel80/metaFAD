<?php
class metafad_gestioneDati_schedeSemplificate_controllers_ajax_Save extends org_glizycms_contents_controllers_moduleEdit_ajax_Save
{
    public function execute($data, $draft=false)
    {
        $this->directOutput = true;
        $modules = org_glizy_Modules::getModules();
        $modulesArray = array();
        foreach ($modules as $key => $value) {
          $modulesArray[$key] = $value->classPath;
        }
        $decodeData = json_decode($data);
        $decodeData->name = rtrim($decodeData->name,' ');

        if(array_key_exists($decodeData->name,$modulesArray) || substr($decodeData->name, -1) == ' ')
        {
          return array('errors' => array('Error'=>'Errore, il nome scelto non è utilizzabile.
                                  Potrebbe essere già presente oppure contenere caratteri non permessi (Es: spazi alla fine)'));
        }
        $isNew = ($decodeData->__id == 0) ? true : false;
        $classPath = $decodeData->form->id;
        $thisModule = $modules[$classPath];
        //Gestione cambio nome

        //TODO Cambiando il nome è anche necessario cambiare tutti i record
        //precedentemente creati sotto un certo tipo di scheda semplificata, dato
        //che il nome del model dipende da questo
        $name = strtolower(str_replace(" ","_",$decodeData->name));
        $name = str_replace(".","",$name);
        $oldName = strtolower(str_replace(" ","_",$decodeData->oldName));
        $oldName = str_replace(".","",$oldName);
        $oldDataLabel = $decodeData->oldName;
        $decodeData->oldName = $decodeData->name;
        $result = parent::execute(json_encode($decodeData), $draft);
        $result['set']['oldName'] = $decodeData->name;
        $decodeData->__id = $result['set']['__id'];

        //Module file
        $modulePath = __Paths::get( 'APPLICATION_TO_ADMIN' ).'classes/userModules/'.$classPath.'/';
        $moduleFile = file_get_contents($modulePath . 'Module.php');
        if($isNew)
        {
          if(__Config::get('metafad.modules.iccd.hasList'))
          {
            $moduleFile = str_replace('</glz:Page>', '<glz:Page pageType="' . $classPath . '.views.Admin_' . $name . '" adm:acl="*" id="' . $classPath . '_' . $name . '" value="{i18n:' . $decodeData->name . '}" /></glz:Page>', $moduleFile);
          }
          else
          {
            $moduleFile = str_replace("/>';", '/><glz:Page pageType="' . $classPath . '.views.Admin_' . $name . '" adm:acl="*" id="' . $classPath . '_' . $name . '" parentId="gestione-dati/patrimonio" value="{i18n:' . $decodeData->name . '}" />\';', $moduleFile);
          }
        }
        else
        {
          $moduleFile = str_replace('<glz:Page pageType="'.$classPath.'.views.Admin_'.$oldName.'" adm:acl="*" id="'.$classPath.'_'.$oldName.'" value="{i18n:'.$oldDataLabel.'}" />',
                                    '<glz:Page pageType="'.$classPath.'.views.Admin_'.$name.'" adm:acl="*" id="'.$classPath.'_'.$name.'" value="{i18n:'.$decodeData->name.'}" />',
                                    $moduleFile);
        }
        @file_put_contents($modulePath.'Module.php', $moduleFile);
        //Admin file
        $adminFileName = ($thisModule->adminFile) ?: 'Admin.xml';
        $modelPath = ($thisModule->model) ?: $classPath . '.models.Model';
        $adminFile = file_get_contents($modulePath.'views/'. $adminFileName);
        if(!$isNew)
        {
          //Elimino il vecchio file se sto modificando una vecchia versione
          if(file_exists($modulePath.'views/Admin_'.$oldName.'.xml'))
          {
            unlink($modulePath.'views/Admin_'.$oldName.'.xml');
          }
        }

        $adminFile = str_replace($modelPath,$classPath.'.models.Model_'.$name,$adminFile);
        $adminFile = str_replace(array('TemplateModuleAdmin','TemplateModuleAdminSimple'),array('TemplateModuleAdminSimplified', 'TemplateModuleAdminSimplified'),$adminFile);
        $adminFile = str_replace('SimplifiedSimple','Simplified',$adminFile);
        $adminFile = str_replace('<glz:template name="form_fields">','<glz:template name="form_fields"><glz:Hidden id="simpleForm" value="'.$decodeData->__id.'"/>',$adminFile);
        @file_put_contents($modulePath.'views/Admin_'.$name.'.xml', $adminFile);
        //Model file
        $modelFileName = ($thisModule->modelFile) ? : 'Model.xml';
        $modelFile = file_get_contents($modulePath.'models/'.$modelFileName);
        if(!$isNew)
        {
          //Elimino il vecchio file se sto modificando una vecchia versione
          if(file_exists($modulePath.'models/Model_'.$oldName.'.xml'))
          {
            unlink($modulePath.'models/Model_'.$oldName.'.xml');
          }
        }
        $modelFile = str_replace('model:tableName="'.$classPath.'"','model:tableName="'.$classPath.'_'.$name.'"',$modelFile);
        $modelFile = str_replace($classPath.'.models.Model',$classPath.'.models.Model_'.$name,$modelFile);
        $modelFile = str_replace('<model:Define>', '<model:Define><model:Field name="simpleForm" type="string" />',$modelFile);
        @file_put_contents($modulePath.'models/Model_'.$name.'.xml', $modelFile);

        org_glizy_cache_CacheFile::cleanPHP();
        return $result;
    }
}
