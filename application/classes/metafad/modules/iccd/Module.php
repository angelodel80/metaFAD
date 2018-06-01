<?php
class metafad_modules_iccd_Module
{
    static function registerModule()
    {
        $classPath = 'iccd';
        glz_loadLocale('metafad.modules.iccd');

        $moduleVO = org_glizy_Modules::getModuleVO();
        $moduleVO->id = 'metafad.modules.iccd';
        $moduleVO->name = 'Generatore schede ICCD';
        $moduleVO->description = 'Modulo creazione schede iccd';
        $moduleVO->version = '1.0.0';
        $moduleVO->classPath = $classPath;
        $moduleVO->author = 'META srl';
        $moduleVO->authorUrl = 'http://www.gruppometa.it';
        $moduleVO->pluginUrl = '';
        $moduleVO->siteMapAdmin = '<glz:Page parentId="impostazioni-sistema" pageType="metafad.modules.iccd.views.Admin" id="metafad.modules.iccd" value="{i18n:'.$moduleVO->name.'}" icon="fa fa-angle-double-right no-rot" adm:acl="*" />';
        $moduleVO->canDuplicated = false;

		if(__Config::get('metafad.be.hasIccdGenerator'))
		{
        	org_glizy_Modules::addModule( $moduleVO );
		}
    }
}
