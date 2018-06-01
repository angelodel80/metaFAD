<?php
class SchedaF400_Module
{
    static function registerModule()
    {
        glz_loadLocale('SchedaF400');

        $moduleVO = org_glizy_Modules::getModuleVO();
        $moduleVO->id = 'SchedaF400';
        $moduleVO->name = __T('SchedaF400.views.FrontEnd');
        $moduleVO->description = '';
        $moduleVO->version = '1.0.0';
        $moduleVO->classPath = 'SchedaF400';
        $moduleVO->pageType = 'SchedaF400.views.FrontEnd';
        $moduleVO->model = 'SchedaF400.models.Model';
        $moduleVO->author = 'META srl';
        $moduleVO->authorUrl = 'http://www.gruppometa.it';
        $moduleVO->pluginUrl = 'http://www.metafadcms.it';
        $moduleVO->iccdModuleType = 'F';
        $moduleVO->siteMapAdmin = '
<glz:Page id="SchedaF400" value="{i18n:SchedaF400.views.FrontEnd}" pageType="SchedaF400.views.Admin" parentId="gestione-dati/patrimonio" adm:acl="*"/>
<glz:Page id="SchedaF400_preview" pageType="SchedaF400.views.AdminPreview" parentId="" adm:acl="*" />
<glz:Page id="SchedaF400_export" value="{i18n:SchedaF400.views.FrontEnd}" pageType="SchedaF400.views.AdminExport" parentId="export/patrimonio" icon="fa fa-angle-double-right" adm:acl="*" />';
        $moduleVO->canDuplicated = false;
        $moduleVO->isICCDModule = true;
        $moduleVO->isAuthority = false;

        org_glizy_Modules::addModule( $moduleVO );
    }
}