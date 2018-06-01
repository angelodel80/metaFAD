<?php
class metafad_gestioneDati_schedeSemplificate_controllers_ajax_RefreshFieldsList extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute($moduleName)
    {
      __Request::set('moduleNameForm',$moduleName);
      return array('sendOutput' => 'fieldList', 'sendOutputState' => 'edit', 'sendOutputFormat' => 'html');
    }
}
