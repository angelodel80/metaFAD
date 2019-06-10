<?php
class metafad_teca_DAM_services_ImportMedia implements metafad_teca_DAM_services_ImportMediaInterface
{
	protected $damUrl;
	protected $damUrlLocal;
	protected $damInstance;

	/** var org_glizy_log_LogBase */
	protected $logger;

	public function __construct($damInstance = null, org_glizy_log_LogBase $logger = null)
	{
		$this->damInstance = $damInstance;
		$this->damUrl = metafad_teca_DAM_Common::getDamUrl($damInstance);
		$this->damUrlLocal = metafad_teca_DAM_Common::getDamUrlLocal($damInstance);
		$this->logger = $logger;
	}

	public function mediaExists($filePath)
	{
		$md5 = md5_file($filePath);
		$result = org_glizy_ObjectFactory::createObject('org.glizy.rest.core.RestRequest', $this->damUrlLocal.'/exists/md5/'.$md5);
		$result->execute();
		$info = $result->getResponseInfo();
		$body = json_decode($result->getResponseBody());
		$response = $info['http_code'] == 200 && !empty($body->ids);
		return array('response'=>$response, 'ids'=>$body->ids);
	}

	public function insertMedia($mediaData,$type = 'media')
	{
		$r = org_glizy_ObjectFactory::createObject('org.glizy.rest.core.RestRequest', $this->damUrlLocal.'/'.$type, 'POST', $mediaData, 'application/json');
		$r->setTimeout(__Config::get('gruppometa.dam.timeout'));
		$response = $r->execute();

		if ($response) {
			$info = $r->getResponseInfo();
			$body = json_decode($r->getResponseBody());
			if ($info['http_code'] == 201 && !empty($body)) {
				$result = $body;
			} else if ($this->logger) {
				$this->logError($mediaData, $response, json_encode($info), $r->getResponseBody());
			}

		} else {
			if ($this->logger) {
				$this->logError($mediaData, $response, json_encode($r->getResponseInfo()), $r->getResponseBody());
			}
			$result = null;
		}

		return $result;
	}

	protected function logError($mediaData, $response, $info, $body)
	{
		$this->logger->debug("Errore nel salvataggio sul DAM! " . $mediaData);
		$this->logger->debug("Response: ".$response);
		$this->logger->debug("Response Info: ".$info);
		$this->logger->debug("Response Body: ".$body);
	}

	public function streamUrl($id, $stream)
	{
		if($this->damInstance == '*')
		{
			$this->correctInstance($id);
		}

		if ($stream == 'original') {
			$url = $this->damUrl.'/resize/'.$id.'/original?h=*&w='.__Config::get('gruppometa.dam.maxResizeWidth');
		} else {
			$url = $this->damUrl.'/get/'.$id.'/'.$stream;
		}
		return $url;
	}

	public function resizeStreamUrl($id, $stream, $w)
	{
		if($this->damInstance == '*')
		{
			$this->correctInstance($id);
		}
		return $this->damUrl.'/resize/'.$id.'/'.$stream.'?w='.$w;
	}

	public function resizeStreamUrlLocal($id, $stream, $w)
	{
		if($this->damInstance == '*')
		{
			$this->correctInstance($id);
		}
		return $this->damUrlLocal.'/resize/'.$id.'/'.$stream.'?w='.$w;
	}

	public function resizeInfoLocal($id, $stream, $w)
	{
		if ($this->damInstance == '*') {
			$this->correctInstance($id);
		}
		return $this->damUrlLocal . '/resize-stream/' . $id . '/' . $stream . '/S?datastream=MainData,Exif,NisoImg&w='.$w;
	}

	public function streamUrlLocal($id, $stream)
	{
		if($this->damInstance == '*')
		{
			$this->correctInstance($id);
		}
		return $this->damUrlLocal.'/get/'.$id.'/'.$stream;
	}

	public function mediaUrl($id, $local = true)
	{
		if($this->damInstance == '*')
		{
			$this->correctInstance($id);
		}
		if($local)
		{
			return $this->damUrlLocal.'/media/'.$id;
		}
		else
		{
			return $this->damUrl.'/media/'.$id;
		}
	}

	public function getJSON($id, $title)
	{
		$obj = new stdClass;
		$obj->id = $id;
		$obj->title = $title;
		$obj->type = 'IMAGE';
		return json_encode($obj);
	}

	public function addMediaToContainer($magName, $medias, $cover)
	{
		$containerData['MainData']['title'] = $magName;
		$containerData['mediaId'] = reset(json_decode($medias)->addMedias);
		$container = $this->insertMedia(json_encode($containerData),'container');
		$id = $container->id;

		$r = org_glizy_ObjectFactory::createObject('org.glizy.rest.core.RestRequest', $this->damUrlLocal.'/container/'.$id.'/ContainedMedia', 'POST', $medias, 'application/json');
		$response = $r->execute();
	}

	public function deleteContainer($id, $removeContainedMedia=false)
	{
		$r = org_glizy_ObjectFactory::createObject('org.glizy.rest.core.RestRequest', $this->damUrlLocal.'/container/'.$id.'?removeContainedMedia='.($removeContainedMedia ? 'true' : 'false'), 'DELETE', $medias, 'application/json');
		$response = $r->execute();
	}

	public function insertBytestream($data,$id)
	{
		$r = org_glizy_ObjectFactory::createObject('org.glizy.rest.core.RestRequest', $this->damUrlLocal.'/media/'.$id.'/bytestream', 'POST', $data, 'application/json');
		$result = null;
		if ($r->execute()) {
			$info = $r->getResponseInfo();
			$body = json_decode($r->getResponseBody());
			if ($info['http_code'] == 201 && !empty($body)) {
				$result = $body;
			}
		}
		return $result;
	}

	public function search($title)
	{
		$params = array(
			'search' => array(array('title' => $title))
		);
		$r = org_glizy_ObjectFactory::createObject('org.glizy.rest.core.RestRequest', $this->damUrlLocal.'/search', 'POST', json_encode($params), 'application/json');
		$r->execute();
		$response = json_decode($r->getResponseBody());
		return $response->results;
	}

	public function getInstance()
	{
		return $this->damInstance;
	}

	public function correctInstance($id)
	{
		$url = __Config::get('gruppometa.dam.solr.url');
		$r = org_glizy_ObjectFactory::createObject('org.glizy.rest.core.RestRequest', $url.'select/?q=id:'.$id.'&wt=json', 'GET', '', 'application/json');
		$r->execute();
		if($r->getResponseBody())
		{
			$realInstance = json_decode($r->getResponseBody())->response->docs[0]->instance_s;
			$this->damUrl = metafad_teca_DAM_Common::getDamUrl($realInstance);
		}
	}

	public function saveMediaDatastream($mediaId, $mediaData,$datastreamName,$datastreamId = null, $method = null)
	{
		if($method == null)
		{
			$method = ($datastreamId) ? 'PUT' : 'POST';
		}
		$r = org_glizy_ObjectFactory::createObject('org.glizy.rest.core.RestRequest', $this->damUrlLocal . '/media/' .$mediaId .'/datastream/'. $datastreamName .(($datastreamId)?'/'.$datastreamId:''), $method, $mediaData, 'application/json');
		$r->setTimeout(__Config::get('gruppometa.dam.timeout'));
		$response = $r->execute();
		if ($response) {
			$info = $r->getResponseInfo();
			$body = json_decode($r->getResponseBody());
			if (($info['http_code'] == 201 || $info['http_code'] == 200) && !empty($body)) {
				$result = $body;
			} else if ($this->logger) {
				$this->logError($mediaData, $response, json_encode($info), $r->getResponseBody());
			}

		} else {
			if ($this->logger) {
				$this->logError($mediaData, $response, json_encode($r->getResponseInfo()), $r->getResponseBody());
			}
			$result = null;
		}
		return $result;
	}
}
