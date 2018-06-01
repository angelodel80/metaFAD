<?php
class metafad_solr_helpers_FeHelper extends GlizyObject
{
    private $lastElementField;

    public function setValues($value, & $array, $allSubFields = null) {
        if (is_object($value)) {
            $value = (array) $value;
        }
        if (is_string($value) && $value != null) {
            $array[] = $value;
        } else if (is_array($value)) {
            if ($value[0]) {
                foreach($value as $val) {
                    $keys = array_keys((array) $val);
                    if ($keys)
                        foreach($keys as $keyVal) {
                            if ($val->$keyVal) {
                                if (is_string($val->$keyVal)) {
                                    $array[] = $val->$keyVal;
                                } else {
                                    $this->setValues($val->$keyVal, $array, $allSubFields);
                                }
                            }
                        }
                }
            }
        }
    }

    public function setValuesString($value, &$array, $allSubFields = null) {
        if (is_object($value)) {
            $value = (array) $value;
        }
        if (is_string($value) && $value != null) {
            $array .= $value.' # ';
        } else if (is_array($value)) {
            if ($value[0]) {
                foreach($value as $val) {
                    $keys = array_keys((array) $val);
                    if ($keys)
                        foreach($keys as $keyVal) {
                            if ($val->$keyVal) {
                                if (is_string($val->$keyVal)) {
                                    $array -= $val->$keyVal.' # ';
                                } else {
                                    $this->setValuesString($val->$keyVal, $array, $allSubFields);
                                }
                            }
                        }
                }
            }
            //caso particolare AUT, va ricostruito l'insieme dei valori
            else if ($value['id']) {
                $record = org_glizy_objectFactory::createObject('org.glizy.dataAccessDoctrine.ActiveRecordDocument');
                if ($record->load($value['id'])) {
                    foreach($record->getRawData() as $key => $value) {
                        if (in_array($key, $allSubFields)) {
                            $array .= $value.' # ';
                        }
                    }
                }
            }
        }
    }

    public function getChildrenFlat($element) {
        $array = array();
        foreach($element->children as $child) {
            if ($child->children) {
                $array[] = $child->name;
                $array[] = $this->getChildren($child);
            } else {
                $array[] = $child->name;
            }
        }
        return $array;
    }

    public function getChildren($element) {
        $array = array();
        foreach($element->children as $child) {
            if ($child->children) {
                $array[$child->name] = $this->getChildren($child);
            } else {
                $array[$child->name] = array();
            }
        }
        return $array;
    }

    public function getHtmlNoChildren($data, $name) {
        $html = '';
        if ($data) {
            $elementName = $name.'-element';
            //$html .= '<div><div class="label">'.__T($name).'</div>';
            $html .= '<div>';
            foreach($data as $value) {
                if (is_string($value->$elementName)) {
                    $html.= '<div class="value">'.$value->$elementName.'</div>';
                }
            }
            $html.= '</div>';
        }
        return $html;
    }

    public function getComplexHtml($data, $element) {
        $allSubFields = array();
        $structure = array();
        $allSubFields[$element->name] = array();

        foreach($element->children as $child) {
            $allSubFields[] = $child->name;
            if ($child->children) {
                $structure[$element->name][$child->name] = $this->getChildren($child);
                foreach($this->getChildrenFlat($child) as $v) {
                    $allSubFields[] = $v;
                }
            } else {
                $structure[$element->name][$child->name] = array();
            }
        }

        $html = '';
        if ($data) {
            foreach($data as $d) {
                $html.= $this->getHtmlFromStructure($d, $structure, $allSubFields, true);
            }
        }
        return $html;
    }

    public function getHtmlFromStructure($data, $structure, $allSubFields = null, $complex = false) {
		$html = '<div class="group-value">';
        //In questo caso abbiamo la struttura di un ripetibile complesso
        foreach($structure as $key => $value) {
            if ($data->$key) {
                //Estraggo i valori
                $d = $data->$key;

                if (strpos($key, '-element') === false) {
                    $html.= '<div class="label">'.__T($key).'</div>';
                }
                if (is_string($d) && $d) {
                    if ($key == 'AUTN') {
                        $query = urlencode('{"query":{"clause":{"type":"SimpleClause","operator":{"operator":"AND"},"field":"Tutto","innerOperator":{"operator":"contains one"},"values":["*"]},"start":0,"rows":10,"facetLimit":100,"facetMinimum":1,"filters":[{"type":"SimpleClause","operator":{"operator":"AND"},"field":"autoreruolo_html_nxtxt","innerOperator":{"operator":"AND"},"values":["'.$data->__AUT->id.'"]}],"facets":null,"orderClauses":null,"fq":null,"fieldNamesAreNative":false}}');
                        $html.= '<div class="value">'.
                                '  <a target="_blank" href="{searchAuthorICCD}'.$query.'">'.$d.'</a>'.
                                '  <a target="_blank" href="{linkToAuthorICCD}'.$data->__AUT->id.'">${button}</a>'.
                                '  <a class="js-openhere" data-modal="{linkToAuthorICCDPopup}'.$data->__AUT->id.'">(i)</a>'.
                                '</div>';
                    } else {
                        $html.= '<div class="value">'.$d.'</div>';
                    }
                } else {
                    $htmlAppoggio = '';
                    foreach($d as $k => $v) {
                        $htmlAppoggio.= '<div>';
                        if (is_string($v) || is_int($v)) {
                            continue;
                        }
                        foreach($v as $field => $val) {
                            if (is_string($val) && $val) {
                                $htmlAppoggio.= $this->getHtmlLabelValue($field, $val);
                            } else if ($val) {
                                if (!is_array($val)) {
                                    //caso particolare autore
                                    $record = org_glizy_objectFactory::createObject('org.glizy.dataAccessDoctrine.ActiveRecordDocument');
                                    if ($record->load($val->id)) {
                                        foreach($record->getRawData() as $kk => $vv) {
                                            if (in_array($kk, $allSubFields)) {
                                                $htmlAppoggio.= $this->getHtmlLabelValue($kk, $vv);
                                            }
                                        }
                                    }
                                } else {
                                    foreach($val as $elKey => $el) {
                                        foreach($el as $x => $y) {
                                            if (!empty($y) && $y) {
                                                $htmlAppoggio.= $this->getHtmlLabelValue($x, $y);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        $htmlAppoggio.= '</div>';
                        if ($htmlAppoggio == '<div></div>') {
                            $html = str_replace('<div class="label">'.__T($key).'</div>', '', $html);
                            $htmlAppoggio = '';
                        }
						$html .= $htmlAppoggio;
						$htmlAppoggio = '';
                    }

                }
            } else {
                //Ottengo l'html della struttura figlia
                $d = $data->$key;
                $htmlAppoggio = $this->getHtmlFromStructure($data, $value, $allSubFields);
                if ($htmlAppoggio != '<div class="group-value"></div>' && $htmlAppoggio != '') {
                    if (!$complex) {
                        $html.= '<div class="label">'.__T($key).'</div>'.$htmlAppoggio;
                    } else {
                        $html.= $htmlAppoggio;
                    }
                }
            }
        }

        $html.= '</div>';
        if ($html == '<div class="group-value"></div>') {
            return '';
        } else {
            return $html;
        }
    }

    public function getHtmlFromStructureFiltered($data, $listSubFields) {
        $html = '<div>';
        foreach($data as $d) {
            foreach($d as $key => $value) {
                if (in_array($key, $listSubFields)) {
                    if (is_string($value) && $value) {
                        $html.= $this->getHtmlLabelValue($key, $value);
                    } else if (is_array($value)) {
                        foreach($value as $val) {
                            foreach($val as $k => $v) {
                                if (!empty($v) && $v) {
                                    $html.= $this->getHtmlLabelValue($k, $v);
                                }
                            }
                        }
                    }
                }
            }
        }
        $html.= '</div>';
        return $html;
    }

    public function getHtmlLabelValue($label, $value) {
        $html = '';
        if (strpos($label, '-element') === false) {
            $html.= '<div class="label">'.__T($label).'</div>';
        } else if (strpos($label, '-element') !== false && $label != $this->lastElementField) {
            $html.= '<div class="label">'.__T(str_replace('-element', '', $label)).'</div>';
        }
        $html.= '<div class="value">'.$value.'</div>';

        if (strpos($label, '-element') !== false) {
            $this->lastElementField = $label;
        }
        return $html;
    }

    public function detailMapping($data) {
        $doc = new stdClass();

        //Mapping per scheda di dettaglio
        $classPath = str_replace('.', '/', $data->__model);
        $classPath = str_replace('Model', 'elements.json', $classPath);
        $modulePath = __Paths::get('APPLICATION_TO_ADMIN').
        'classes/userModules/'.$classPath;

        if ($data->__model == 'AUT300.models.Model') {
            $doc->versione_scheda_s = '3.00';
        } else if ($data->__model == 'AUT400.models.Model') {
            $doc->versione_scheda_s = '4.00';
        }

        $elements = json_decode(file_get_contents($modulePath));
        foreach($elements as $key => $value) {
            $labelPrefix = $value->name;
            $prName = $labelPrefix;
            //Se è già primo livello ne creo la struttura html
            if ($data->$prName) {
                $thisLabel = strtolower($labelPrefix.'_html_nxt');
                $elementChildren = $this->getChildren($value);
                if (is_array($data->$prName)) {
                    $doc->$thisLabel = $this->getComplexHtml($data->$prName, $value);
                } else {
                    $doc->$thisLabel = $this->getHtmlFromStructure($data->$prName, $elementChildren, $value);
                }
            } //Altrimenti devo scendere al secondo livello
            else {
                foreach($value->children as $child) {
                    $name = $child->name;
                    $val = $data->$name;
                    $childLabel = strtolower($labelPrefix.
                        '_'.$name.
                        '_html_nxt');
                    //Caso specifico autore
                    if ($name == 'AUT') {
                        $elementChildren = $this->getChildren($child);
                        $childLabel.= 'xt';
                        $record = org_glizy_objectFactory::createObject('org.glizy.dataAccessDoctrine.ActiveRecordDocument');
                        if ($val) {
                            foreach($val as $authorKey => $author) {
                                if ($record->load($author->__AUT->id)) {
                                    if (!is_array($doc->autoreruolo_html_nxtxt)) {
                                        $doc->autoreruolo_html_nxtxt = array();
                                    }
                                    $role = ($author->AUTR) ? '['.$author->AUTR.']': '';
                                    array_push($doc->autoreruolo_html_nxtxt, $author->__AUT->id.$role);
                                    foreach($record->getRawData() as $k => $v) {
                                        if (array_key_exists($k, $elementChildren)) {
                                            $val[$authorKey]->$k = $v;
                                        }
                                    }
                                }
                            }
                            foreach($val as $authorKey => $author) {
                                $doc->$childLabel.= $this->getHtmlFromStructure($author, $elementChildren);
                            }
                        }
                    }
                    //Caso specifico BIB
                    else if ($name == 'BIB') {
                        $elementChildren = $this->getChildren($child);
                        $childLabel.= 'xt';
                        $record = org_glizy_objectFactory::createObject('org.glizy.dataAccessDoctrine.ActiveRecordDocument');
                        if ($val) {
                            foreach($val as $bibKey => $bib) {
                                if ($record->load($bib->__BIB->id)) {
                                    foreach($record->getRawData() as $k => $v) {
                                        if (array_key_exists($k, $elementChildren)) {
                                            $val[$bibKey]->$k = $v;
                                        }
                                    }
                                }
                            }
                            foreach($val as $bibKey => $bib) {
                                $doc->$childLabel.= $this->getHtmlFromStructure($bib, $elementChildren);
                            }
                        }
                    } //Non ripetibile, valore stringa
                    else if ($child->maxOccurs == 1 && is_string($val)) {
                        $doc->$childLabel = $val;
                    } //Non ripetibile, valore array
                    else if ($child->maxOccurs == 1 && is_array($val)) {
                        $elementChildren = $this->getChildren($child);
                        foreach($val as $oKey => $o) {
                            $doc->$childLabel.= $this->getHtmlFromStructure($o, $elementChildren);
                        }
                    } //Ripetibile
                    else if ($child->maxOccurs == 'unbounded') {
                        $childLabel = str_replace('_html_nxt', '_html_nxtxt', $childLabel);
                        //Con figli
                        if ($child->children) {
                            if ($val) {
                                $elementChildren = $this->getChildren($child);
                                foreach($val as $oKey => $o) {
                                    $doc->$childLabel.= $this->getHtmlFromStructure($o, $elementChildren);
                                }
                            }
                        } //Senza figli
                        else {
                            $doc->$childLabel = $this->getHtmlNoChildren($val, $name);
                        }
                    }

                    if ($child->children) {
                        foreach($child->children as $c) {
                            $name = $c->name;
                            $val = $data->$name;
                            $childLabel = strtolower($labelPrefix.'_'.$name.'_html_nxt');
                            //Caso specifico autore
                            if ($name == 'AUT') {
                                $elementChildren = $this->getChildren($c);
                                $childLabel.= 'xt';
                                $record = org_glizy_objectFactory::createObject('org.glizy.dataAccessDoctrine.ActiveRecordDocument');
                                foreach($val as $authorKey => $author) {
                                    if ($record->load($author->__AUT->id)) {
                                        foreach($record->getRawData() as $k => $v) {
                                            if (array_key_exists($k, $elementChildren)) {
                                                $val[$authorKey]->$k = $v;
                                            }
                                        }
                                    }
                                }

                                foreach($val as $authorKey => $author) {
                                    $doc->$childLabel.= $this->getHtmlFromStructure($author, $elementChildren);
                                }

                            } //Non ripetibile, valore stringa
                            else if ($c->maxOccurs == 1 && is_string($val)) {
                                $doc->$childLabel = $val;
                            } //Non ripetibile, valore array
                            else if ($c->maxOccurs == 1 && is_array($val)) {
                                $elementChildren = $this->getChildren($c);
                                foreach($val as $oKey => $o) {
                                    $doc->$childLabel.= $this->getHtmlFromStructure($o, $elementChildren);
                                }
                            } //Ripetibile
                            else if ($c->maxOccurs == 'unbounded') {
                                //Con figli
                                if ($c->children) {
                                    if ($val) {
                                        $elementChildren = $this->getChildren($c);
                                        foreach($val as $oKey => $o) {
                                            $doc->$childLabel.= $this->getHtmlFromStructure($o, $elementChildren);
                                        }
                                    }
                                } //Senza figli
                                else {
                                    $doc->$childLabel = $this->getHtmlNoChildren($val, $name);
                                }
                            }
                        }
                    }
                }
            }
        }

        //Indicizzo i campi html per la ricerca sul campo tutto
        foreach($doc as $key => $value) {
            if (!$value) {
                unset($doc->$key);
                continue;
            }
            if (strpos($key, '_html_nxtxt') !== false) {
                $label = str_replace('_html_nxtxt', '_ss', $key);
                $labelt = str_replace('_html_nxtxt', '_txt', $key);
                $doc->$label = preg_replace('#<[^>]+>#', ' ', $value);
                $doc->$labelt = $doc->$label;
            } else if (strpos($key, '_html_nxt') !== false) {
                $label = str_replace('_html_nxt', '_s', $key);
                $labelt = str_replace('_html_nxt', '_t', $key);
                $doc->$label = preg_replace('#<[^>]+>#', ' ', $value);
                $doc->$labelt = $doc->$label;
            }
        }
        return $doc;
    }

    public function searchFieldsMapping($mappingFields, $data) {
        $doc = new stdClass();

        foreach($mappingFields as $key => $value) {
            //3 possibili tipi di chiave:
            //1 - sub:Chiave, ovvero appiattire il gruppo
            //2 - chiave.subChiave, ovvero indica il percorso per ottenere il valore (solo per F)
            //3 - semplice chiave campo
            $label = str_replace(array(":", '(', ')'), '_', strtolower($key));
            $label = str_replace(' ', '_', $label);
            $labeltxt = str_replace('__', '_', $label).'_txt';
            $label = str_replace('__', '_', $label).'_ss';
            $doc->$label = array();

            //Caso particolare tipo di scheda (label)
            if ($key == 'Tipo di scheda (label)') {
                $doc->$label = __T($data->TSK);
                $doc->$labeltxt = $doc->$label;
                continue;
            }

            $structure = array();
            if (strpos($value[0], ':') === false && strpos($value[0], '.') === false && strpos($value[0], '->') === false) {
                foreach($value as $v) {
                    $structure[$v] = array();
                }
            }

            foreach($value as $v) {
                if (strpos($v, ':') !== false) {
                    $label = str_replace('_ss', '_s', $label);
                    $labeltxt = str_replace('_txt', '_t', $labeltxt);
                    $doc->$label = '';
                    //Quando si indica sub:campo, in realtà campo non ha una corrispondenza
                    //nel model, ma si può ricorstruire l'insieme dei campi che lo compone
                    //tramite il file "elements.json".
                    $f = explode(":", $v);
                    $field = $f[1];
                    $file = str_replace('.', '/', $data->__model);
                    $elements = json_decode(file_get_contents(__Paths::get('APPLICATION_TO_ADMIN').'classes/userModules/'.str_replace('Model', 'elements.json', $file)));
                    foreach($elements as $e) {
                        if ($e->name == $field) {
                            $element = $e;
                            break;
                        }
                    }
                    //Creo la struttura da utilizzare per l'estrazione dei campi
                    //e appiattisco per ottenere tutti i campi
                    $allSubFields = array();
                    $structure = array();
                    $allSubFields[] = $element->name;

                    foreach($element->children as $child) {
                        $allSubFields[] = $child->name;
                        if ($child->children) {
                            $structure[$element->name][$child->name] = $this->getChildren($child);
                            foreach($this->getChildrenFlat($child) as $v) {
                                $allSubFields[] = $v;
                            }
                        }
                    }

                    foreach($allSubFields as $fieldKey => $field) {
                        if ($data->$field) {
                            if (is_string($data->$field)) {
                                $this->setValuesString($data->$field, $doc->$label, $allSubFields);
                            } else {
                                foreach($data->$field as $d) {
                                    if (is_object($d)) {
                                        $appoggio = array($d);
                                        $this->setValuesString($appoggio, $doc->$label, $allSubFields);
                                    } else {
                                        $this->setValuesString($d, $doc->$label, $allSubFields);
                                    }
                                }
                            }
                        }
                    }
                    $doc->$label = rtrim($doc->$label, ' # ');
                } else if (strpos($v, '.') !== false) {
                    $path = explode(".", $v);
                    $fieldValue = $data->$path[0];
                    //Se si tratta di un percorso autore il campo va pescato da DB
                    if ($path[0] == 'AUT') {
                        $record = org_glizy_objectFactory::createObject('org.glizy.dataAccessDoctrine.ActiveRecordDocument');
                        if ($record->load($fieldValue[0]->__AUT->id)) {
                            foreach($record->getRawData() as $key => $value) {
                                if ($key == $path[1] && $fieldValue[0]->$path[1] == null) {
                                    $fieldValue[0]->$path[1] = $value;
                                }
                            }
                        }
                    }
                    if (!is_string($fieldValue) && $fieldValue != null) {
                        foreach($fieldValue as $f) {
                            $this->setValues($f->$path[1], $doc->$label);
                        }
                    } else if ($fieldValue != null) {
                        $this->setValues($fieldValue, $doc->$label);
                    }
                } else if (strpos($v, '->') !== false) {
                    $path = explode("->", $v);
                    $fieldValue = $data->$path[0];
                    if ($fieldValue) {
                        $fieldsToRead = explode("&", $path[2]);
                        foreach($fieldValue as $fv) {
                            foreach($fv->$path[1] as $f) {
                                foreach($fieldsToRead as $ftr) {
                                    $this->setValues($f->$ftr, $doc->$label);
                                }
                            }
                        }
                    }
                } else {
                    $this->setValues($data->$v, $doc->$label);
                }

                if ($doc->$label == null) {
                    unset($doc->$label);
                }
            }
            $doc->$labeltxt = $doc->$label;

        }

        //Campi sorting
        if ($doc->titolo_sintetica_ss) {
            $string = '';
            foreach($doc->titolo_sintetica_ss as $v) {
                $string.= $v.' ';
            }
            $doc->titolo_ordinamento_s = strtolower($string);
            $doc->titolo_ordinamento_t = $string;
        }

        if ($doc->definizione_culturale_autore_ss) {
            $string = '';
            foreach($doc->definizione_culturale_autore_ss as $v) {
                $string.= $v.' ';
            }
            $doc->autore_ordinamento_s = strtolower($string);
            $doc->autore_ordinamento_t = $string;
        }
        return $doc;
    }
}
