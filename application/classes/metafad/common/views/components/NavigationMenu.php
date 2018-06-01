<?php
class metafad_common_views_components_NavigationMenu extends org_glizy_components_NavigationMenu
{
    function _render_html(&$menu, &$output)
    {
        $attributes = array();
        $attributes['class'] = $this->getAttribute('cssClass');

        if (empty($output))
        {
            $attributes['id'] = $this->getId();
            $attributes['class'] = 'sidebar-menu';
            $attributes['title'] = $this->getAttributeString('title');
            $output .=  org_glizy_helpers_Html::renderTag('ul', $attributes, false);
        }
        else
        {
            $attributes['class'] = 'treeview-menu';
            $output .=  org_glizy_helpers_Html::renderTag('ul', $attributes, false);
        }

        for($i=0; $i<count($menu); $i++)
        {
            $menu[$i]['node'] = str_replace($menu[$i]['title'].'</a>','<span>'.$menu[$i]['title'].'</span></a>',$menu[$i]['node']);
            if (is_array($menu[$i]['node']))
            {
                $this->_render_html($menu[$i]['node'], $output);
                $output .= '</li>';
            }
            else
            {
                // <a href="http://localhost/ctsg_poloDigitaleNapoli/gestione-dati" title="Gestione dati"><i class="fa fa-university"></i> Gestione dati</a>
                if ($menu[$i]['haveChild'] ) {
                    $menu[$i]['cssClass'] .= ' treeview ';
                    $menu[$i]['node'] = preg_replace('/href="([^"]*)"/', 'href="noTranslate:#"', $menu[$i]['node']);
                    if (__Config::get('metafad.be.hasEcommerce') == 'true') {
                        $menu[$i]['node'] = str_replace('</a>', '<i class="fa fa-angle-left pull-right"></i>'.($menu[$i]['title']=="Ecommerce" && metafad_ecommerce_helpers_Ecommerce::countReq()!=0 ? ' <div class="e-request bg-lava text-white"><span id="countReq">'.metafad_ecommerce_helpers_Ecommerce::countReq().'</span></div>' :'').'</a>', $menu[$i]['node']);
                    }
                }
                $cssClass = trim($menu[$i]['cssClass']);
                $selected = $menu[$i]['selected'];
                if ($cssClass) {
                    if ( strpos($selected, 'class="')!==false) {
                        $selected = str_replace('class="', 'class="'.$cssClass.' ', $selected);
                        $cssClass = '';
                    }
                    else
                    {
                        $cssClass = 'class="'.$cssClass.'"';
                    }
                }
                $attributes = trim($cssClass.' '.$selected);
                $output .= '<li'.($attributes ? ' '.$attributes : '').'>'.$menu[$i]['node'].($i+1<count($menu) && is_array($menu[$i+1]['node']) ? '' : '</li>');
            }
        }
        $output .= '</ul>';
    }
}
