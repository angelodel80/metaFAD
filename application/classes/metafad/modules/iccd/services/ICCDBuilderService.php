<?php
class metafad_modules_iccd_services_ICCDBuilderService extends GlizyObject
{
    public function parseFiles($xsdFile, $xmlFileRequired, $moduleName, $siteMapParentNode, $sbnweb, $isAuthority, $linkFNTToArchive,$archiveModels, $ecommerce)
    {
        $moduleBuilder = org_glizy_ObjectFactory::createObject('metafad.modules.iccd.services.ModuleBuilder', $moduleName);
        $classPath = $moduleBuilder->getClassPath();
        $moduleId = $moduleBuilder->getModuleId();

        $xsdParser = org_glizy_ObjectFactory::createObject('metafad.modules.iccd.services.XSDParser', $classPath, $moduleId, $moduleName);
        $elements = $xsdParser->parseFile($xsdFile, $xmlFileRequired);
        $htmlElements = $xsdParser->makeHtmlElements($isAuthority,$linkFNTToArchive,$archiveModels,$ecommerce,$sbnweb);
        $htmlNoGroups = $xsdParser->makeHtmlNoGroups();
        $fieldsAttributes = $xsdParser->getFieldsAttributes();
        $iccdModuleType = $xsdParser->getIccdModuleType();

        $iccdParams = array(
            "elements"=>$elements,
            "htmlElements"=>$htmlElements,
            "htmlNoGroups"=>$htmlNoGroups,
            "fieldsAttributes"=>$fieldsAttributes,
            "siteMapParentNode"=>$siteMapParentNode,
            "sbnweb"=>$sbnweb,
            "isAuthority"=>$isAuthority,
            "moduleName"=>$moduleName,
            "iccdModuleType" => $iccdModuleType,
            "ecommerce" => $ecommerce
        );

        return $iccdParams;
    }

    public function createModule($options,$reindex=true,$fe=false,$be=true)
    {
        $moduleBuilder = org_glizy_ObjectFactory::createObject('metafad.modules.iccd.services.ModuleBuilder', $options['moduleName']);
        $moduleBuilder->createModule($options,$reindex,$fe,$be);

        org_glizy_cache_CacheFile::cleanPHP();
    }
}
