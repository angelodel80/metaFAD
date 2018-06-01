<?php

class metafad_workflow_activities_Module
{
    static function registerModule()
    {
        $moduleVO = org_glizy_Modules::getModuleVO();
        $moduleVO->id = 'metafad.workflow.activities';
        $moduleVO->name = 'Attività';
        $moduleVO->description = '';
        $moduleVO->version = '1.0.0';
        $moduleVO->classPath = 'metafad.workflow.activities';
        $moduleVO->pageType = '';
        $moduleVO->author = 'META srl';
        $moduleVO->authorUrl = 'http://www.gruppometa.it';
        $moduleVO->pluginUrl = '';
        $moduleVO->siteMapAdmin = '<glz:Page parentId="processi" id="processi-definizione-attivita" pageType="metafad.workflow.activities.views.Admin" value="{i18n:Definizione attività}" icon="fa fa-angle-double-right" adm:acl="*"/>';
        $moduleVO->canDuplicated = false;

        org_glizy_Modules::addModule($moduleVO);
    }
}