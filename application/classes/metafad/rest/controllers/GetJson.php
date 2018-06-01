<?php
class metafad_rest_controllers_GetJson extends org_glizy_rest_core_CommandRest
{
  function execute($type)
  {
	  if(__Request::get('module') == 'archive')
	  {
		  echo file_get_contents('application/classes/userModules/archivi/json/'.$type.'.json');
	  }
	  else
	  {
		  echo file_get_contents('application/classes/userModules/'.$type.'/models/elements.json');
	  }
	  exit;
  }
}
