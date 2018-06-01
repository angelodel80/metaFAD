<?php
class BIB300_Module
{
    static function registerModule()
    {
        glz_loadLocale('BIB300');

        $moduleVO = org_glizy_Modules::getModuleVO();
        $moduleVO->id = 'BIB300';
        $moduleVO->name = __T('BIB300.views.FrontEnd');
        $moduleVO->description = '';
        $moduleVO->version = '1.0.0';
        $moduleVO->classPath = 'BIB300';
        $moduleVO->pageType = 'BIB300.views.FrontEnd';
        $moduleVO->model = 'BIB300.models.Model';
        $moduleVO->author = 'META srl';
        $moduleVO->authorUrl = 'http://www.gruppometa.it';
        $moduleVO->pluginUrl = 'http://www.metafadcms.it';
        $moduleVO->iccdModuleType = 'BIB';
        $moduleVO->siteMapAdmin = '
<glz:Page id="BIB300" value="{i18n:BIB300.views.FrontEnd}" pageType="BIB300.views.Admin" parentId="gestione-dati/authority/iccd" adm:acl="*"/>
<glz:Page id="BIB300_preview" pageType="BIB300.views.AdminPreview" parentId="" adm:acl="*" /><glz:Page pageType="BIB300.views.AdminPopup" id="BIB300_popup" visible="true" parentId="" />';
        $moduleVO->canDuplicated = false;
        $moduleVO->isICCDModule = true;
        $moduleVO->isAuthority = true;

        org_glizy_Modules::addModule( $moduleVO );
    }
}