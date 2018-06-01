<?php
class metafad_workflow_processes_views_renderer_StatusTitle extends GlizyObject
{
    function renderCell( $key, $value, $row )
    {
        if(!$value){
            return 'Non avviato';
        }
        else if($value == '1'){
            return 'In corso';
        }
        else{
            return 'Completato';
        }
    }
}