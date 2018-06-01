<?php
class archivi_controllers_Index extends metafad_common_controllers_Command
{
    public function execute()
    {
        $this->setComponentsVisibility('tabs', true);
        $this->setComponentsAttribute(array('stateHistory'), 'draw', false);
    }
}
