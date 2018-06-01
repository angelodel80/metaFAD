<?php

class metafad_usersAndPermissions_users_controllers_EditPersonal extends metafad_usersAndPermissions_users_controllers_Edit
{
    public function execute()
    {
		//Verifico se l'utente avrebbe permessi di editing, in caso positivo gli permetto
		//di modificare anche campi che a livello di sistema sono "delicati" (es: gruppo)
		if($this->user->acl('utenti-e-permessi-utenti','edit',false))
		{
			$setReadOnlyFalse = array('user_FK_usergroup_id','user_isActive','institute','roles');
			foreach($setReadOnlyFalse as $c)
			{
				$this->view->getComponentById($c)->setAttribute('readOnly',false);
			}		
		}

		//Imposto valori url per il saveClose della pagina personale, per tornare al punto
		//in cui mi trovavo inizialmente
		$current = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		$referer = $_SERVER["HTTP_REFERER"];

		if(__Session::get('lastPage') != $referer && strpos($referer,$current) === false)
		{
			__Session::set('lastPage', $referer);
		}

		parent::execute($this->user->id);
    }
}
