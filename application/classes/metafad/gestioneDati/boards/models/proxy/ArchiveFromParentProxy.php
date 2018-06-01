<?php
class metafad_gestioneDati_boards_models_proxy_ArchiveFromParentProxy extends GlizyObject
{
  private $result = array();

  public function findTerm($fieldName, $model, $query, $term, $proxyParams)
  {

    if($proxyParams)
    {
      $it = org_glizy_ObjectFactory::createModelIterator('archivi.models.Model')
      ->where('parent', $proxyParams->parentId)
      ->allTypes()
      ->limit(0,100);

      $this->iterateChildren($it, $term);
    }

    return $this->result;
  }

  public function iterateChildren($children, $term)
  {
    foreach ($children as $child) {
      if($child->getType() == 'archivi.models.UnitaArchivistica' || $child->getType() == 'archivi.models.UnitaDocumentaria')
      {
        $ar = org_glizy_ObjectFactory::createModelIterator($child->getType())
              ->where('document_id',$child->getId())->first();
        if($term && stripos($ar->_denominazione, $term) === false)
        {
          //Non va recuperato questo nodo
        }
        else
        {
          $prefix = ($child->getType() == 'archivi.models.UnitaArchivistica') ? 'UA': 'UD';
          $title = $ar->denominazione ?: $ar->titoloAttribuito;
          $this->result[] = array(
            'id' => $child->getId(),
            'text' => $ar->_denominazione,
            'model' => $child->getType()
          );
        }
      }

      $it = org_glizy_ObjectFactory::createModelIterator('archivi.models.Model')
              ->where('parent', $child->getId())
              ->allTypes();

      if($term) 
      {
        $it = $it->where('_denominazione', '%' . $term . '%', 'ILIKE');
      }

      if($it->count())
      {
        $this->iterateChildren($it, $term);
      }
    }
  }
}
