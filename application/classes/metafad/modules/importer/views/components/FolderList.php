<?php
class metafad_modules_importer_views_components_FolderList extends org_glizy_components_Input
{

  function init()
  {
    $this->defineAttribute('cssClass',		false, 	'',		COMPONENT_TYPE_STRING);
    $this->defineAttribute('emptyValue',	false, 	NULL,	COMPONENT_TYPE_STRING);
    $this->defineAttribute('label',			false, 	NULL,	COMPONENT_TYPE_STRING);
    $this->defineAttribute('title',			false, 	NULL,	COMPONENT_TYPE_STRING);
    $this->defineAttribute('rows',			false, 	1,		COMPONENT_TYPE_INTEGER);
    $this->defineAttribute('required',			false, 	false,	COMPONENT_TYPE_BOOLEAN);
    $this->defineAttribute('requiredMessage',	false, 	NULL,	COMPONENT_TYPE_STRING);
    $this->defineAttribute('wrapLabel',		false, 	false,	COMPONENT_TYPE_BOOLEAN);
    $this->defineAttribute('pathFromConfig',		true, 	NULL,	COMPONENT_TYPE_STRING);
    $this->defineAttribute('moduleRef',		false, 	NULL,	COMPONENT_TYPE_STRING);

    parent::init();
  }

  function process()
  {
    $this->_content = $this->_parent->loadContent($this->getId(), $this->getAttribute('bindTo'));
  }

  function modulesExist(){
      $aSBN = $this->getAttribute('moduleRef');
      return (!$aSBN || __Modules::getModule($aSBN));
  }

  function render()
  {
    if (!$this->modulesExist()){
      return;
    }

    $attributes 				= array();
    $attributes['id'] 			= $this->getId();
    $attributes['name'] 		= $this->getOriginalId();
    $attributes['class'] 		= $this->getAttribute('required') ? 'required' : '';
    $attributes['class'] 		.= $this->getAttribute( 'cssClass' ) != '' ? ( $attributes['class'] != '' ? ' ' : '' ).$this->getAttribute( 'cssClass' ) : '';
    $attributes['title'] 		= $this->getAttributeString('title');
    $attributes['onchange'] 		= $this->getAttribute('onChange');

    if ( $this->getAttribute('rows')>1)
    {
      $attributes['size'] 		= $this->getAttribute('rows');
    }

    $output = '<div id="div_'.$this->getId().'" class="form-group '.$attributes['class'].'">';
    $output .= '<label for="'.$this->getId().'" class="col-sm-2 control-label required">'.$this->getAttribute('label').'</label>';
    $output .= '<div class="col-sm-10">';
    $pathFromConf = $this->getAttribute('pathFromConfig');
    $folderPath = __Config::get($pathFromConf);

    if (is_dir($folderPath)){
      $fileList = scandir(__Config::get($pathFromConf));
      $output .= '<select id="'.$this->getId().'" name="'.$this->getId().'" class="form-control"><option value=""></option>';
      foreach($fileList as $item) {
        if($item != '.' && $item != '..' && is_dir(__Config::get($pathFromConf).'/'.$item)) {
          $output .= '<option value="'.glz_encodeOutput($item).'">'.glz_encodeOutput($item).'</option>';
        }
      }
      $output .= "</select>";
    } else {
      $output .= '<select id="'.$this->getId().'" name="'.$this->getId().'" class="form-control"><option value=""></option>';
      $output .= "</select>";
      $output .= "<div>Impostare correttamente le configurazioni per il valore pathFromConfig. Valore attuale = $folderPath.</div>";
    }

    $output .= '</div></div>';

    $this->addOutputCode($output);
  }

}
