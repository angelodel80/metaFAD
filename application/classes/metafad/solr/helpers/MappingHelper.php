<?php
class metafad_solr_helpers_MappingHelper extends GlizyObject
{
    private $lastElementField;

    public function setValues($value,&$array,$allSubFields = null)
    {
      if(is_object($value))
      {
        $value = (array)$value;
      }
      if(is_string($value) && $value != null)
      {
        $array[] = $value;
      }
      else if(is_array($value))
      {
        if($value[0])
        {
          foreach ($value as $val) {
            $keys = array_keys((array)$val);
            if($keys)
            foreach ($keys as $keyVal) {
              if($val->$keyVal)
              {
                if(is_string($val->$keyVal))
                {
                  $array[] = $val->$keyVal;
                }
                else
                {
                  $this->setValues($val->$keyVal, $array, $allSubFields);
                }
              }
            }
          }
        }
      }
    }

    public function setValuesString($value,&$array,$allSubFields = null)
    {
      if(is_object($value))
      {
        $value = (array)$value;
      }
      if(is_string($value) && $value != null)
      {
        $array .= $value . ' # ';
      }
      else if(is_array($value))
      {
        if($value[0])
        {
          foreach ($value as $val) {
            $keys = array_keys((array)$val);
            if($keys)
            foreach ($keys as $keyVal) {
              if($val->$keyVal)
              {
                if(is_string($val->$keyVal))
                {
                  $array .= $val->$keyVal . ' # ';
                }
                else
                {
                  $this->setValuesString($val->$keyVal, $array, $allSubFields);
                }
              }
            }
          }
        }
        //caso particolare AUT, va ricostruito l'insieme dei valori
        else if($value['id']){
          $record = org_glizy_objectFactory::createObject('org.glizy.dataAccessDoctrine.ActiveRecordDocument');
          if($record->load($value['id'])){
            foreach ($record->getRawData() as $key => $value) {
              if(in_array($key,$allSubFields))
              {
                $array .= $value .' # ';
              }
            }
          }
        }
      }
    }

    public function getChildrenFlat($element)
    {
      $array = array();
      foreach ($element->children as $child) {
        if($child->children)
        {
          $array[] = $child->name;
          $array[] = $this->getChildren($child);
        }
        else
        {
          $array[] = $child->name;
        }
      }
      return $array;
    }

    public function getChildren($element)
    {
      $array = array();
      foreach ($element->children as $child) {
        if($child->children)
        {
          $array[$child->name] = $this->getChildren($child);
        }
        else
        {
          $array[$child->name] = array();
        }
      }
      return $array;
    }

	public function translateLabel($label,$array,$otherTranslation = null)
	{
		if(array_key_exists($label,$array))
		{
			return $array[$label];
		}
		else if(array_key_exists($label,$otherTranslation))
		{
			return $otherTranslation[$label];
		}
		else
		{
			return __T($label);
		}
	}
}
