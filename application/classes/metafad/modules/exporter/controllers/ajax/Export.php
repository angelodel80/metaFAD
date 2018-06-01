<?php
class metafad_modules_exporter_controllers_ajax_Export extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute()
    {
        $result = $this->checkPermissionForBackend('publish');
        if (is_array($result)) {
            return $result;
        }

        if(__Request::get("confirmExport")=="Esporta"){
            echo "export".__Request::get("id");

            $arrayIds=explode("-",__Request::get("id"));
            $moduleName=__Request::get("pageId");
            $format=__Request::get("format");
            $autbib=__Request::get("autbib");
            $damInstance=__Config::get("gruppometa.dam.instance");

            //Creazione cartella export
            $milliseconds = microtime(true) * 100;
            $foldername =  $milliseconds . "_" . $moduleName . "_" . $format;

            //Creazione del job di export
            $jobFactory = org_glizy_ObjectFactory::createObject('metacms.jobmanager.JobFactory');
            $jobFactory->createJob('metafad.modules.exporter.helpers.Batch',
                                   array(
                                      'format' => $format,
                                      'arrayIds' => $arrayIds,
                                      'moduleName' => $moduleName,
                                      'autbib' => $autbib,
                                      'foldername' => $foldername,
                                      'damInstance' => $damInstance
                                    ),
                                   'Esportazione schede '.$format.' ('.count($arrayIds).')',
                                   'BACKGROUND');
            $url = org_glizy_helpers_Link::makeUrl( 'link', array( 'pageId' => 'metafad.modules.importerreport' ) );
            org_glizy_helpers_Navigation::gotoUrl($url);

          } 

    }

}
