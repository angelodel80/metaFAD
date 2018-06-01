<?php
require_once("core/core.inc.php");

$application = org_glizy_ObjectFactory::createObject('org.glizy.oaipmh2.core.Application', 'application', '');
$application->sitemapFactory(function($forceReload=false) use ($application) {
    $application->setAdapter('metafad.oaipmh.adapters.MagAdapter');

    $application->addMetadataFormat(org_glizy_oaipmh2_models_VO_MetadataVO::create(
        'mag',
        'http://www.iccu.sbn.it/directories/metadigit201/metadigit.xsd',
        'http://www.iccu.sbn.it/metaAG1.pdf',
        'mag',
        'http://www.iccu.sbn.it/directories/metadigit201/metadigit.xsd'
    ));
    
    $application->addMetadataFormat(org_glizy_oaipmh2_models_VO_MetadataVO::create(
        'mets',
        'http://www.loc.gov/standards/mets/profile_docs/mets.profile.v1-2.xsd',
        'http://www.loc.gov/METS_Profile/',
        'mag',
        'http://www.loc.gov/standards/mets/profile_docs/mets.profile.v1-2.xsd'
    ));

    $application->addSet('mag', 'metafad.oaipmh.sets.Mag');

    $application->createSiteMap($forceReload);
    return $application->siteMap;
});
$application->run();


