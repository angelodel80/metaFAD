<?php
class metafad_mods_controllers_ajax_SaveClose extends metafad_mods_controllers_ajax_Save
{
    public function execute($data)
    {
        $result = parent::execute($data);

        if ($result['errors']) {
            return $result;
        }

        return array('url' => __Routing::makeUrl('link', array('pageId' => 'mods')));
    }
}