<?php

class metafad_gestioneDati_boards_controllers_ajax_ShowHistory extends metafad_common_controllers_ajax_CommandAjax
{
    protected function formatObj($obj)
    {
        $array = json_decode($obj, true);
        ksort($array);
        return explode("\n", str_replace("<\/p>", "<\/p>\n", json_encode($array, JSON_PRETTY_PRINT)));
    }

    public function execute($model, $a, $b)
    {
        $this->checkPermissionForBackend();

        $this->directOutput = true;

        $a = __Request::get('a');
        $b = __Request::get('b');
        $model = __Request::get('model');

        $it = org_glizy_objectFactory::createModelIterator($model);
        $it->where("document_detail_id", $a)
             ->allStatuses();
        $ar_a = $it->first();

        $it = org_glizy_objectFactory::createModelIterator($model);
        $it->where("document_detail_id", $b)
             ->allStatuses();
        $ar_b = $it->first();

        $document_a = $this->formatObj($ar_a->document_detail_object);
        $document_b = $this->formatObj($ar_b->document_detail_object);

        glz_importLib('Diff/Diff.php');
        glz_importLib('Diff/Diff/Renderer/Html/SideBySide.php');
        // Options for generating the diff
        $options = array(
          'context' => 5
        );
        $diff = new Diff($document_a, $document_b, $options);

        $renderer = new Diff_Renderer_Html_SideBySide;
        $result = $diff->Render($renderer);
        return array('html' => $result);
    }
}
