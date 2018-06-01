<?php

class metafad_sbn_modules_sbnunimarc_controllers_Show extends org_glizycms_contents_controllers_moduleEdit_Edit
{
    public function execute($id)
    {
// TODO controllo ACL
        if ($id) {
            // read the module content
            $c = $this->view->getComponentById('__model');
            __Request::set('model', $c->getAttribute('value'));

            //*************** show con id **************

            /*$contentproxy = org_glizy_objectFactory::createObject('org.glizycms.contents.models.proxy.ModuleContentProxy');
            $data = $contentproxy->loadContent($id, $c->getAttribute('value'));*/

            //*************************************

            //*************** show con bid **************
            $data = array();

            $it = org_glizy_objectFactory::createModelIterator($c->getAttribute('value'));

            $ar = $it->where('id', $id)->orderBy('document_detail_modificationDate','DESC')->first();

            if ($ar) {
                $objData = $ar->getRawData();
                $data = json_decode(json_encode($objData), true);
            }
            //*************************************

            if ($data['hasParts']) {
                $this->view->getComponentById('textBoardLink')->setAttribute('enabled', 'true');
                $this->view->getComponentById('relatedBoardLink')->setAttribute('enabled', 'true');
                $this->view->getComponentById('relatedBoardGrid')->setAttribute('enabled', 'true');
            };

            $data['__id'] = $id;

            $inventoryComponent = $this->view->getComponentById('inventoryNumber');
            $inventoryComponentStrumag = $this->view->getComponentById('strumagInventoryNumber');
            $dataInventory = $inventoryComponent->getAttribute('data');
            $inventoryComponent->setAttribute('data',$dataInventory.';proxy_params={##inventory##:##'.str_replace("\"","'",json_encode($data['inventory'])).'##}');
            $inventoryComponentStrumag->setAttribute('data',$dataInventory.';proxy_params={##inventory##:##'.str_replace("\"","'",json_encode($data['inventory'])).'##}');

            $this->enableJSTab($data);

            $inventoryCollectionCopiesBE = array();

            foreach ($data as $key => $value) {
                if($key == 'linkedMedia' || $key == 'linkedInventoryMedia' || $key == 'linkedStruMag' || $key == 'linkedInventoryStrumag' || $key == 'ecommerceLicenses' || $key == 'visibility')
                {
                  continue;
                }
                if (is_array($value)) {
                    $objectHTML = $key . '_html';
                    $objectPlain = $key . '_plain';
                    if ($value[0]->$objectHTML) {
                        unset($value[0]->$objectPlain);
                    }
                }
                if ($this->view->getComponentById($key)) {
                    $count = 0;
                    if (is_array($value)) {
                        foreach ($value as $val) {
                            if ($key == 'inventoryCollectionCopiesBE') {
                                if ($postBid = strstr($val, 'bid=')) {
                                    $tmp = explode("\n",strip_tags($postBid));
                                    $postBid = $tmp[0];
                                    $arrayInventory = explode("\n",$val);
                                    
                                    $inventoryCollectionCopiesBE[] = $this->processKardex($arrayInventory, $val);
                                }
                            } else if ($postBid = strstr($val, 'BID=')) {
                                $str = substr($postBid, 0, strpos($postBid, '">'));
                                $bid = str_replace('BID=', '', $str);
                                $url = __Link::makeURL('actionsMVC',
                                    array(
                                        'pageId' => 'metafad.sbn.unimarcSBN_popup',
                                        'title' => __T('GLZ_RECORD_EDIT'),
                                        'action' => 'show', 'id' => $bid));
                                $data[$key][$count] = str_replace('href="${page}', 'class="rif" data-url="' . $url, $data[$key][$count]);
                                $data[$key][$count] = str_replace('?BID=' . $bid . '">', '">', $data[$key][$count]);
                            } else if ($postVid = strstr($val, 'VID=')) {
                                $str = substr($postVid, 0, strpos($postVid, '">'));
                                $vid = str_replace('VID=', '', $str);
                                $url = __Link::makeURL('actionsMVC',
                                    array(
                                        'pageId' => 'metafad.sbn.modules.authoritySBN_popup',
                                        'title' => __T('GLZ_RECORD_EDIT'),
                                        'action' => 'show', 'id' => $vid));
                                $data[$key][$count] = str_replace('href="${page}', 'class="rif" data-url="' . $url, $data[$key][$count]);
                                $data[$key][$count] = str_replace('?VID=' . $vid . '">', '">', $data[$key][$count]);
                            }
                            $count++;
                        }
                        $this->view->getComponentById($key)->setAttribute('enabled', 'true');
                    }
                }
            }

            $data['inventoryCollectionCopiesBE'] = $inventoryCollectionCopiesBE ? : $data['inventoryCollectionCopiesBE']; 

            if(!is_array($data['localization']))
            {
              $data['localization'] = array($data['localization']);
            }

            $localizations = array();

            foreach ($data['localization'] as $localization) {
                if ($localization == 'Biblioteca del Pio Monte della Misericordia') {
                    $localizations[] = 'Pio Monte della Misericordia';
                } else {
                    $localizations[] = $localization;
                }
            }

            $instituteKey = metafad_usersAndPermissions_Common::getInstituteKey();
            $instituteName = metafad_usersAndPermissions_Common::getInstituteName();

            if ($instituteKey != '*' && !in_array(strtolower($instituteName), array_map('strtolower', $localizations))) {
                $c = $this->view->getComponentById('linkeMedia_tab');
                $c->setAttribute('visible', false);
            }

            $c = $this->view->getComponentById('relatedBoardGrid');
            $c->setAttribute('bid', $data['identificationCode'][0]);
            $this->view->getComponentById('editForm')->setData($data);
        }
    }
    
    

    protected function processKardex($arrayInventory, $val)
    {
        $instituteProxy = __ObjectFactory::createObject('metafad.usersAndPermissions.institutes.models.proxy.InstitutesProxy');
        $vo = $instituteProxy->getInstituteVoByKey(metafad_usersAndPermissions_Common::getInstituteKey());
        $institutePrefix = $vo->institute_prefix;
        
        $inventoryCollectionCopiesBE = htmlspecialchars_decode(str_replace('${kardexService}?', __Config::get('metafad.kardex.url'), $val));
        
        $kardexService = __ObjectFactory::createObject('metafad.sbn.modules.sbnunimarc.services.KardexService');
        
        //Estraggo ogni link al kardex e lo elaboro
        $count = 0;
        foreach ($arrayInventory as $v) {
            if (strpos($v,'${kardexService}?') !== false) {
                $url = htmlspecialchars_decode(strip_tags(str_replace(array('${kardexService}?','Kardex'),array(__Config::get('metafad.kardex.url'),''),$v)));
                $file = $kardexService->getData($url);
                if ($file) {
                    $fileDecode = json_decode($file);
                    $text = ($fileDecode->kardexType->inventario[0]->documento->isbd) ? $fileDecode->kardexType->inventario[0]->documento->isbd : 'Mostra' ;
                } else {
                    $text = 'Titolo non disponibile' ;
                }

                $replace = '<span class="OpenGrid kardex" data-info="' . str_replace("</div>", "", $url ).'">' . $text . '</span>';
                
                preg_match('/biblioteca=(..)/', $url, $m);
                
                if ($m[1] == $institutePrefix) {
                    $replace .='<input class="ResynchKardex" type="button" value="Riscarica" data-url="'.$url.'"/>';
                }
                
                $inventoryCollectionCopiesBE = str_replace($url, $replace, $inventoryCollectionCopiesBE);
            }

            if ($count == 0) {
                $c = $this->view->getComponentById('kardexGrid');
                $c->setAttribute('kardexParam', $url);
            }

            $count++;
        }

        return $inventoryCollectionCopiesBE;
    }

    private function enableJSTab($datas)
    {
        //TODO mettere eventualmente nel config_common
        $schemaUnimarc = array('bibliographicLevel_tab' => array('bibliographicLevel', 'documentType', 'identificationCode',
            'ISBN', 'ISSN', 'print', 'ISMN', 'otherStandardNum',
            'NBN', 'musicEditorialNumber', 'ean',"title", "edition", "numeration",
                "presentation", "publication", "location", "phisicalDescription",
                "seriesCollectionDescription", "generalNotes", "titlesNotes",
                "responsabilityNotes", "exampleNotes", "periodicityNote",
                "contentNotes", "abstract", "electronicResourceNotes"),
            'elaborationType_tab' => array("elaborationType", "language", "country", "cdMonographic",
                "cdPeriodic", "codedDataGraphic", "codedDataCartographic",
                "codedDataCartographicCar", "cdMusicPrint", "cdElaboration",
                "cdOldMaterial", "cdExpressionContent", "cdSupportType"),
            'collection_tab' => array("collection", "continuationOf", "continuationInPartOf", "continueWith", "splitIn",
                "attachedTo", "otherEditionSameSupport", "translationOf", "set",
                "subset", "analiticPartBond", "examinationBond", "otherTitleRelated"),
            'titleUniform_tab' => array("titleUniform", "titleParallel", "titleAlternative", "titleKey", "titleFictitious"),
            'subject_tab' => array("subject", "publicationLocationNormalized", "deweyClassification",
                "deweyCode", "deweyDescription"),
            'pnMainResponsability_tab' => array("pnMainResponsability", "pnAlternativeResponsability",
                "pnSecondaryResponsability", "gnMainResponsability", "gnAlternativeResponsability",
                "gnSecondaryResponsability", "pnNotAccepted", "gnNotAccepted"),
            'recordOrigin_tab' => array("inventoryCollectionCopiesBE",
                "localization","originNotes", "monographyNumber"),
            'editorialMark_tab' => array("editorialMark", "rapresentation", "interpreters", "cdUniformTitleMusic", "composition")
        );

        foreach ($datas as $key => $value) {
            foreach ($schemaUnimarc as $k => $v)
                if (in_array($key, $v)) {
                    $this->view->getComponentById($k)->setAttribute('enabled', 'true');
                    $this->view->getComponentById($k)->setAttribute('visible', 'true');
                }
        }
    }
}
