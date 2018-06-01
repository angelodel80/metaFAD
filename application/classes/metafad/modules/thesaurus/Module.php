<?php
class metafad_modules_thesaurus_Module
{
    static function registerModule()
    {
        $classPath = 'iccd';
        glz_loadLocale($classPath);

        $moduleVO = org_glizy_Modules::getModuleVO();
        $moduleVO->id = 'metafad.modules.thesaurus';
        $moduleVO->name = 'Dizionari';
        $moduleVO->description = 'Modulo creazione schede iccd';
        $moduleVO->version = '1.0.0';
        $moduleVO->classPath = $classPath;
        $moduleVO->author = 'META srl';
        $moduleVO->authorUrl = 'http://www.gruppometa.it';
        $moduleVO->pluginUrl = '';
        $moduleVO->siteMapAdmin = '<glz:Page parentId="impostazioni-sistema" pageType="metafad.modules.thesaurus.views.Admin" id="metafad.modules.thesaurus" value="{i18n:'.$moduleVO->name.'}" icon="fa fa-angle-double-right no-rot" adm:acl="*" />';
        $moduleVO->canDuplicated = false;

		if(__Config::get('metafad.be.hasDictionaries'))
		{
        	org_glizy_Modules::addModule( $moduleVO );
		}
    }
}
