<?php
class metafad_usersAndPermissions_IstituteCheckListener extends GlizyObject
{
    function __construct()
    {
        $this->addEventListener(GLZ_EVT_BEFORE_CREATE_PAGE, $this);
    }

    public function beforeCreatePage($event = null)
    {
        $user = $event->target->_user;
        $pageId = __Request::get('pageId');

        $instituteKey = metafad_usersAndPermissions_Common::getInstituteKey();

        // sulle pagine pubbliche il redirect non va fatto
        if ($user->id && !$instituteKey && !in_array($pageId, array('', 'utenti-e-permessi-selezione-istituto', 'utenti-e-permessi-istituto-mancante', 'Login', 'Logout'))) {
            // se l’utente appartiene a più istituti l’utente sceglierà con quale istituto entrare
            $relationsProxy = __ObjectFactory::createObject('metafad.usersAndPermissions.relations.models.proxy.RelationsProxy');

            if ($relationsProxy->hasMoreInstitutes($user->id)){
                org_glizy_helpers_Navigation::gotoUrl(__Link::makeUrl('link', array('pageId' => 'utenti-e-permessi-selezione-istituto')));
            } else {
                $instituteId = $relationsProxy->getInstituteId($user->id);

                if ($instituteId) {
                    $instituteProxy = __ObjectFactory::createObject('metafad.usersAndPermissions.institutes.models.proxy.InstitutesProxy');
                    $institute = $instituteProxy->getInstituteVoById($instituteId);
                    metafad_usersAndPermissions_Common::setInstituteKey($institute->institute_key);
                    $evt = array('type' => 'reloadAcl');
                    $this->dispatchEvent($evt);
                    $this->checkBackEndAccess($user);
                    org_glizy_helpers_Navigation::gotoUrl(__Link::makeUrl('link', array('pageId' => $pageId)));
                } else {
                    org_glizy_helpers_Navigation::gotoUrl(__Link::makeUrl('link', array('pageId' => 'utenti-e-permessi-istituto-mancante')));
                }
            }
        }

        if ($user->id && $instituteKey && in_array($pageId, array('dashboard', 'home'))) {
            $this->checkBackEndAccess($user);
        }

        __Config::set('gruppometa.dam.instance', $instituteKey);

        if ($pageId == 'Logout') {
            metafad_usersAndPermissions_Common::setInstituteKey(null);
        }
    }

    protected function checkBackEndAccess($user)
    {
        if (!$user->acl('home', 'all')) {
    		org_glizy_Session::set('glizy.user', null);
    		org_glizy_Session::set('glizy.userLogged', false);
            org_glizy_Session::set('glizy.loginError', org_glizy_locale_Locale::get('LOGGER_INSUFFICIENT_GROUP_LEVEL'));
            org_glizy_helpers_Navigation::gotoUrl(__Link::makeUrl('link', array('pageId' => 'Login')));
        }
    }
}