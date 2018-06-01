<?php
class metafad_sbn_modules_sbnunimarc_controllers_ajax_RefreshKardexGrid extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute($kardexUrl)
    {
        $result = $this->checkPermissionForBackend('visible');
        if (is_array($result)) {
            return $result;
        }
        
      __Session::set('kardexUrl',$kardexUrl);
      return array('sendOutput' => 'kardexGrid', 'sendOutputState' => 'show', 'sendOutputFormat' => 'html' );
    }

}
