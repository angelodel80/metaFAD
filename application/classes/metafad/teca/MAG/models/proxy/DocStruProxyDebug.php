<?php
class metafad_teca_MAG_models_proxy_DocStruProxyDebug extends org_glizy_mvc_core_Proxy implements metafad_teca_MAG_models_proxy_DocStruProxyInterface
{
	public function getRootNode($id)
	{
	}

	public function getRootId($id)
	{
	}

	public function getRootNodeByRefId($refId)
	{
	}

	public function getRootNodeByDocumentId($id)
	{
	}

	public function getChildren($id)
	{
	}

	public function getChildrenId($id)
	{
	}

	public function readContentFromNode($id)
	{
	}

	public function saveContentForNode($data)
	{
		var_dump($data);
	}

	public function createNewRootNode($title='Nuova pubblicazione')
	{
        return rand(0, 1000);
	}

	public function validateDataToSave($data)
	{
	}


	public function deleteNode($id, $cleanDocuments=true)
	{
	}

	public function moveNode($id, $newParentId, $position)
	{
	}

	private function moveNodeById($id, $newParentId, $position)
	{
	}

	public function saveNewRoot($id,$title)
	{
	}

	public function saveNewMedia($media,$rootId,$sequence)
	{
	}

	public function getFirstImage($id)
	{
	}

	public function createPages($rootId,$pages)
	{
	}
}
