<?php

class metafad_sbn_modules_authoritySBN_controllers_Show extends org_glizycms_contents_controllers_moduleEdit_Edit
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

            $ar = $it->where('id', $id)->first();

            if($ar){
                $objData = $ar->getRawData();
                $data = json_decode(json_encode($objData), true);
            }
            //*************************************

            $data['__id'] = $id;

            $this->enableJSTab($data);
            foreach ($data as $key => $value) {
                if ($this->view->getComponentById($key)) {
                    if ($preBid = strstr($value[0], 'BID=')) {
                        $str = substr($preBid, 0, strpos($preBid, '">'));
                        $bid = str_replace('BID=', '', $str);
                        $url = __Link::makeURL( 'actionsMVC',
                            array(
                                'pageId' => 'metafad.sbn.unimarcSBN_popup',
                                'title' => __T('GLZ_RECORD_EDIT'),
                                'action' => 'show','id' => $bid));
                        $data[$key][0] = str_replace('href="${page}', 'class="rif" data-url="' . $url, $data[$key][0]);
                        $data[$key][0] = str_replace('?BID=' . $bid . '">', '">', $data[$key][0]);
                    } else if ($preVid = strstr($value[0], 'VID=')) {
                        $str = substr($preVid, 0, strpos($preVid, '">'));
                        $vid = str_replace('VID=', '', $str);
                        $url = __Link::makeURL( 'actionsMVC',
                            array(
                                'pageId' => 'metafad.sbn.modules.authoritySBN_popup',
                                'title' => __T('GLZ_RECORD_EDIT'),
                                'action' => 'show','id' => $vid));
                        $data[$key][0] = str_replace('href="${page}', 'class="rif" data-url="' . $url, $data[$key][0]);
                        $data[$key][0] = str_replace('?VID=' . $vid . '">', '">', $data[$key][0]);
                    }
                    $this->view->getComponentById($key)->setAttribute('enabled', 'true');
                }
            }
            $this->view->getComponentById('editFormAuthority')->setData($data);
        }
    }

    private function enableJSTab($datas)
    {

        $schemaUnimarc = array('identification_tab'=> array('idVersion', 'ISADN', 'elaborationData',
            'language', 'nationality', 'catalogingRules', "personalName", "groupName", "dating", "informativeNote", "sourceBibliographyPositive", "sourceBibliographyNegative"),
            'identificationQualification_tab' => array('idVersion', 'ISADN', 'elaborationData',
            'language', 'nationality', 'catalogingRules'),
            'headerDescription_tab' => array("personalName", "groupName", "dating", "informativeNote", "sourceBibliographyPositive", "sourceBibliographyNegative"),
            'relation_tab' => array("seeAlsoAuthor", "seeAlsoGroup", "sourceRecord", "variantForms","vediAnche"),
            'referralReports_tab' => array("sourceRecord", "variantForms"),
            'seeAlso_tab' => array("seeAlsoAuthor", "seeAlsoGroup", "vediAnche"),
            'note_tab' => array("cataloguerNotes"),
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
