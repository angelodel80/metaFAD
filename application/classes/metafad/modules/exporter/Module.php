<?php

class metafad_modules_exporter_Module
{
    static function registerModule()
    {
        $moduleVO = org_glizy_Modules::getModuleVO();
        $moduleVO->id = 'export';
        $moduleVO->name = 'Export';
        $moduleVO->description = '';
        $moduleVO->version = '1.0.0';
        $moduleVO->pageType = '';
        $moduleVO->author = 'META srl';
        $moduleVO->authorUrl = 'http://www.gruppometa.it';
        $moduleVO->pluginUrl = '';
        $moduleVO->siteMapAdmin = '<glz:Page id="export" pageType="" value="{i18n:Esporta}" icon="fa fa-download" adm:acl="*">
            <glz:Page id="export/patrimonio" pageType="" value="{i18n:Patrimonio}" icon="fa fa-angle-double-right" adm:acl="*"/>
            <glz:Page id="archive_export" pageType="" value="{i18n:Archivi}" icon="fa fa-angle-double-right" adm:acl="*">
                <glz:Page id="archive_export_mets" pageType="archivi.views.AdminExport" value="{i18n:METS-SAN}" icon="fa fa-angle-right" adm:acl="*"/>
            </glz:Page>
        </glz:Page>';
        $moduleVO->canDuplicated = false;

		if(__Config::get('metafad.be.hasExport'))
		{
			org_glizy_Modules::addModule($moduleVO);
		}
    }
}
