<?php
interface metafad_teca_MAG_models_proxy_DocStruProxyInterface
{
	public function getRootNode($id);

	public function getRootId($id);

	public function getRootNodeByRefId($refId);

	public function getRootNodeByDocumentId($id);

	public function getChildren($id);

	public function getChildrenId($id);

	public function readContentFromNode($id);

	public function saveContentForNode($data);

	public function createNewRootNode($title='Nuova pubblicazione');

	public function validateDataToSave($data);

	public function deleteNode($id, $cleanDocuments=true);

	public function moveNode($id, $newParentId, $position);

	public function saveNewRoot($id,$title);

	public function saveNewMedia($media,$rootId,$sequence);

	public function getFirstImage($id);

	public function createPages($rootId,$pages);
}
