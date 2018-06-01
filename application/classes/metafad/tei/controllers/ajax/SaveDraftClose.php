<?php
class metafad_tei_controllers_ajax_SaveDraftClose extends metafad_tei_controllers_ajax_SaveDraft
{
    public function execute($data)
    {
        $result = parent::execute($data);

        if ($result['errors']) {
            return $result;
        }

        return array('url' => __Routing::makeUrl('link', array('pageId' => 'tei-manoscritto')));
    }
}