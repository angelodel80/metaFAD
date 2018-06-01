<?php
class metafad_modules_importer_Module
{
    static function registerModule()
    {
        $moduleVO = org_glizy_Modules::getModuleVO();
        $moduleVO->id = 'metafad.modules.importer';
        $moduleVO->name = 'Importatore';
        $moduleVO->description = 'Modulo importazione schede iccd';
        $moduleVO->version = '1.0.0';
        $moduleVO->classPath = $classPath;
        $moduleVO->author = 'META srl';
        $moduleVO->authorUrl = 'http://www.gruppometa.it';
        $moduleVO->pluginUrl = '';
        $moduleVO->siteMapAdmin = '<glz:Page parentId="impostazioni-sistema" pageType="metafad.modules.importer.views.Admin" id="metafad.modules.importer" value="{i18n:'.$moduleVO->name.'}" icon="fa fa-angle-double-right no-rot" adm:acl="*" />';
        $moduleVO->canDuplicated = false;

		if(__Config::get('metafad.be.hasImport'))
		{
        	org_glizy_Modules::addModule( $moduleVO );
		}
    }
}
