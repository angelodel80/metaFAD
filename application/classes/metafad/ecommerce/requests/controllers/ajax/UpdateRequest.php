<?php
class metafad_ecommerce_requests_controllers_ajax_UpdateRequest extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute($id,$value,$fieldname,$type,$notify,$email)
    {
        $result = $this->checkPermissionForBackend('edit');
        if (is_array($result)) {
            return $result;
        }
        
    $ar = org_glizy_objectFactory::createModel('metafad.ecommerce.requests.models.Model');
    if($ar->load($id))
    {
      if($type != 'request_operator') {
        $ar->$fieldname = $value;
      }
      else {
        $ar->request_operator_id = $value['id'];
        $ar->request_operator = $value['text'];
      }
      $ar->save();

      $gateway = org_glizy_ObjectFactory::createObject('metafad.ecommerce.gateway.RequestGateway');
      //Invio email
      if($type == 'request_state')
      {
        if($notify === 'notifyBuy')
        {
          if($value == 'get' || $value == 'buyable' || $value == 'digitalized')
          $gateway->sendEmail($ar,$email);

        }
        else if($notify === 'notifyState') {
          $gateway->sendEmail($ar,$email);
        }
      }
    }
  }
}
