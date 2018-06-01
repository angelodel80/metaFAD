<?php
class metafad_ecommerce_orders_views_components_OrderObjects extends org_glizy_components_Component
{
    private $helper;

    function init()
    {
      // define the custom attributes
      parent::init();
    }

    function render()
    {
      $this->helper = org_glizy_objectFactory::createModel('metafad.ecommerce.requests.views.helpers.ObjectInfoHelper');

      $id = __Request::get('id',null);
      if($id)
      {
        $priceTotal = 0;
        $items = org_glizy_objectFactory::createModelIterator('metafad.ecommerce.orders.models.Item')
                  ->where('orderitem_FK_order_id',$id);
        $itemOutput = '';
        foreach ($items as $i) {
          $priceTotal += (float)$i->orderitem_price;
          $itemData = explode('#',$i->orderitem_code);
          $itemData[] = $i->orderitem_price;
          //0 - type (sempre media)
          //1 - id del media (se non c'è acquisto full)
          //2 - id del record
          //3 - id licenza
          //4 - price
          $itemOutput .= $this->getObjectRecord($itemData);
        }
      }

      $output .= '<div class="form-group">
                      <label for="total" class="col-sm-2 control-label ">Totale speso</label>
                      <div class="col-sm-10">
                          <input id="total" name="total" readonly="readonly" title="Totale" class="form-control" type="text" value="'.$priceTotal.' €">
                      </div>
                  </div>'.$itemOutput;

      $this->addOutputCode($output);
    }

    private function getObjectRecord($itemData)
    {
      $output = '<fieldset><div class="container form-group"><div class="col-sm-12 container">';
      if($itemData[2])
      {
        $output .= $this->helper->getInfoFromMetaindex($itemData[2],true,true,$itemData);
      }
      $output .= '</div></div></fieldset>';
      return $output;
    }
}
