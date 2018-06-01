<?php
class metafad_teca_DAM_Common
{
    public static function getDamUrl($damInstance = null)
    {
        return __Config::get('gruppometa.dam.url').($damInstance ? $damInstance : __Config::get('gruppometa.dam.instance'));
    }

    public static function getDamBaseUrl()
    {
        return __Config::get('gruppometa.dam.url');
    }

    public static function getDamUrlLocal($damInstance = null)
    {
        return __Config::get('gruppometa.dam.url.local').($damInstance ? $damInstance : __Config::get('gruppometa.dam.instance'));
    }

    public static function getDamBaseUrlLocal()
    {
        return __Config::get('gruppometa.dam.url.local');
    }

    // fix temporaneo da togliere quando verranno sganciati i servizi del BE funzionali al FE
    public static function replaceUrl($url)
    {
        return str_replace(array('mibac_museowebfad/rest/dam', 'dam/admin/rest/dam', 'metafad/rest/dam'), array('dam', 'dam', 'dam'), $url);
    }

    public static function getDamBaseUrlLocalWithQueryString()
    {
        $queryString = preg_replace('/&/', '?&', $_SERVER['QUERY_STRING'], 1);
        return self::getDamBaseUrlLocal().str_replace('dam/', '', $queryString);
    }
}
