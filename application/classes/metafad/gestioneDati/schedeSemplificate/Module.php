<?php
class metafad_gestioneDati_schedeSemplificate_Module
{
    static function registerModule()
    {
        $moduleVO = org_glizy_Modules::getModuleVO();
        $moduleVO->id = 'metafad.gestioneDati.schedeSemplificate';
        $moduleVO->name = 'Schede semplificate';
        $moduleVO->description = '';
        $moduleVO->version = '1.0.0';
        $moduleVO->classPath = 'metafad.gestioneDati.schedeSemplificate';
        $moduleVO->model = 'SchedaF400.models.Model';
        $moduleVO->pageType = '';
        $moduleVO->author = 'META srl';
        $moduleVO->authorUrl = 'http://www.gruppometa.it';
        $moduleVO->pluginUrl = '';
        $moduleVO->siteMapAdmin = '<glz:Page parentId="impostazioni-sistema" id="metafad.gestioneDati.schedeSemplificate" pageType="metafad.gestioneDati.schedeSemplificate.views.Admin" value="{i18n:Schede semplificate}" icon="fa fa-angle-double-right no-rot" adm:acl="*" />';
        $moduleVO->canDuplicated = false;

        org_glizy_Modules::addModule( $moduleVO );

    }
}
