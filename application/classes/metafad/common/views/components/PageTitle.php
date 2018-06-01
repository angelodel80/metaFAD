<?php

class metafad_common_views_components_PageTitle extends org_glizy_components_PageTitle
{
    protected $parentId;
    protected $icon;

    function process()
    {
        if (is_null($this->getAttribute('value'))) {
            $arrayIcons = array(
                'processi' => 'fa fa-gears',
                'utenti-e-permessi' => 'fa fa-users',
                'teca' => 'fa fa-th',
                'teca-mag' => 'fa fa-th',
                'mets' => 'fa fa-th',
                'opac' => 'fa fa-folder-o',
                'ecommerce' => 'fa fa-shopping-cart',
                'impostazioni-sistema' => 'fa fa-gear',
                'export/patrimonio' => 'fa fa-gear',
                'export' => 'fa fa-gear',
            );
            $menuId = $this->getAttribute('menuId');
            $siteMap = &$this->_application->getSiteMap();
            $this->currentMenu = is_null($menuId) ? $this->_application->getCurrentMenu() : $siteMap->getNodeById($menuId);
            if (is_null($this->getAttribute('menuDepth'))) {
                if (array_key_exists($this->currentMenu->parentId, $arrayIcons)) {
                    $this->icon = $arrayIcons[$this->currentMenu->parentId];
                    $this->parentId = $this->currentMenu->parentId;
                } else if (strpos($this->currentMenu->parentId, 'gestione-dati') !== false) {
                    $this->parentId = $this->currentMenu->parentId;
                    $this->icon = 'fa fa-archive';
                } else if ($this->currentMenu->title == 'Dashboard') {
                    $this->parentId = null;
                    $this->icon = 'fa fa-home';
                }
                
                $this->_content = $this->currentMenu->title;
                //var_dump($_SERVER['REQUEST_URI']);
                //var_dump($this->getAjaxUrl());
            } else {
                $this->currentMenu = &$this->currentMenu->parentNodeByDepth($this->getAttribute('menuDepth'));
                $this->_content = $this->currentMenu->title;
            }
        } else {
            $this->_content = glz_encodeOutput($this->getAttribute('value'));
        }

        if ($this->getAttribute('setSiteMapTitle')) {
            $menu = $this->_application->getCurrentMenu();
            $menu->title = html_entity_decode($this->_content);
        }
    }

    function render_html()
    {
        if (!empty($this->_content)) {
            $tag = $this->getAttribute('tag');
            $cssClass = $this->getAttribute('cssClass');
            $hierarchy = //Titoli significativi estratti, tolto un doppione (mi esclude la home così!)
                array_slice(
                    array_filter(
                        array_map(
                            function ($a) {
                                return $a->title;
                            },
                            $this->getHierarchyNodes(false)
                        ),
                        function ($a) {
                            return $a;
                        }
                    ),
                    0, -1
                );

            $output = $this->generateMenuTitle($tag, $cssClass, $hierarchy);

            $this->addOutputCode($output);
        }
    }

    /**
     * @param bool $asc (Default TRUE) per avere la gerarchia ordinata da foglia a radice
     * @return array insieme di nodi della SiteMap
     */
    private function getHierarchyNodes($asc = true)
    {
        $cur = $this->currentMenu;
        $siteMap = &$this->_application->getSiteMap();
        $ret = array();

        do {
            $ret[] = $cur;
        } while ($cur != null && $cur = $siteMap->getNodeById($cur->parentId)); //Salgo di gerarchia

        if ($cur != null) {
            $ret[] = $cur;
        }

        return $asc !== false ? $ret : array_reverse($ret);
    }

    /**
     * @param $tag
     * @param $cssClass
     * @param $hierarchy
     * @return string
     */
    private function generateMenuTitle($tag, $cssClass, $hierarchy)
    {
        $output = '';
        $output .= '<' . $tag . ($cssClass ? ' class="' . $cssClass . '"' : '') . '>';
        if ($this->getAttribute('drawIcon') && $this->icon) {
            $output .= '<i class="' . $this->icon . '"></i> ';
        }

        foreach ($hierarchy as $node) {
            if ($node == 'Gestione Dati Bibliografico') {
                $node = 'Gestione Dati <i class="fa fa-angle-right"></i> Bibliografico';
            }
            $output .= $node . ' <i class="fa fa-angle-right"></i> ';
        }

        $azione = strtolower(__Request::get("action"));
        if ($azione != "index") {
            $output .= '<a href="' . __Link::makeURL("link") . '">' . $this->_content . ' </a> <i class="fa fa-angle-right"></i> ' . $this->translateAction($azione) . "</$tag>";
        } else {
            $output .= $this->_content . '</' . $tag . '>';
        }
        if ($this->getAttribute('wrap')) {
            $wrapCssClass = $this->getAttribute('wrapCssClass');
            $output = '<div' . ($wrapCssClass ? ' class="' . $wrapCssClass . '"' : '') . '>' . $output . '</div>';
            return $output;
        }
        return $output;
    }

    private function translateAction($act)
    {
        $string = strtolower($act);
        if ($string == 'edit') {
            $recordId = $this->getRecordId("Modifica");
            if($recordId)
            {
                return $recordId;
            }
            return "Modifica";
        }
        else if ($string == 'editpersonal') {
            return "Modifica dati";
        } else if ($string == 'show') {
            $recordId = $this->getRecordId("Mostra");
            if ($recordId) {
                return $recordId;
            }
            return "Mostra";
        } else if ($string == 'detail') {
            return "Dettaglio";
        } else if ($string == 'editdraft') {
            return "Modifica Bozza";
        } else if ($string == 'listdetail') {
            return "Lista figli";
        } else {
            return ucfirst($string);
        }
    }

    private function getRecordId($label)
    {
        $id = __Request::get('id');
        $model = __Request::get('model');

        //CASO PARTICOLARE SBN
        if($model == 'metafad.sbn.modules.sbnunimarc.model.Model' || $model == 'metafad.sbn.modules.authoritySBN.model.Model')
        {
            return $label . ': ' . $id;
        }

        if ($id && $model) {
            $ar = __ObjectFactory::createModel($model);
            if ($ar->load($id)) {
                if (method_exists($ar, 'getRecordId')) {
                    $recordId = $ar->getRecordId();
                    if ($recordId) {
                        return $label .': '. $recordId;
                    }
                }
            }
        }   
        return null;         
    }

}
