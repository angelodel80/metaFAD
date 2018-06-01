<?php

class metafad_gestioneDati_schedeSemplificate_views_helpers_SimpleAdminHelper extends GlizyObject
{
	public function getFields($id)
	{
		$ar = org_glizy_objectFactory::createModel('metafad.gestioneDati.schedeSemplificate.models.Model');
		$ar->load($id);

		$modules = org_glizy_Modules::getModules();
		$m = $modules[$ar->form->id];

		$moduleService = __ObjectFactory::createObject('metafad.modules.iccd.services.ModuleService');
		if ($m->adminFile) {
			$adminFile = $m->adminFile;
			$elements = $moduleService->getElements($ar->form->id, $adminFile, true);
		} else {
			$elements = $moduleService->getElements($ar->form->id);
		}
		$elements = json_decode(json_encode($elements), true);

		$fieldsArray = array();
		$this->exploreElements($elements,$fieldsArray);

		$fieldJson = json_decode($ar->fieldJson)->fields;
		$fieldsArrayToShow = array();
		if($fieldJson)
		{
			foreach ($fieldJson as $f) {
				$fieldsArrayToShow[$f->field][$f->type] = true;
			}
		}

		return array('all'=>$fieldsArray,'toShow'=>$fieldsArrayToShow);
	}

	public function exploreElements($elements,&$fieldsArray)
	{
		foreach ($elements as $el) {
			$fieldsArray[] = $el['name'];
			if($el['children'])
			{
				$this->exploreElements($el['children'],$fieldsArray);
			}
		}
	}

	public function deleteFiles($id)
	{
		$ar = org_glizy_objectFactory::createModel('metafad.gestioneDati.schedeSemplificate.models.Model');
		$ar->load($id);

		$classPath = $ar->form->id;
		$name = strtolower(str_replace(" ","_",$ar->name));
		$name = str_replace(".","",$name);
		$modulePath = __Paths::get( 'APPLICATION_TO_ADMIN' ).'classes/userModules/'.$classPath.'/';

		$modelName = $ar->form->id.'.models.Model_'.$name;

		//Cancellazione files
		if(file_exists($modulePath.'views/Admin_'.$name.'.xml'))
		{
			unlink($modulePath.'views/Admin_'.$name.'.xml');
		}
		if(file_exists($modulePath.'models/Model_'.$name.'.xml'))
		{
			unlink($modulePath.'models/Model_'.$name.'.xml');
		}
		//Il modulo in realtà non viene cancellato ma solo modificato
		$moduleFile = file_get_contents($modulePath . 'Module.php');
		if(__Config::get('metafad.modules.iccd.hasList'))
		{
			$moduleFile = str_replace('<glz:Page pageType="'.$classPath.'.views.Admin_'.$name.'" adm:acl="*" id="'.$classPath.'_'.$name.'" value="{i18n:'.$ar->name.'}" />','',$moduleFile);
		}
		else
		{
			$moduleFile = str_replace('<glz:Page pageType="' . $classPath . '.views.Admin_' . $name . '" adm:acl="*" id="' . $classPath . '_' . $name . '" parentId="gestione-dati/patrimonio" value="{i18n:' . $ar->name . '}" />', '', $moduleFile);
		}
		@file_put_contents($modulePath.'Module.php', $moduleFile);

		org_glizy_cache_CacheFile::cleanPHP();
	}
}
