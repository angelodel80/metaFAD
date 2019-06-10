<?php
class metafad_common_controllers_ajax_SelectAll extends org_glizy_mvc_core_Command
{
    function execute($instituteKey, $model, $filters, $massive = false, $hasDigital = false)
    {
        if($massive)
        {
            $idList = explode("-", __Session::get('idList'));
            $ids = array();
            foreach ($idList as $i) {
                $ids[] = '"' . $i . '"';
            }

            $filters[] = array('type' => 'multiple', 'name' => 'id', 'value' => $ids);
        }

        if($hasDigital)
        {
            $filters[] = array('type' => 'boolean', 'name' => 'digitale_i', 'value' => '1');
        }

        $query = $this->buildQuery($instituteKey, $model, $filters);

        $url = __Config::get('metafad.solr.url').'select?'.implode('&', $query);
        $content = json_decode(file_get_contents($url));
        
        $result = array();
        foreach ($content->response->docs as $doc) {
            $result[] = $doc->id;
        }

        $this->directOutput = true;
        return $result;
    }

    protected function buildQuery($instituteKey, $model, $filters)
    {
        if(strpos($model,',') !== false)
        {
            $docQuery = array();
            $models = explode(',',$model);
            foreach($models as $m)
            {
                $docQuery[] = '(document_type_t:"' . $m . '")';
            }
            $q = array('('.implode(' OR ',$docQuery).')');
        }
        else
        {
            $q = array( 
                '(document_type_t:"'.$model.'")'
            );
        }

        if (metafad_common_helpers_LanguageHelper::checkLanguage($model)) 
        {
            $languagePrefix = $this->_application->getEditingLanguage();
            $q[] = 'language_s:"' . $languagePrefix . '"';
        }

        if ($instituteKey) {
            $q[] = '(instituteKey_s:"'.$instituteKey.'")';
        }

        if ($filters) {
            foreach ($filters as $filter) {
                if ($filter['type'] == 'date' || $filter['type'] == 'dateCentury') {
                    $f = explode(',', $filter['name']);
                    $v = $filter['value'];

                    if ($filter['type'] = 'dateCentury') {
                        $romanService = __ObjectFactory::createObject('metafad.common.helpers.RomanService');
                        $v[0] = $romanService->romanToInteger($v[0]);
                        $v[1] = $romanService->romanToInteger($v[1]);
                    }

                    if ($v[0]) {
                        $q[] = $f[0] . ':['.sprintf('%04d', $v[0]).' TO *]';
                    }

                    if ($v[1]) {
                        $q[] = $f[1] . ':[* TO '.sprintf('%04d', $v[1]).']';
                    }
                } 
                else if ($filter['type'] == 'multiple') {
                    $a = '(' . implode(' OR ', $filter['value']) . ')';
                    $q[] = '(' . $filter['name'] . ':' . $a . ')';
                } 
                else {
                    $q[] = $filter['name'].':"'.$filter['value'].'"';
                }
            }
        }
        
        $query = array(
            'q='.urlencode(implode(' AND ', $q)),
            'fl=id',
            'wt=json',
            'rows=100000000'
        );

        if (__Config::get('DEBUG')) {
            $query[] = 'indent=true';
        }

        return $query;
    }
}
