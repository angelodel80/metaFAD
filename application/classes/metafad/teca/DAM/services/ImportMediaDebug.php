<?php
class metafad_teca_DAM_services_ImportMediaDebug implements metafad_teca_DAM_services_ImportMediaInterface
{
    private $damInstance;

    public function __construct($damInstance)
    {
      $this->damInstance = $damInstance;
    }

    public function mediaExists($filePath)
    {
        return array('response'=> null, 'ids'=> null);
    }

    public function insertMedia($mediaData)
    {
        var_dump('INSERT MEDIA');
        var_dump($mediaData);
        $r = new StdClass;
        $r->ids = array(rand(0, 1000));
        return $r;
    }

    public function streamUrl($id, $stream)
    {
        return $this->damInstance.'/get/'.$id.'/'.$stream;
    }

    public function mediaUrl($id)
    {
        return $this->damInstance.'/media/'.$id;
    }

    public function getJSON($id, $title)
    {
        $obj = new StdClass;
        $obj->id = $id;
        $obj->title = $title;
        $obj->type = 'IMAGE';
        $obj->src = $this->streamUrl($id, 'original');
        $obj->thumbnail = $this->streamUrl($id, 'thumbnail');
        $obj->metadata = $this->mediaUrl($id);
        $result = json_encode($obj);
    }
}
