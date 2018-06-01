<?php
class metafad_ecommerce_orders_Module
{
    static function registerModule()
    {
        $moduleVO = org_glizy_Modules::getModuleVO();
        $moduleVO->id = 'metafad.ecommerce.orders';
        $moduleVO->name = 'Orders';
        $moduleVO->description = '';
        $moduleVO->version = '1.0.0';
        $moduleVO->classPath = 'metafad.ecommerce.orders';
        $moduleVO->model = 'metafad.ecommerce.orders.models.Model';
        $moduleVO->pageType = '';
        $moduleVO->author = 'META srl';
        $moduleVO->authorUrl = 'http://www.gruppometa.it';
        $moduleVO->pluginUrl = '';
        $moduleVO->siteMapAdmin = '<glz:Page pageType="metafad.ecommerce.orders.views.Admin" icon="fa fa-angle-double-right no-rot" parentId="ecommerce" id="ecommerce-orders" value="{i18n:Ordini}" adm:acl="*"/>';
        $moduleVO->canDuplicated = false;

        org_glizy_Modules::addModule( $moduleVO );

    }
}
