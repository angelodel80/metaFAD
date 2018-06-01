<?php

class metafad_workflow_activities_models_proxy_ActivitiesProxy extends GlizyObject
{
    public function findTerm($fieldName, $model, $query, $term, $proxyParams)
    {
        $it = org_glizy_objectFactory::createModelIterator('metafad.workflow.activities.models.Model');

        if ($term != '') {
            $it->where('title', '%'.$term.'%', 'ILIKE');
        }

        $result = array();

        foreach($it as $ar) {
            $result[] = array(
                'id' => $ar->getId(),
                'text' => $ar->title,
            );
        }

        return $result;
    }
}