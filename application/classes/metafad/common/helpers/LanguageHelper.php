<?php
class metafad_common_helpers_LanguageHelper extends GlizyObject
{
    public static function checkLanguage($model)
    {
        //Prima verifico che effettivamente sia abilitato il multilingua
        if(!__Config::get('metafad.hasMultiLanguage'))
        {
            return false;
        }

        //Controllo poi se il model in questione è multilingua o meno
        //(es: SBN non è multilingua)
        $m = __ObjectFactory::createModel($model);
        if(method_exists($m,'canTranslate'))
        {
            return $m->canTranslate();
        }
        else
        {
            return false;
        }
    }

    public static function appendLanguagePrefix($id)
    {
        $application = org_glizy_ObjectValues::get('org.glizy', 'application');
        $languagePrefix = $application->getEditingLanguage();
        return $languagePrefix . '-' . $id;
    }
}