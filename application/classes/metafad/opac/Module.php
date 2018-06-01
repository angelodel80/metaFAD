<?php
class metafad_opac_Module
{
    static function registerModule()
    {
        glz_loadLocale('metafad.opac');
        $moduleVO = org_glizy_Modules::getModuleVO();
        $moduleVO->id = 'metafad.opac.configRicerche';
        $moduleVO->name = 'Opac';
        $moduleVO->description = '';
        $moduleVO->version = '1.0.0';
        $moduleVO->classPath = 'metafad.opac';
        $moduleVO->model = 'metafad.opac.models.Model';
        $moduleVO->pageType = '';
        $moduleVO->author = 'META srl';
        $moduleVO->authorUrl = 'http://www.gruppometa.it';
        $moduleVO->pluginUrl = '';
        $moduleVO->siteMapAdmin = '<glz:Page pageType="metafad.opac.views.Admin" icon="fa fa-angle-double-right no-rot" parentId="opac" id="metafad.opac.configRicerche" value="{i18n:Configurazione ricerche}" adm:acl="*"/>';
        $moduleVO->canDuplicated = false;

        org_glizy_Modules::addModule( $moduleVO );

    }
}
