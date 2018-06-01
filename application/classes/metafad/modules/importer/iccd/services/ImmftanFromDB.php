<?php
class metafad_modules_importer_iccd_services_ImmftanFromDB implements metafad_modules_importer_iccd_services_ImmftanInterface
{
    private $values;

    public function __construct($records)
    {
        //$this->read($records);
        $this->read(unserialize(file_get_contents($records)));

    }

    private function read($records)
    {

        $this->values = array();
        foreach ($records as $work)
            if (array_key_exists('FTA', $work)) {
                foreach ($work['FTA'] as $fta) {
                    $key = implode('-', array($fta['FTAN'], $work['NCTR'], $work['NCTN'], 'ncts' . $work['NCTS'], 'rvel' . $work['RVEL']));

                    $imagename = $fta['fotodir'] . $fta['fotoname'];
                    $fullpath = __Config::get('metafad.modules.importer.iccd.services.ImmftanFromDB.folder') . $fta['fotodir'] . $fta['fotoname'];

                    if (file_exists("$fullpath.jpg"))
                        $this->values[$key] = "$imagename.jpg";
                    elseif (file_exists("$fullpath.JPG"))
                        $this->values[$key] = "$imagename.JPG";
                    else {
                        echo "$key Immagine non trovata: $fullpath<br />\n";
                        flush();
                        $this->values[$key] = "";
                    }
                }
            }

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
                    'RVEL' => @($record->RVEL ?: ($record->RV[0]->RVE[0]->RVEL ?: ""))
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

    public function count()
    {
        return count($this->values);
    }
}
