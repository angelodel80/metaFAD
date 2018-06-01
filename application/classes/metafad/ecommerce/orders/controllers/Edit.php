<?php
class metafad_ecommerce_orders_controllers_Edit extends org_glizycms_contents_controllers_activeRecordEdit_Edit
{
  public function execute($id, $templateID)
  {
    if ($id) {
      $order = org_glizy_objectFactory::createModel('metafad.ecommerce.orders.models.Model');
      if($order->load($id))
      {
        $contentProxy = org_glizy_objectFactory::createObject('org.glizycms.contents.models.proxy.ModuleContentProxy');
        //Recupero nome utente per join informazioni
        $userId = $order->order_FK_user_id;
        $user = $contentProxy->loadContent($userId, 'metafad.ecommerce.requests.models.FEUsers');

        $this->view->getComponentById('userName')->setAttribute('value',$user['user_firstName']);
        $this->view->getComponentById('userLastname')->setAttribute('value',$user['user_lastName']);
        $this->view->getComponentById('userEmail')->setAttribute('value',$user['user_email']);
        $this->view->getComponentById('userAddress')->setAttribute('value',$user['user_address']);
        $this->view->getComponentById('userCity')->setAttribute('value',$user['user_city']);
        $this->view->getComponentById('userProvince')->setAttribute('value',$user['user_state']);
        $this->view->getComponentById('userZipcode')->setAttribute('value',$user['user_zip']);
        $this->view->getComponentById('userState')->setAttribute('value',$user['user_country']);
        $this->view->getComponentById('userVat')->setAttribute('value',$user['user_vat']);
        $this->view->getComponentById('userCode')->setAttribute('value',strtoupper($user['user_fiscalCode']));

        $this->view->getComponentById('transactionCode')->setAttribute('value',strtoupper($order->order_code));
        $this->view->getComponentById('transactionNumber')->setAttribute('value',strtoupper($order->order_code));
        $this->view->getComponentById('document_creationDate')->setAttribute('value',strtoupper($order->order_date));
      }
    }
  }
}
