<?php
class metacms_dam_models_vo_MediaInfoVO
{
    private $data;

    function __construct($data)
    {
        $this->data = $data;
    }


    public function __get($name)
    {
        switch ($name) {
            case 'title':
            case 'menudetail_title':
                return $this->data->MainData->title;
                break;
            case 'description':
            case 'menudetail_description':
                return '';
                break;
            case 'media_type':
                return $this->data->MainData->type;
            case 'media_originalFileName':
                return $this->data->MainData->filename;
            case 'media_size':
                return count($this->data->bytestream)==1 ? $this->data->bytestream->size : 0;
            case 'media_zoom':
                return $this->data->MainData->zoom ? 1 : 0;
        }

        throw new InvalidArgumentException('Invalid field name: '.$name);
    }
}