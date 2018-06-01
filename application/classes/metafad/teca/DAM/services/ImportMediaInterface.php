<?php
interface metafad_teca_DAM_services_ImportMediaInterface
{
    public function mediaExists($filePath);

    public function insertMedia($mediaData,$type);

    public function streamUrl($id, $stream);

    public function mediaUrl($id);

    public function getJSON($id, $title);

    public function addMediaToContainer($magName,$medias,$cover);

    public function insertBytestream($data,$id);

}
