<?php
class metafad_solr_helpers_HasImageHelper extends GlizyObject
{
    public function hasImage($data, $type) {
    	if ($type == 'iccd' && $data->FTA) {
    		foreach($data->FTA as $k => $v) {
    			if ($v->{"FTA-image"}) {
    				return true;
    			}
    		}
    	} else if ($type == 'archive') {
    		if ($data->linkedStruMag || $data->mediaCollegati) {
    			return true;
    		}
    	}

    	return false;
    }
}
