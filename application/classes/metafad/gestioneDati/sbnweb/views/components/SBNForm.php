<?php
class metafad_gestioneDati_sbnweb_views_components_SBNForm extends org_glizy_components_Component
{
  private $count = 1;
  private $arrayRepetition = array();

  function init()
  {
      parent::init();
  }

  function process()
  {
    $url = __Config::get('metafad.sbnmarc.url').'bid='.str_replace(' ','',__Request::get('BID')).'&type='.__Request::get('type');

    //Aggancio parametro biblioteca necessario per distinguere istituto di arrivo
    $instituteProxy = __ObjectFactory::createObject('metafad.usersAndPermissions.institutes.models.proxy.InstitutesProxy');
    $i = $instituteProxy->getInstituteVoByKey(__Session::get('usersAndPermissions.instituteKey'));
    if($i->institute_prefix)
    {
      $url = $url.'&biblioteca='.$i->institute_prefix;
    }
    // -- //

    if(__Request::get('version'))
    {
      $url .= '&version='.__Request::get('version');
    }
    $url = htmlspecialchars_decode($url);

    $file = file_get_contents($url);

    $file = json_decode($file);
    if(empty($file->records))
    {
      header('Location: '.$_SERVER['HTTP_REFERER'].'&error=true&errorText='.$file->message);
    }
    else
    {
      //Estraggo titolo
      // $title = $this->titleSearch($file);
      // $title = ($title === NULL) ? 'Senza titolo' : $title;
      $linkToSBNmarc = __Link::makeUrl('actionsMVC',array('pageId'=>'metafad.sbn.modules.sbnunimarc','action'=>'show','id'=>__Request::get('BID')));

      $schedaCollegata = '<div class="title-schedasbn">Scheda SBN: <a target="_blank" href="'.$linkToSBNmarc.'">'.__Request::get('BID').'</a></div>';

      $hiddenBID = '<input class="hide" id="BID" type="text" value="'.__Request::get('BID').'"/>';
      //$warning = '<div class="SBN-warning"><i style="margin-right:10px;" class="fa fa-exclamation-triangle"></i>Il campo della scheda è compilando. Importando i dati il valore sarà sovrascritto con il valore OPAC</div>';
      $buttons = $hiddenBID.
                 '<div class="formButtons formButtonsSBN">
                    <div class="content">
                      <input class="btn btn-flat js-back" type="button" value="Annulla" />
                      <input class="btn btn-flat btn-info js-import" type="button" value="Importa" />
                    </div>
                  </div>';

      $this->_content['schedaCollegata'] = $schedaCollegata;
      $this->_content['html'] = $this->exploreJson($file->records[0]->nodes,1) . $warning . $buttons;

    }
  }

  public function exploreJson($nodes,$level,$parent = '',$parentNum = 0)
  {
    $toPrint = '';
    if($nodes !== NULL)
    {
      foreach ($nodes as $key => $value) {
        if(array_key_exists($value->name,$this->arrayRepetition))
        {
          $this->arrayRepetition[$value->name] += 1;
          $name = $value->name.$this->arrayRepetition[$value->name];
        }
        else
        {
          $this->arrayRepetition[$value->name] = $parentNum;
          $name = $value->name.$parentNum;
        }

        //Il campo AUT si porta dietro il VID dell'autore da collegare
        $vid = ($value->vid) ? $value->vid : '';

        $classOE = ($this->count % 2 === 0) ? 'even' : 'odd';
        $toPrint .= '<fieldset data-id="'.$name.'">';
        $padding = 15 * $level;
        $classHide = ($value->value == NULL && $value->nodes == NULL) ? 'hide' : '';
        if($classHide !== 'hide')
        {
          $classColor = ($value->value === NULL) ? ' color: #159ce2;' : '';
          $toPrint .= '<div style="padding-left: '.$padding.'px; '.$classColor.'padding-top: 10px;padding-bottom: 10px;" class="row '.$classOE.' '.$classHide.'"><input data-parent="'.$parent.'" class="js-select-checkbox" id="'.$name.'" data-type="'.$value->name.'" data-n="'.$this->arrayRepetition[$value->name].'" type="checkbox"/><div class="col-md-3" style="margin-left:5px;margin-right:5px; display:inline">'.$value->name . ' - ' .__T($value->name).'</div>';
          if($vid != '')
          {
            $toPrint .= '<div id="value-'.$name.'" class="col-md-9 sbn-values" style="display:none">'.$vid.'</div>';
          }
          $this->count++;
          if($value->value === NULL)
          {
            $toPrint .= '</div>';
            $toPrint .= $this->exploreJson($value->nodes,$level+1,$name,$this->arrayRepetition[$value->name]);
          }
          else if($value->value != NULL)
          {
            $toPrint .= '<div id="value-'.$name.'" class="col-md-9 sbn-values" style="display:inline">'.$value->value.'</div></div>';
          }
          else
          {
            $toPrint .= '<div id="value-'.$name.'" style="color:red;" class="col-md-9" style="display:inline"></div></div>';
          }
        }
        $toPrint .= '</fieldset>';
      }
    }
    return $toPrint;

  }

  // public function titleSearch($file)
  // {
  //   foreach ($file->records[0]->nodes as $key => $value) {
  //     if($value->name == 'SG')
  //     {
  //       foreach ($value->nodes as $k => $v) {
  //         if($v->name == 'SGL')
  //         {
  //           foreach ($v->nodes as $k => $t) {
  //             if($t->name == 'SGLT')
  //             {
  //               return $t->value;
  //             }
  //           }
  //         }
  //       }
  //     }
  //   }
  //   return NULL;
  // }
}
