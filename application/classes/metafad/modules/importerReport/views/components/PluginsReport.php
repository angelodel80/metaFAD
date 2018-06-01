<?php
class metafad_modules_importerReport_views_components_PluginsReport extends org_glizy_components_ComponentContainer
{
    private $it;

    function process()
    {
        // TODO: controllo acl
        //glz_dbdebug();
        $c = $this->getAttribute('paginate');
        if (is_object($c)) {
            $c->setRecordsCount();
            $pageLimits = $c->getLimits();
        }

        if(__Request::get('show')!=null){
            $titolo=__Request::get('titolo');

            if(__Request::get('show')=='export') $job_name='metafad.modules.exporter.helpers.Batch';
            else if (__Request::get('show')=='import') $job_name='metafad.modules.importer.helpers.Batch';
            else $job_name='';

            $jobStatus = array();

            if (__Request::get('done')) {
                $jobStatus[] = "job_status = 'COMPLETED'";
            }

            if (__Request::get('inExecution'))  {
                $jobStatus[] = "job_status = 'RUNNING'";
            }

            if (__Request::get('error')) {
                $jobStatus[] = "job_status = 'ERROR'";
            }

            $datefrom=date('Y-m-d',strtotime(str_replace('/', '-',__Request::get('dateFrom'))));
            $dateto=date('Y-m-d',strtotime(str_replace('/', '-',__Request::get('dateTo')))).' 23:59:59';

            $this->it = org_glizy_objectFactory::createModelIterator('metacms.jobmanager.models.Job')
                 ->load(
                    'searchForm',
                    array(
                        'params' => array(
                            ':titolo' => '%'.$titolo.'%',
                            ':name' => '%'.$job_name.'%',
                            ':dateto' => $dateto,
                            ':datefrom' => $datefrom
                        ),
                        'replace' => array(
                            '##job_params##' => (!empty($jobStatus) ? implode(' OR ', $jobStatus) : 'TRUE')
                        )
                    )
                );
        } else {
            $this->it = org_glizy_objectFactory::createModelIterator('metacms.jobmanager.models.Job');
        }

        $this->it->orderBy('job_modificationDate', 'DESC');

        if (is_object($c)) {
            $this->it->limit($pageLimits['start'], $pageLimits['pageLength']);
            $c->setRecordsCount($this->it->count());
        }

    }

    function render()
    {
        $tableBlueprint = $this->getTableBlueprint();
        $header = $this->getHeaderRow($tableBlueprint);

        $output = '<div class="table-container"><table id="'.$this->getAttribute('id').'" class="table table-bordered table-striped">';
        $output .= '<thead><tr>'.$header.'</tr></thead>';
        $output .= '<tbody>';

        foreach ($this->it as $ar) {
            //Questo controlla la patch se è stata messa o meno
            $this->patch20170317Control($ar);

            $status = metacms_jobmanager_JobStatus::getDescription($ar->job_status);
            $strDowload="";
            if((substr($ar->job_description,0,23)=='Esportazione schede trc' || substr($ar->job_description,0,27)=='Esportazione schede iccdxml') && $status=="Eseguito"){
                $linkZip=unserialize($ar->job_params);
                $strDowload='<a href="'.org_glizy_Paths::get('ROOT').'export/'.$linkZip["foldername"].'.zip">Scarica Zip</a>';
            }
            if(substr($ar->job_description,0,24)=='Esportazione schede mets' && $status=="Eseguito"){
                $linkZip=unserialize($ar->job_params);
                $strDowload='<a href="'.org_glizy_Paths::get('ROOT').'export/'.$linkZip["foldername"].'.xml">Scarica Xml</a>';
            }
            $row = $this->getDataRow($ar, $status, $strDowload, $tableBlueprint);

            $output .= '<tr>'.$row.'</tr>';
        }

        $output .= '</tbody>';
        $output .= "</table></div>";

        $this->addOutputCode($output);
    }

    private function patch20170317Control($ar){
        if (!property_exists($ar->getRawData(), "job_creationDate")){
            throw new Exception("Aggiornare il sistema con la patch presente in \"wwwRoot/application/data/20170317_patch_jobs_tbl.sql\"");
        }
    }

    /**
     * Table building
     */
    /**
     * @return array
     */
    private function getTableBlueprint(){
        return array(
            array(
                "text" => "Descrizione",
                "field" => "descr",
                "tableAttr" => ""
            ),
            array(
                "text" => "Stato",
                "field" => "status",
                "tableAttr" => ""
            ),
            array(
                "text" => "Avanzamento",
                "field" => "progress",
                "tableAttr" => " style=\"text-align:center\""
            ),
            array(
                "text" => "Messaggio",
                "field" => "msg",
                "tableAttr" => " style=\"width:500px;text-align:center\""
            ),
            array(
                "text" => "Download",
                "field" => "download",
                "tableAttr" => " style=\"text-align:center\""
            ),
            array(
                "text" => "Aggiornato il",
                "field" => "lastModify",
                "tableAttr" => " style=\"text-align:center\""
            ),
            array(
                "text" => "Creato il",
                "field" => "createdAt",
                "tableAttr" => " style=\"text-align:center\""
            )
        );
    }

    /**
     * @param $tableBlueprint
     * @return string
     */
    private function getHeaderRow($tableBlueprint)
    {
        return implode("", array_map(function ($a) {
            return "<th{$a['tableAttr']}>{$a['text']}</th>";
        }, $tableBlueprint));
    }

    /**
     * @param $ar
     * @param $status
     * @param $strDowload
     * @param $tableBlueprint
     * @return string
     */
    private function getDataRow($ar, $status, $strDowload, $tableBlueprint)
    {
        $data = new stdClass();

        $data->descr = $ar->job_description;
        $data->status = $status;
        $data->progress = $ar->job_progress."%";
        $data->msg = $ar->job_message;
        $data->download = $strDowload;
        $data->lastModify = $ar->job_modificationDate;
        $data->createdAt = $ar->job_creationDate;

        $row = implode("", array_map(function ($a) use ($data) {
            $val = $data->{$a['field']};
            return "<td>$val</th>";
        }, $tableBlueprint));

        return $row;
    }
}
