<?php
class metafad_gestioneDati_boards_controllers_Relations extends metafad_common_controllers_Command
{
    public function execute($id)
    {
        $this->checkPermissionForBackend('edit');

        $stateLink = __Session::get('prevState');
        $c = $this->view->getComponentById('editTab');
        $c->setAttribute('routeUrl',$stateLink);
    }
}
