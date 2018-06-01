<?php
class metafad_common_controllers_ajax_AutoComplete extends org_glizy_mvc_core_Command
{
    function execute($instituteKey, $model, $filters, $fieldName, $term)
    {
        $query = $this->buildQuery($instituteKey, $model, $filters, $fieldName, $term);

        $url = __Config::get('metafad.solr.url').'select?'.implode('&', $query);
        
        $content = json_decode(file_get_contents($url));
        
        $result = array();

        foreach ($content->response->docs as $doc) {
            $value = $doc->{$fieldName};
            if (is_array($value)) {
                foreach ($value as $s) {
                    if (preg_match('/.*'.$term.'.*/', $s)) {
                        $result[$s] = true;
                    }
                }
            } else {
                $result[$value] = true;
            }
        }

        $result = array_map('strval', array_keys($result));
        sort($result);

        $this->directOutput = true;

        return array_slice($result, 0, __Config::get('metafad.dataGridSolr.autoComplete'));
    }

    protected function buildQuery($instituteKey, $model, $filters, $fieldName, $term)
    {
        $q = array( 
            'document_type_t:"'.$model.'"',
            $fieldName.':*'.$term.'*',
        );

        if ($instituteKey) {
            $q[] = 'instituteKey_s:"'.$instituteKey.'"';
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
                } else {
                    $q[] = $filter['name'].':"'.$filter['value'].'"';
                }
            }
        }
        
        $query = array(
            'q='.urlencode(implode(' AND ', $q)),
            'fl='.$fieldName,
            'wt=json',
            'rows=100000000'
        );

        if (__Config::get('DEBUG')) {
            $query[] = 'indent=true';
        }

        return $query;
    }
}
