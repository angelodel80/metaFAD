<?php
class metafad_modules_thesaurus_controllers_Index extends metafad_common_controllers_Command
{
    public function execute()
    {
		if(__Config::get('metafad.be.hasDictionaries') === 'demo')
		{
			$this->view->getComponentById('importButton')->setAttribute('visible',false);
		}
    }
}
