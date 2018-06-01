<?php
class metafad_modules_importer_iccd_services_immftanFromXml implements metafad_modules_importer_iccd_services_ImmftanInterface
{
    private $values;

    public function __construct($file)
    {
        $this->read($file);
    }


    private function getSomePossibilities($string){
        return array(
            $string,
            strtolower($string),
            strtoupper($string),
            ucfirst(strtolower($string))
        );
    }

    private function tryOtherNames($fullname){
        $dirname = pathinfo($fullname, PATHINFO_DIRNAME);
        $filename = pathinfo($fullname, PATHINFO_FILENAME);
        $extension = pathinfo($fullname, PATHINFO_EXTENSION);
        $match = null;

        if (!realpath($dirname)){
            return false;
        } else {
            $filenames = $this->getSomePossibilities($filename);
            $exts = $this->getSomePossibilities($extension);
            $i = 0;
            $n = count($filenames);
            while(!$match && $i < $n){
                $name = $filenames[$i];
                $j = 0;
                $m = count($exts);
                while(!$match && $j < $m){
                    $ext = $exts[$j];
                    $match = file_exists("$dirname/$name.$ext") ? array("file" => $name, "ext" => $ext) : $match;
                    $j++;
                }
                $i++;
            }
            return $match ? $dirname."/".$match['file'].".".$match['ext'] : null;
        }
    }

    private function read($file)
    {
        $this->values = array();

        if (!file_exists($file))
            $file = $this->tryOtherNames($file);

        if (!file_exists($file))
            return false;

        $xmlDoc = new DOMDocument();
        $xmlDoc->load($file);
        $xpath = new DOMXpath($xmlDoc);
        $elements = $xpath->query("/csm_immftan/csm_def/relazione");
        $this->readXmlIccd($elements);

        return true;
    }


    private function readXmlIccd($elements){
      foreach($elements as $element) {
        if($element->getElementsByTagName('file')->item(0)->nodeValue && $element->getElementsByTagName('identificativo_allegato')->item(0)->getElementsByTagName('nome')->item(0)->nodeValue=="FTAN"){
          $this->values[$element->getElementsByTagName('identificativo_allegato')->item(0)->getElementsByTagName('valore')->item(0)->nodeValue]=$element->getElementsByTagName('file')->item(0)->nodeValue;
        }
      }
    }


    public function getImages($record)
    {
        $images = array();

        if (property_exists($record, 'FTA')) {
            foreach ($record->FTA as $fta) {
                $images[] = $this->values[trim($fta->FTAN)];
            }
        }

        return $images;
    }


    public function count()
    {
        return count($this->values);
    }
}
