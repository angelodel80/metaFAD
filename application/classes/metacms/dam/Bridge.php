<?php
class metacms_dam_Bridge implements org_glizycms_mediaArchive_BridgeInterface
{
    protected $cmsCmpatibility;

    function __construct()
    {
        $this->cmsCmpatibility = __Config::get('gruppometa.dam.cmsIntegration')===true;
    }

    protected function getDamUrlFe()
    {
        return __Config::get('gruppometa.dam_fe.url') . '?instance=' . __Config::get('gruppometa.dam.instance');
    }

    protected function getDamUrl()
    {
        return metafad_teca_DAM_Common::getDamUrl() . '/';
    }

    public function mediaByIdUrl($id)
    {
        return $this->resolveMediaByIdUrl($id, $this->cmsCmpatibility);
    }

    public function imageByIdUrl($id)
    {
        return $this->resolveImageByIdUrl($id, $this->cmsCmpatibility);
    }

    public function imageByIdAndResizedUrl($id, $width, $height, $crop=false, $cropOffset=1, $forceSize=false, $useThumbnail=false)
    {
        return $this->resolveImageByIdAndResizedUrl($id, $width, $height, $crop, $cropOffset, $forceSize, $useThumbnail, $this->cmsCmpatibility);
    }

    public function jsonFromModel($model)
    {
        // lanciare un'eccezione
        // non supportato
    }

    public function mediaPickerUrl($tinyVersion=false, $mediaType='ALL')
    {

       return $this->getDamUrlFe() . '&cms=true&singleSelection=true';
    }

    public function mediaTemplateUrl()
    {
        return $this->mediaByIdUrl('#id#');
    }

    public function imageTemplateUrl()
    {
        return $this->mediaByIdUrl('#id#');
    }

    public function imageResizeTemplateUrl($width='#w#', $height='#h#', $crop=false, $cropOffset=1)
    {
        return $this->imageByIdAndResizedUrl('#id#', $width, $height, $crop, $cropOffset);
    }

    public function mediaIdFromJson($json)
    {
        return $json->id;
    }

    public function mediaInfo($id)
    {
        $r = org_glizy_ObjectFactory::createObject(
                'org.glizy.rest.core.RestRequest',
                $this->getDamUrl().'media/'.$id,
                'GET',
                'MainData=true&bytestream=original');
        $r->execute();
        $info = $r->getResponseInfo();
        $body = @json_decode($r->getResponseBody());
        if ($info['http_code'] == 200 && $body) {
            return __ObjectFactory::createObject('metacms.dam.models.vo.MediaInfoVO', $body);
        }

        return null;
    }

    public function serveMedia($id)
    {
        $this->redirectTo($this->resolveMediaByIdUrl($id, false));
    }

    public function serveImage($id, $width, $height, $crop=false, $cropOffset=1, $forceSize=false, $useThumbnail=false)
    {
        if ($width && $height) {
            $this->redirectTo($this->resolveImageByIdAndResizedUrl($id, $width, $height, $crop, $cropOffset, $forceSize, $useThumbnail, false));
        } else {
            $this->redirectTo($this->resolveMediaByIdUrl($id, false));
        }
    }

    private function redirectTo($url)
    {
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: '.$url);
    }

    private function resolveMediaByIdUrl($id, $cmsCmpatibility)
    {
        return $cmsCmpatibility ?
                    'getFile.php?id='.$id :
                    $this->getDamUrl() . 'get/' . $id . '/original';
    }

    private function resolveImageByIdUrl($id, $cmsCmpatibility)
    {
        return $cmsCmpatibility ?
                    'getImage.php?id='.$id :
                    $this->getDamUrl() . 'get/' . $id . '/original';
    }

    private function resolveImageByIdAndResizedUrl($id, $width, $height, $crop, $cropOffset, $forceSize, $useThumbnail, $cmsCmpatibility)
    {
         return $cmsCmpatibility ?
                    'getImage.php?id='.$id.'&w='.$width.'&h='.$height.'&c='.($crop ? '1' : '0').'&co='.$cropOffset.'&f='.($forceSize ? '1' : '0').'&t='.($useThumbnail ? '1' : '0').'&.jpg' :
                    $this->getDamUrl() . 'resize/' . $id . '/'.($useThumbnail ? 'thumbnail' : 'original').'?&w='.$width.'&h='.$height.'&c='.($crop ? '1' : '0').'&co='.$cropOffset.'&f='.($forceSize ? '1' : '0');
    }
    
    public function mediaInfoAll($id)
    {
        $r = org_glizy_ObjectFactory::createObject(
            'org.glizy.rest.core.RestRequest',
            $this->damUrl.'media/'.$id,
            'GET',
            'MainData=true&bytestream=original&datastream=all');
        $r->disableSslCheck(true);
        $r->execute();
        $info = $r->getResponseInfo();
        $body = @json_decode($r->getResponseBody());
        if ($info['http_code'] == 200 && $body) {
            return __ObjectFactory::createObject('metacms.dam.models.vo.MediaInfoVO', $body);
        }

        return null;
    }
}
