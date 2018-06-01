<?php
class metafad_teca_mets_controllers_ajax_SaveDraftClose extends metafad_teca_mets_controllers_ajax_SaveDraft
{
    public function execute($data)
    {
        $result = parent::execute($data);

        if (!isset($result['set'])) {
            return $result;
        }

        $this->directOutput = true;
        return array('url' => $this->changeAction(''));
    }

}
