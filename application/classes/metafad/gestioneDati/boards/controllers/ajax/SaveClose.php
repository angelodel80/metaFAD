<?php
class metafad_gestioneDati_boards_controllers_ajax_SaveClose extends metafad_gestioneDati_boards_controllers_ajax_Save
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
