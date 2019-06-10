<?php
class archivi_controllers_ajax_GetTreeFromParent extends metafad_common_controllers_ajax_CommandAjax
{
    private $iconClasses;

    public function execute($id)
    {
        $result = $this->checkPermissionForBackend('visible');
        if (is_array($result)) {
            return $result;
        }

        $archiviProxy = __ObjectFactory::createObject('archivi.models.proxy.ArchiviProxy');

        $this->iconClasses = (array)json_decode(__Config::get('metafad.archive.treeIcon'));
        
        $children = array();

        $options = array('fields' => 'id,document_type_t,livelloDiDescrizione_s,denominazione_s,cronologia_s,doc_store');
        $docs = $archiviProxy->getChildren($id);

        foreach($docs as $doc) {
            $options = array('fields' => 'id');
            $docs2 = $archiviProxy->getChildren($doc->id, $options);
            $hasChildren = count($docs2) > 0;
            $children[] = $this->createNodeFromDoc($doc, $hasChildren);
        }

        $this->directOutput = true;
        return $children;
    }

    protected function createNodeFromDoc($doc, $hasChildren)
    {
        $id = $doc->id;
        $documentType = $doc->document_type_t[0];
        $livelloDiDescrizione = $doc->livelloDiDescrizione_s;
        $title = $doc->denominazione_s . ' || ' . $doc->cronologia_s;
        $pageId = "archivi-".strtolower(str_replace("archivi.models.", "", $documentType));
        $doc_store = json_decode($doc->doc_store[0]);

        $iconclass = ($livelloDiDescrizione == 'documento-allegato') ? 'fa fa-paperclip' : $this->iconClasses[$documentType];

        $node = array(
            'id' => $id,
            'title' => $title, //POLODEBUG-481 BE, Punto 2
            'type' => $livelloDiDescrizione,
            'folder' => $hasChildren ? true : false,
            'lazy' => $hasChildren ? true : false, // lazy è true se il nodo ha figli
            'canAdd' => $livelloDiDescrizione != 'documento-allegato' && $livelloDiDescrizione != 'unita-documentaria',
            'canEdit' => $doc_store->hasPublishedVersion,
            'canEditDraft' => $doc_store->hasDraftVersion,
            'routingEdit' => __Routing::makeUrl('archiviMVC', array(
                'id' => $id,
                'pageId' => $pageId,
                'sectionType' => $livelloDiDescrizione,
                'action' => 'edit'
            )),
            'routingEditDraft' => __Routing::makeUrl('archiviMVC', array(
                'id' => $id,
                'pageId' => $pageId,
                'sectionType' => $livelloDiDescrizione,
                'action' => 'editDraft'
            )),
            'iconclass' => $iconclass
        );

        return $node;
    }
    
}
