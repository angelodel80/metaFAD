<?php
setlocale(LC_TIME, "it_IT", "it", "it_IT.utf8");
org_glizycms_Glizycms::init();
metafad_Metafad::init();

metafad_usersAndPermissions_users_Module::registerModule();
metafad_usersAndPermissions_institutes_Module::registerModule();
metafad_usersAndPermissions_roles_Module::registerModule();

org_glizy_log_LogFactory::create('DB', array(), 255, 'audiction' );
org_glizy_log_LogFactory::create('DB', array(), 255, 'thesaurus' );

org_glizy_ObjectFactory::remapPageType('org.glizycms.mediaArchive.views.MediaArchive', 'metacms.dam.views.MediaArchive');
org_glizy_ObjectFactory::remapPageType('org.glizycms.mediaArchive.views.MediaPicker', 'metacms.dam.views.MediaPicker');
org_glizy_ObjectFactory::remapClass('org.glizy.components.Fieldset', 'metafad.common.views.components.Fieldset');

__ObjectFactory::createObject('metafad.usersAndPermissions.IstituteCheckListener');
__ObjectFactory::createObject('metafad.solr.Listener');

metafad_sbn_modules_authoritySBN_Module::registerModule();
metafad_sbn_modules_sbnunimarc_Module::registerModule();
metafad_teca_DAM_Module::registerModule();
metafad_teca_STRUMAG_Module::registerModule();
metafad_modules_exporter_Module::registerModule();
metafad_teca_MAG_Module::registerModule();
metafad_teca_mets_Module::registerModule();
metafad_opac_Module::registerModule();
metafad_gestioneDati_massiveEdit_Module::registerModule();
metafad_workflow_activities_Module::registerModule();
metafad_workflow_processes_Module::registerModule();

metafad_modules_thesaurus_Module::registerModule();
metafad_modules_importer_Module::registerModule();
metafad_uploader_Module::registerModule();
metafad_modules_importerReport_Module::registerModule();
metafad_gestioneDati_schedeSemplificate_Module::registerModule();

metafad_ecommerce_licenses_Module::registerModule();
metafad_ecommerce_orders_Module::registerModule();
metafad_ecommerce_requests_Module::registerModule();

metafad_tei_Module::registerModule();
metafad_mods_Module::registerModule();
