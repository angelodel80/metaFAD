<?php
class metafad_common_controllers_ajax_AutoComplete extends org_glizy_mvc_core_Command
{
    function execute($instituteKey, $model, $filters, $fieldName, $term)
    {
        $query = $this->buildQuery($instituteKey, $model, $filters, $fieldName, $term);

        $url = __Config::get('metafad.solr.url').'select?'.implode('&', $query);
        
        $content = json_decode(file_get_contents($url));
        
        $result = array();

        $facetField = $content->facet_counts->facet_fields;

        $facets = $facetField->$fieldName;

        for ($i = 0; $i < count($facets); $i += 2) {
            $term = $facets[$i];
            $termFreq = $facets[$i + 1];

            if ($termFreq > 0) {
                $result[] = $term;
            }
        }

        sort($result);

        $this->directOutput = true;

        return array_slice($result, 0, __Config::get('metafad.dataGridSolr.autoComplete'));
    }

    protected function buildQuery($instituteKey, $model, $filters, $fieldName, $term)
    {
        $q = array( 
            '(document_type_t:"'.$model.'")',
            '('.$fieldName.':*'.str_replace(' ','*',$term).'*)',
        );

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
                else if($filter['type'] == 'multiple')
                {
                    $a = '(' . implode(' OR ', $filter['value']) . ')';
                    $q[] = '(' . $filter['name'] . ':'. $a . ')';
                }
                else {
                    $q[] = '(' . $filter['name'].':"'.$filter['value'].'")';
                }
            }
        }

        $query = array(
            'q=' . urlencode(implode(' AND ', $q)),
            'fl=' . $fieldName,
            'facet=true',
            'facet.field=' . $fieldName,
            'facet.limit=' . __Config::get('metafad.dataGridSolr.autoComplete'),
            'wt=json',
            'rows=0'
        );
        
        if (__Config::get('DEBUG')) {
            $query[] = 'indent=true';
        }

        return $query;
    }
}
