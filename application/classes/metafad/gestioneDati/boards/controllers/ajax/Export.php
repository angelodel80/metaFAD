<?php
class metafad_gestioneDati_boards_controllers_ajax_Export extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute()
    {
		if(__Config::get('metafad.be.hasExport') === 'demo')
		{
			$this->directOutput = true;
            $this->logAndMessage(__T('Spiacente, questa funzionalità non è disponibile nella modalità DEMO.'), '', GLZ_LOG_MESSAGE);
            return array('url' => __Link::makeUrl('link', array('pageId' => 'archive_export_mets')));
		}

        $result = $this->checkPermissionForBackend('publish');
        if (is_array($result)) {
            return $result;
        }

        $ids = (__Request::get('ids')) ? : '';
        $exportAll = (__Request::get('exportAll')) ? : 'false';
        $exportSelected = (__Request::get('exportSelected')) ? : 'false';
        $exportTitle = (__Request::get('exportTitle')) ? : '';
        $exportFormat = (__Request::get('exportFormat')) ? : '';
        $exportAutBib = (__Request::get('exportAutBib')) ? : 'false';
        $exportEmail = (__Request::get('exportEmail')) ? : '';
        $module = (__Request::get('pageId')) ? : '';
        $instKey = metafad_usersAndPermissions_Common::getInstituteKey();

        if (trim($exportTitle) == '') {
            $this->directOutput = true;
            return array('msg' => 'ATTENZIONE: Inserire un titolo valido');
        }
        // if($exportEmail=='')
        // {
        //   $this->directOutput = true;
        //   return array('msg' => 'ATTENZIONE: Inserire un\'email valida', 'url' => null);
        // }

        if ($exportAll == 'true') {
            $schList = array();
            $lastSearch = __Session::get('lastSearch');
            $query = $lastSearch['search'];
            $numFound = $lastSearch['numFound'];
            $query = str_replace('&rows=10', '&rows='.$numFound, $query);
            $request = org_glizy_objectFactory::createObject('org.glizy.rest.core.RestRequest', $query);
            $request->setTimeout(1000);
            $request->setAcceptType('application/json');
            $request->execute();

            $docs = json_decode($request->getResponseBody())->response->docs;
            foreach($docs as $d) {
                $schList[] = $d->id;
            }
        }
        else if ($exportSelected == 'true') {
            $schList = $ids;
        }
        else {
            // $this->logAndMessage('ATTENZIONE: Selezionare con apposito checkbox i record della sintetica che si desidera esportare', '', true);
            // $this->changeAction('export');
            $this->directOutput = true;
            return array('msg' => 'ATTENZIONE: Selezionare con apposito checkbox i record della sintetica che si desidera esportare');
        }

        if (sizeof($schList) == 0 || $schList[0] == '') {
            // $this->logAndMessage('ATTENZIONE: Non è stato selezionato nessun record da esportare!', '', true);
            // $this->changeAction('export');
            $this->directOutput = true;
            return array('msg' => 'ATTENZIONE: Non è stato selezionato nessun record da esportare!');
        }
        else {
            $module = substr($module, 0, strpos($module, '_'));
            if ($module == 'archive') $exportFormat = 'mets';

            //Creazione cartella export
            $milliseconds = microtime(true) * 100;
            $foldername = $exportTitle.
            "_".$milliseconds;

            $host = GLZ_HOST;

            //Creazione del job di export
            $jobFactory = org_glizy_ObjectFactory::createObject('metacms.jobmanager.JobFactory');
            $jobFactory->createJob('metafad.modules.exporter.helpers.Batch',
                array(
                    'format' => $exportFormat,
                    'arrayIds' => $schList,
                    'moduleName' => $module,
                    'autbib' => $exportAutBib,
                    'foldername' => $foldername,
                    'damInstance' => $instKey,
                    'email' => $exportEmail,
                    'title' => $exportTitle,
                    'host' => $host
                ),
                'Esportazione schede '.$exportFormat.
                ' ('.count($schList).
                ') - '.$exportTitle,
                'BACKGROUND');

            $this->directOutput = true;
            $url = __Routing::makeUrl('link', array('pageId' => 'metafad.modules.importerReport'));
            return array('url' => $url);
        }
    }
}
