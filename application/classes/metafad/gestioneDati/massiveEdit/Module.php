<?php

class metafad_gestioneDati_massiveEdit_Module
{
    static function registerModule()
    {
        $moduleVO = org_glizy_Modules::getModuleVO();
        $moduleVO->id = 'metafad.gestioneDati.massiveEdit';
        $moduleVO->name = 'massiveEdit';
        $moduleVO->description = '';
        $moduleVO->version = '1.0.0';
        $moduleVO->classPath = 'metafad.gestioneDati.massiveEdit';
        $moduleVO->pageType = '';
        $moduleVO->author = 'META srl';
        $moduleVO->authorUrl = 'http://www.gruppometa.it';
        $moduleVO->pluginUrl = '';
        $moduleVO->siteMapAdmin = '<glz:Page parentId="gestione-dati" id="massiveEdit" pageType="metafad.gestioneDati.massiveEdit.views.Admin" value="{i18n:Modifica Massiva}" icon="fa fa-angle-double-right no-rot" adm:acl="*" />';
        $moduleVO->canDuplicated = false;

        org_glizy_Modules::addModule($moduleVO);
    }
}
