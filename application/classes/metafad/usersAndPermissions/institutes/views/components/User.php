<?php
class metafad_usersAndPermissions_institutes_views_components_User extends org_glizy_components_Component
{
    function render()
    {
        $userId = $this->_user->id;
        $fullName = implode(' ', array($this->_user->firstName, $this->_user->lastName));
        $instituteName = metafad_usersAndPermissions_Common::getInstituteName();

        $relationsProxy = __ObjectFactory::createObject('metafad.usersAndPermissions.relations.models.proxy.RelationsProxy');
        $institutesRoles = $relationsProxy->load($userId);
        $ruolo = $institutesRoles[0]->roles[0]->text;

        $userInfo = $fullName;

        if ($ruolo) {
            $userInfo .= ' ('.$ruolo.')';
        }

        if ($instituteName) {
            $userInfo .= ' - ' . $instituteName;
        }

        $profilePageUrl = __Link::makeUrl('gestione-profilo');

		if(__Config::get('metafad.be.hasInstitutes'))
		{
      $proxy = __ObjectFactory::createObject('metafad.usersAndPermissions.relations.models.proxy.RelationsProxy');
      $institutes = $proxy->getInstitutesOfCurrentUser();

      if(sizeof($institutes) > 1)
      {
			$changeInstitute = <<<EOD
<div>
  <a href="utenti-e-permessi-selezione-istituto" class="btn btn-default btn-flat">Cambia istituto</a>
</div>
EOD;
      }
		}

        $output .= <<<EOD
<div class="navbar-custom-menu">
    <ul class="nav navbar-nav">
      <!-- User Account: style can be found in dropdown.less -->
      <li class="dropdown user user-menu">
        <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
          <img src="application/template/img/avatar.png" class="user-image" alt="User Image">
          <span class="hidden-xs">$userInfo</span>
        </a>
        <ul class="dropdown-menu">
          <!-- User image -->
          <li class="user-header">
            <img src="application/template/img/avatar.png" class="img-circle" alt="User Image">
            <p>
              $fullName - $ruolo
              <small>$instituteName</small>
            </p>
          </li>
          <!-- Menu Footer-->
          <li class="user-footer">
              <div>
                <a href="$profilePageUrl" class="btn btn-default btn-flat">Profilo</a>
              </div>
              $changeInstitute
              <div class="pull-right">
                <a href="Logout" class="btn btn-default btn-flat">Esci</a>
              </div>
          </li>
        </ul>
      </li>
    </ul>
</div>
EOD;
        $this->addOutputCode($output);
    }
}
