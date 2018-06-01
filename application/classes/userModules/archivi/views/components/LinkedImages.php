<?php
// TODO esistono 3 LinkedImages uno per modulo fare refactoring per migliorare questa cosa
class archivi_views_components_LinkedImages extends org_glizy_components_Component
{
    function init()
    {
        // define the custom attributes
        $this->defineAttribute('model', false, '', COMPONENT_TYPE_STRING);
        parent::init();
    }

    function render()
    {
        $id = __Request::get('id') ?: __Session::get('idLinkedImages');
        $imagesDataArray = array();
        $model = $this->getAttribute('model');
        $media = array();

        if($model)
        {
            $status = __Request::get('action') == 'edit' ? 'PUBLISHED' : 'DRAFT';
            
            $ar = org_glizy_objectFactory::createModel($model);
            $ar->load($id, $status);

            $size = 0;
            if($ar->mediaCollegati)
            {
                $size = 1;
                $media[] = json_decode($ar->mediaCollegati);
            }
            if($ar->linkedStruMag)
            {
                $strumag = __ObjectFactory::createModel('metafad.teca.STRUMAG.models.Model');
                $strumag->load($ar->linkedStruMag['id']);
                $images = json_decode($strumag->physicalSTRU)->image;
                if($images)
                {
                    foreach ($images as $i) {
                        $media[] = $i;
                        $size++;
                    }
                }
            }
            if($media)
            {
                $instituteKey = $ar->instituteKey ? : metafad_usersAndPermissions_Common::getInstituteKey();
                $dam = __ObjectFactory::createObject('metafad.teca.DAM.services.ImportMedia', $instituteKey);

                $count = 0;
                $output = '<div id="linkedImageContainer" style="display:none"><div id="linkedImages">';
                foreach ($media as $m) {
                    $src = $dam->streamUrl($m->id,'thumbnail');

                    if($src == null)
                    {
                        continue;
                    }

                    $orig = $dam->streamUrl($m->id,'original');

                    $classNavigator = ($size === 1) ? 'hide':'';
                    if($count === 0 && $orig != NULL)
                    {
                        $count++;
                        $output .= '<div>
                            <a href="" id="js-image-prev" class="image-navigate '.$classNavigator.'" data-next="'.($size - 1).'">
                                <i class="images-nav fa fa-angle-double-left" aria-hidden="true"></i>
                            </a>
                            </div>
                            <img id="js-linked-img" src="'.$orig.'"/>
                            <div>
                            <div class="commands">
                                <a href="" class="image-close">
                                <i class="commands-action fa fa-times" aria-hidden="true"></i>
                                </a>
                                <a id="js-lightbox-image-a" href="'.$orig.'" class="js-lightbox-image">
                                <i class="commands-action fa fa-eye" aria-hidden="true"></i>
                                </a>
                                <i class="hide commands-action fa fa-search-plus" aria-hidden="true"></i>
                                <i class="hide commands-action fa fa-th" aria-hidden="true"></i>
                                <a href="" id="js-image-next" class="image-navigate '.$classNavigator.'" data-next="1">
                                    <i class="images-nav-right fa fa-angle-double-right" aria-hidden="true"></i>
                                </a>
                            </div>
                            </div>
                            <div id="image-pagination">
                            1 / '.($size).'
                            </div>';
                    }
                    $imagesDataArray[] = array("src"=>$src,"didascalia"=>'',"orig"=>$orig);
                }
                
                if($size == 0 || $count == 0)
                {
                    $output = str_replace('<div id="linkedImages">','',$output);
                    $output .= '<div class="no-image-message">
              <i class="fa fa-exclamation-triangle" aria-hidden="true"></i>
              Nessuna immagine collegata</div></div>';
                }
                else {
                    $output .= '</div></div>';
                }

                $output .= '<script>
                        var imagesData = new Array(';
                if($imagesDataArray)
                {
                    foreach ($imagesDataArray as $value) {
                        if($value['src'] != null )
                            $output .= 'new Array("'.$value['src'].'","'.$value['didascalia'].'","'.$value['orig'].'"),';
                    }
                }
                $output = rtrim($output,",");
                $output .= ');</script>';
            }
            else
            {
                $output = '<div id="linkedImageContainer" style="display:none">
                          <div class="no-image-message">
                          <i class="fa fa-exclamation-triangle" aria-hidden="true"></i>
                          Nessuna immagine collegata</div>
                       </div>';
            }
        }
        $this->addOutputCode($output);
    }

    function process()
    {
        $this->_application->addLightboxJsCode();
    }
}