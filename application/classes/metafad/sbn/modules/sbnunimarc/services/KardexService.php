<?php
class metafad_sbn_modules_sbnunimarc_services_KardexService extends GlizyObject
{
    protected function getQueryFromUrl($url)
    {
        $basename = pathinfo(urldecode($url), PATHINFO_BASENAME);
        $qs = substr($basename, strpos($basename, '?')+1);
        foreach (explode('&', $qs) as $v) {
            $qv = explode('=', $v);
            $query['kardex_'.$qv[0]] = trim($qv[1]);
        }

        return $query;
    }

    // gestione caching kardex
    public function getData($url, $force=false)
    {
        $query = $this->getQueryFromUrl($url);
        $ar = __ObjectFactory::createModel('metafad.sbn.modules.sbnunimarc.model.Kardex');
        
        if (!$ar->find($query) || $force)  {
            foreach ($query as $k => $v) {
                $ar->$k = $v;
            }
            $data = file_get_contents($url);
            $ar->kardex_data = json_encode(json_decode($data));
            $ar->kardex_creationDate = new org_glizy_types_DateTime();
            $ar->kardex_modificationDate = new org_glizy_types_DateTime();
            $ar->save();
        }

        return $ar->kardex_data;
    }

    public function resynchData($url)
    {
        $this->getData($url, true);
        $query = $this->getQueryFromUrl($url);
        $this->updateFE($query['kardex_bid']);
    }

    // collega uno strumag a un fascicolo
    public function attachStruMag($url, $fascicoloId, $struMagObj)
    {
        $data = json_decode($this->getData($url));
        $fascicoloDaAgginciare = explode(';', $fascicoloId);
        
        if ($data->kardexType->inventario[0]->fascicolo) {
            $found = false;
            foreach ($data->kardexType->inventario[0]->fascicolo as $fascicolo) {
                if ($fascicolo->annata == $fascicoloDaAgginciare[0] &&
                    $fascicolo->volume == $fascicoloDaAgginciare[1] &&
                    $fascicolo->numerazione == $fascicoloDaAgginciare[2]) {
                    $fascicolo->linkedStruMag = $struMagObj;
                    $found = true;
                    break;
                }
            }

            if ($found) {
                $query = $this->getQueryFromUrl($url);
                $ar = __ObjectFactory::createModel('metafad.sbn.modules.sbnunimarc.model.Kardex');
                if ($ar->find($query))  {
                    $ar->kardex_data = json_encode($data);
                    $ar->kardex_modificationDate = new org_glizy_types_DateTime();
                    $ar->save();
                }

                $this->updateFE($query['kardex_bid']);
            }
        }
    }

    // scollega uno strumag a un fascicolo
    public function detachStruMag($url, $fascicoloId)
    {
        $data = json_decode($this->getData($url));
        $fascicoloDaScollegare = explode(';', $fascicoloId);
        
        if ($data->kardexType->inventario[0]->fascicolo) {
            $found = false;
            foreach ($data->kardexType->inventario[0]->fascicolo as $fascicolo) {
                if ($fascicolo->annata == $fascicoloDaScollegare[0] &&
                    $fascicolo->volume == $fascicoloDaScollegare[1] &&
                    $fascicolo->numerazione == $fascicoloDaScollegare[2]) {
                    $fascicolo->linkedStruMag = null;
                    $found = true;
                    break;
                }
            }

            if ($found) {
                $query = $this->getQueryFromUrl($url);
                $ar = __ObjectFactory::createModel('metafad.sbn.modules.sbnunimarc.model.Kardex');
                if ($ar->find($query))  {
                    $ar->kardex_data = json_encode($data);
                    $ar->kardex_modificationDate = new org_glizy_types_DateTime();
                    $ar->save();
                }

                $this->updateFE($query['kardex_bid']);
            }
        }
    }

    // aggiorna i dati solr di FE
    public function updateFE($bid)
    {
        $doc = new StdClass();
        $doc->id = $bid;
        
        $kardex_only_store = new StdClass();

        $it = __ObjectFactory::createModelIterator('metafad.sbn.modules.sbnunimarc.model.Kardex')
            ->where('kardex_bid', $bid);

        $firstStrumagId = null;

        foreach ($it as $ar) {
            $fascicoliStrumMag = array();
            
            $data = json_decode($ar->kardex_data);

            if ($data->kardexType->inventario[0]->fascicolo) {
                foreach ($data->kardexType->inventario[0]->fascicolo as $fascicolo) {
                    if ($fascicolo->linkedStruMag) {
                        $f = new StdClass();
                        $f->annata = $fascicolo->annata;
                        $f->volume = $fascicolo->volume;
                        $f->numerazione = $fascicolo->numerazione;
                        $f->dataPubblicazione = $fascicolo->dataPubblicazione;
                        $f->tipo = $fascicolo->tipo;
                        $f->descrizione = $fascicolo->descrizione;
                        $f->strumagId = $fascicolo->linkedStruMag->id;

                        if (!$firstStrumagId) {
                            $firstStrumagId = $f->strumagId ;
                        }

                        $fascicoliStrumMag[] = $f;
                    }
                }

                if (!empty($fascicoliStrumMag)) {
                    $kardex_only_store->{$ar->kardex_inventario} = $fascicoliStrumMag;
                }
            }
        }

        $kardexArray = (array) $kardex_only_store;

        if (empty($kardexArray)) {
            $evt = array(
                'type' => 'deleteRecord',
                'data' => array(
                    'id' => $bid,
                    'option' => array(
                        'url' => __Config::get('metafad.solr.url.fe'), 
                        'commit' => true
                    )
                )
            );
        } else {
            $doc->kardex_only_store = json_encode($kardex_only_store);
    
            $evt = array(
                'type' => 'insertData',
                'data' => array(
                    'data' => $doc,
                    'option' => array(
                        'url' => __Config::get('metafad.solr.url.fe'), 
                        'commit' => true
                    )
                )
            );

        }
        
        $this->dispatchEvent($evt);
        
        $this->updateSbnImage($bid, $firstStrumagId);
    }

    public function getFirstStruMagId($bid)
    {
        $it = __ObjectFactory::createModelIterator('metafad.sbn.modules.sbnunimarc.model.Kardex')
            ->where('kardex_bid', $bid);

        foreach ($it as $ar) {
            $data = json_decode($ar->kardex_data);

            if ($data->kardexType->inventario[0]->fascicolo) {
                foreach ($data->kardexType->inventario[0]->fascicolo as $fascicolo) {
                    if ($fascicolo->linkedStruMag) {
                        return $fascicolo->linkedStruMag->id;
                    }
                }
            }
        }

        return null;
    }

    protected function updateSbnImage($bid, $struMagId)
    {
        $ar = org_glizy_objectFactory::createModel('metafad.sbn.modules.sbnunimarc.model.Model');
        $ar->find(array('id' => $bid));
        $data = $ar->getRawData();
        $data->__id = $data->id;
        
        $fi = org_glizy_objectFactory::createObject('metafad.viewer.helpers.FirstImage');
        $firstImage = $fi->execute($bid, 'sbn');

        if (!$firstImage && $struMagId) {
            $firstImage = $fi->getImageFromStruMag($struMagId);
            if ($firstImage) {
                $data->hasFirstImage = true;
            }
        }

        $updateSbn = org_glizy_ObjectFactory::createObject('metafad_sbn_modules_sbnunimarc_model_proxy_UpdateSbnProxy');
        $updateSbn->updateSbnDigitale($data, $firstImage['firstImage']);
    }
}