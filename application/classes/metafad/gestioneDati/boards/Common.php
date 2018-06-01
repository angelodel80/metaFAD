<?php
class metafad_gestioneDati_boards_Common
{
    public static function logAction($isNew, $pageId, $action, $document, $id, $objectName = 'scheda')
    {
        $label =  $document->getTitle();
        $url = org_glizy_helpers_Link::makeLink('actionsMVC', array(
            'pageId' => $pageId,
            'action' => $action,
            'id' => $id,
            'label' => $label ? $label : 'Senza titolo'
        ));

        $message = ($isNew ? 'Creazione '.$objectName : 'Modifica '.$objectName) . ' ' . $url;
        metafad_Metafad::logAction($message, 'audiction');
    }
}
