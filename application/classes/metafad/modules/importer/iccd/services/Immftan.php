<?php
class metafad_modules_importer_iccd_services_Immftan implements metafad_modules_importer_iccd_services_ImmftanInterface
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

        if (!file_exists($file)){
            $file = $this->tryOtherNames($file);
        }

        if (!file_exists($file))
            return false;

        $lines = file($file);
        foreach ($lines as $l)
            $this->parseLine($l);

        return true;
    }

    public function getImages($record)
    {
        $images = array();

        if (property_exists($record, 'FTA')) {
            foreach ($record->FTA as $fta) {
                $images[] = $this->imageBy(array(
                    'FTAN' => $fta->FTAN,
                    'NCTR' => $record->NCTR,
                    'NCTN' => $record->NCTN,
                    'NCTS' => $record->NCTS,
                    'RVEL' => $this->getRVELFromRecord($record)
                ));
            }
        }

        return $images;
    }

    public function imageBy($record)
    {
        $key = implode('-', array($record['FTAN'], $record['NCTR'], $record['NCTN'], 'ncts' . $record['NCTS'], 'rvel' . $record['RVEL']));

        return array_key_exists($key, $this->values) ? $this->values[$key] : '';
    }

    private function parseLine($line)
    {
        if (preg_match('/^([0-9]+),(.+?),FTAN:(.+?),NCTR:([0-9]+),NCTN:([0-9]+),,$/', $line, $matches))
            $key = implode('-', array($matches[3], $matches[4], $matches[5], 'ncts', 'rvel'));
        elseif (preg_match('/^([0-9]+),(.+?),FTAN:(.+?),NCTR:([0-9]+),NCTN:([0-9]+),NCTS:(.+?),$/', $line, $matches))
            $key = implode('-', array($matches[3], $matches[4], $matches[5], 'ncts' . $matches[6], 'rvel'));
        elseif (preg_match('/^([0-9]+),(.+?),FTAN:(.+?),NCTR:([0-9]+),NCTN:([0-9]+),,RVEL:(.+?)$/', $line, $matches))
            $key = implode('-', array($matches[3], $matches[4], $matches[5], 'ncts', 'rvel' . $matches[6]));
        elseif (preg_match('/^([0-9]+),(.+?),FTAN:(.+?),NCTR:([0-9]+),NCTN:([0-9]+),NCTS:(.+?),RVEL:(.+?)$/', $line, $matches))
            $key = implode('-', array($matches[3], $matches[4], $matches[5], 'ncts' . $matches[6], 'rvel' . $matches[7]));

        $this->values[$key] = $matches[2];
    }

    public function count()
    {
        return count($this->values);
    }

    /**
     * @param $record
     * @return int
     */
    private function getRVELFromRecord($record)
    {
        if (property_exists($record, "RVEL")) {
            return $record->RVEL === null ? "" : $record->RVEL;
        }

        $value = null;
        $value = @($record->RV[0]->RVE[0]->RVEL);
        if ($value === null) {
            return "";
        }

        return $value;
    }
}
