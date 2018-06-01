<?php
class archivi_controllers_ajax_SaveDraftClose extends archivi_controllers_ajax_SaveDraft
{
    public function execute($data)
    {
        $result = parent::execute($data);

        $pages = array(
            "archivi-complessoarchivistico",
            "archivi-unitaarchivistica",
            "archivi-unitadocumentaria"
        );

        if ($result['errors']) {
            return $result;
        }

        //POLODEBUG-312
        if (in_array(__Request::get('pageId'), $pages)){
            return array('url' => $this->changePage('linkCurrentPage', array("pageId" => "archivi-complessoarchivistico")));
        } else {
            return array('url' => $this->changeAction(''));
        }
    }
}