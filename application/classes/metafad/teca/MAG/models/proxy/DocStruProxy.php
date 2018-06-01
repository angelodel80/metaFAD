<?php
class metafad_teca_MAG_models_proxy_DocStruProxy extends org_glizy_mvc_core_Proxy implements metafad_teca_MAG_models_proxy_DocStruProxyInterface
{
	public function getRootNode($id)
	{
		$ar = org_glizy_ObjectFactory::createModel('metafad.teca.MAG.models.Docstru');
		$ar->load($id);
		return $ar;
	}

	public function getRootId($id)
	{
		$ar = org_glizy_ObjectFactory::createModel('metafad.teca.MAG.models.Docstru');
		$ar->load($id);
		return $ar->docstru_rootId;
	}

	public function getRootNodeByRefId($refId)
	{
		$it = org_glizy_ObjectFactory::createModelIterator('metafad.teca.MAG.models.Docstru')
					->where('docstru_refId', $refId);

		return $it->first();
	}

	public function getRootNodeByDocumentId($id)
	{
		$it = org_glizy_ObjectFactory::createModelIterator('metafad.teca.MAG.models.Docstru')
					->where('docstru_FK_document_id', $id);
		return $it->first();
	}

	public function getChildren($id)
	{
		$it = org_glizy_ObjectFactory::createModelIterator('metafad.teca.MAG.models.Docstru')
					->where('docstru_parentId', $id)
					->orderBy('docstru_order');
		return $it;
	}

	public function getChildrenId($id)
	{
		$it = org_glizy_ObjectFactory::createModelIterator('metafad.teca.MAG.models.Docstru')
					->where('docstru_parentId',$id)
					->orderBy('docstru_order');
		$array = array();
		foreach ($it as $value) {
			$array[] = $value->docstru_id;
		}
		return $array;
	}

	public function readContentFromNode($id)
	{
		$ar = org_glizy_ObjectFactory::createModel('metafad.teca.MAG.models.Docstru');
		if ($ar->load($id)) {
			if ($ar->docstru_FK_document_id) {
				$arDoc = org_glizy_ObjectFactory::createModel('metafad.teca.MAG.models.'.ucfirst($ar->docstru_type));
				$arDoc->load($ar->docstru_FK_document_id);
				$data = $arDoc->getValuesAsArray();
			} else {
				$data = array();
			}
			return array_merge($ar->getValuesAsArray(), $data);
		}

		return false;
	}

	public function saveContentForNode($data, $type = 'MAG')
	{
		$id = $data->id;
		$ar = org_glizy_ObjectFactory::createModel('metafad.teca.MAG.models.Docstru');
		if ($id && !$ar->load($id)) {
			return false;
		} else if (!$id) {
			// nuovo nodo
			$ar->docstru_parentId = $data->parentId;
			$ar->docstru_rootId = $data->recordId;
			$ar->docstru_type = $data->type;
			$ar->docstru_order = @$data->order ? $data->order : 0;
		}

		$model = (ucfirst($ar->docstru_type) == 'Publication') ? 'Model' : ucfirst($ar->docstru_type);
		$arDoc = org_glizy_ObjectFactory::createModel('metafad.teca.'.$type.'.models.'.$model);
		if ($ar->docstru_FK_document_id) {
			$arDoc->load($ar->docstru_FK_document_id);
		}
		$ar->docstru_title = $data->docstru_title;
		$data->docstru_id = $id;
		$data->docstru_parentId = $ar->docstru_parentId ? : $id;
		$data->docstru_rootId = $ar->docstru_rootId;
		$data->docstru_type = $ar->docstru_type;
		$data->title = $data->docstru_title;
		$data->nomenclature = $data->docstru_title;
		$data->imggroupID = $data->originalSizeName;

		if ($data->docstru_refInfo) {
			$data->docstru_refId = $data->docstru_refInfo->id;
			$ar->docstru_refId = $data->docstru_refInfo->id;
			$ar->docstru_refInfo = json_encode($data->docstru_refInfo);
		}

		foreach ($data as $k => $v) {
	        // remove the system values
	        if (strpos($k, '__') === 0 || !$arDoc->fieldExists($k)) continue;
	        $arDoc->$k = $v;
	    }
	    $arDoc->fulltext = org_glizycms_core_helpers_Fulltext::make($data, $arDoc);

	    try {
            $documentId = $arDoc->save(null, false, 'PUBLISHED');
            $ar->docstru_FK_document_id = $documentId;
            $ar->save();
            return $documentId;
        } catch (org_glizy_validators_ValidationException $e) {
            return $e->getErrors();
        }
	}

	public function createNewRootNode($title='Nuova pubblicazione')
	{
		$ar = org_glizy_ObjectFactory::createModel('metafad.teca.MAG.models.Docstru');
		$ar->docstru_parentId = 0;
		$ar->docstru_rootId = 0;
		$ar->docstru_type = 'publication';
		$ar->docstru_title = $title;
		$id = $ar->save();

		// agggiorna il campo rootId
		$ar->docstru_rootId = $id;
		$ar->save();

        return $id;
	}

	public function validateDataToSave($data)
	{
		return  !(!$data ||
				(!$data->id && (!$data->parentId || !$data->recordId)) ||
				!in_array($data->type, array('publication', 'folder', 'page')));
	}


	public function deleteNode($id, $cleanDocuments=true, $type = 'MAG', $deleteRoot = true)
	{
		// TODO migliorare la procedura
		$children = $this->getChildren($id);
		foreach ($children as $child) {
			$this->deleteNode($child->docstru_id, false, $type);
		}
		if ($deleteRoot) {
			$ar = org_glizy_ObjectFactory::createModel('metafad.teca.MAG.models.Docstru');
			$ar->load($id);
			$document_id = $ar->docstru_FK_document_id;
			$ar->delete($id);

			$pub = org_glizy_ObjectFactory::createModel('metafad.teca.'.$type.'.models.Publication');
			$pub->delete($document_id);
		}
	}

	public function moveNode($id, $newParentId, $position)
	{
		$id = is_array($id) ? $id : array($id);
		foreach($id as $v) {
			$this->moveNodeById($v, $newParentId, $position);
		}
	}

	private function moveNodeById($id, $newParentId, $position)
	{
		$ar = org_glizy_ObjectFactory::createModel('metafad.teca.MAG.models.Docstru');
		if ($ar->load($id)) {
			$ar->docstru_parentId = $newParentId;
			$ar->docstru_order = $position;
			$ar->save();

			$it = $this->getChildren($newParentId);
	        $pos = 0;
	        foreach ($it as $arNode) {
	            if ($id == $arNode->docstru_id) continue;
	            if ($position == $pos) $pos++;
	            $arNode->docstru_order = $pos;
	            $arNode->save();
	            $pos++;
	        }
		}
	}

	public function saveNewRoot($id,$title)
	{
		if(!$title)
		{
			$title = 'Nessun titolo';
		}
		$docstru = org_glizy_ObjectFactory::createModelIterator('metafad.teca.MAG.models.Docstru')
							 ->where('docstru_FK_document_id',$id)
							 ->first();
		if(!$docstru)
		{
			$docstru = org_glizy_ObjectFactory::createModel('metafad.teca.MAG.models.Docstru');
			$docstru->docstru_parentId = 0;
			$docstru->docstru_FK_document_id = $id;
			$docstru->docstru_title = $title;
			$docstru->docstru_type = 'publication';
			$docstru->docstru_order = 0;
			$rootId = $docstru->save();
			$docstru->docstru_rootId = $rootId;
			$docstru->save();
		}
		else
		{
			$docstru->docstru_title = $title;
			$docstru->save();
		}
		return $rootId;
	}

	function evalRational($e)
    {
        list($n, $d) = explode('/', $e);

        if ($d == 0) {
            return '';
        }

        return $n / $d;
    }

	public function getNisoFromExif($exif)
	{
		$exif = $exif->Exif;

		$nisoImg  = new StdClass;
		$nisoImg->image_length = $exif->ExifImageWidth;
		$nisoImg->image_width = $exif->ExifImageWidth;
		$nisoImg->source_x_dimension = $this->evalRational($exif->XResolution);
		$nisoImg->source_y_dimension = $this->evalRational($exif->YResolution);
		$nisoImg->sampling_frequency_unit = $exif->ResolutionUnit+1;
		$nisoImg->mime = $exif->MimeType;
		
		$niso = new StdClass;
		$niso->NisoImg = $nisoImg;

		return $niso;
	}

	public function saveNewMedia($media,$rootId,$sequence,$modelType = 'MAG')
	{
		$arrayType = array(
			"IMAGE" => "Img",
			"PDF" => "Doc",
			"AUDIO" => "Audio",
			"VIDEO" => "Video"
		);

		//Salvo i dati NISO già nel file se presenti
		$damService = __ObjectFactory::createObject('metafad.teca.DAM.services.ImportMedia');
		$url = $damService->mediaUrl($media->id) . '?bytestream=true';

		$bytestream = json_decode(file_get_contents($url));
		foreach ($bytestream->bytestream as $b) {
			if($b->name == 'original')
			{
				$original = $b;
				break;
			}
		}
		if($original)
		{
			$nisoUrl = $damService->mediaUrl($media->id) . '/bytestream/'.$original->id.'/datastream/NisoImg';
			$niso = json_decode(file_get_contents($nisoUrl));

			/*
			if (!$niso->NisoImg->id) {
				$exifUrl = $damService->mediaUrl($media->id) . '/bytestream/'.$original->id.'/datastream/Exif';
				$exif = json_decode(file_get_contents($exifUrl));
				$niso = $this->getNisoFromExif($exif);
			}
			*/
		}

		$type = $arrayType[$media->type];
		//Salvataggio del media in documents_tbl
		$check = org_glizy_ObjectFactory::createModelIterator('metafad.teca.'. $modelType .'.models.'.$type)
						 ->where('dam_media_id',$media->id)
						 ->where('docstru_rootId',$rootId)
						 ->first();
		if(!$check)
		{
			$obj = new StdClass();
			$obj->instituteKey = metafad_usersAndPermissions_Common::getInstituteKey();
			$obj->mediaId = $media->id;

			$mediaModel = org_glizy_ObjectFactory::createModel('metafad.teca.'. $modelType .'.models.'.$type);
			$mediaModel->dam_media_id = $media->id;
			$mediaModel->sequence_number = $sequence;
			$mediaModel->nomenclature = ($media->label) ? $media->label : $media->title;
			$mediaModel->title = $media->title;
			$mediaModel->file = json_encode($obj);
			$mediaModel->docstru_parentId = $rootId;
			$mediaModel->docstru_rootId = $rootId;
			$mediaModel->docstru_type = lcfirst($type);

			$content = file_get_contents($damService->streamUrlLocal($media->id, 'original'));

			$mediaModel->md5 = md5($content);
			$mediaModel->filesize = mb_strlen($content, '8bit');

			if($niso && $type == 'Img')
			{
				$niso = $niso->NisoImg;
				$mediaModel->imagelength = $niso->image_length;
				$mediaModel->imagewidth = $niso->image_width;
				$mediaModel->source_xdimension = $niso->source_x_dimension;
				$mediaModel->source_ydimension = $niso->source_y_dimension;
				$mediaModel->xsamplingfrequency = $niso->x_sampling_frequency;
				$mediaModel->ysamplingfrequency = $niso->y_sampling_frequency;
				$mediaModel->samplingfrequencyunit = $niso->sampling_frequency_unit;
				$mediaModel->samplingfrequencyplane = $niso->sampling_frequency_plane;
				$mediaModel->bitpersample = $niso->bit_per_sample;
				$mediaModel->photometricinterpretation = $niso->photometric_interpretation;
				$mediaModel->imggroupID = $niso->groupid;
				$mediaModel->name = $niso->name;
				$mediaModel->mime = $niso->mime;
				$mediaModel->compression = $niso->compression;
				$mediaModel->sourcetype = $niso->source_type;
				$mediaModel->scanningagency = $niso->scanning_agency;
				$mediaModel->devicesource = $niso->device_source;
				$mediaModel->scanner_manufacturer = $niso->scanner_manufacturer;
				$mediaModel->scanner_model = $niso->scanner_model;
				$mediaModel->capture_software = $niso->capture_software;
			}


			if($type == 'Img')
			{
				$mediaModel->altimg = $this->createAltImgSection($mediaModel);
			}

			$mediaId = $mediaModel->save(null,false,'PUBLISHED');

			$docstru = org_glizy_ObjectFactory::createModel('metafad.teca.MAG.models.Docstru');
			$docstru->docstru_parentId = $rootId;
			$docstru->docstru_FK_document_id = $mediaId;
			$docstru->docstru_title = $media->title;
			$docstru->docstru_type = lcfirst($type);
			$docstru->docstru_order = $sequence;
			$docstru->docstru_rootId = $rootId;
			$docstru->save();

			$mediaModel->docstru_id = $docstru->docstru_id;
			$mediaId = $mediaModel->save();
		}
	}

	public function getFirstImage($id)
	{
		$rootId = $this->getRootNodeByDocumentId($id);
		$damService = __ObjectFactory::createObject('metafad.teca.DAM.services.ImportMedia');

		if($rootId)
		{
			$firstImage = org_glizy_ObjectFactory::createModelIterator('metafad.teca.MAG.models.Img')
					->where('docstru_rootId',$rootId->docstru_id)
					->where('docstru_type','img')->first();

			$media = new stdClass();
			$media->id = $firstImage->dam_media_id;
			$media->title = $firstImage->title;
			$media->type = 'IMAGE';
			$media->src = $damService->streamUrl($firstImage->dam_media_id,'original');
			$media->thumbnail =  $damService->streamUrl($firstImage->dam_media_id,'thumbnail');

			return $media;
		}
		else
		{
			return null;
		}
	}

	public function createPages($rootId,$pages,$type = 'MAG')
	{
		$damService = __ObjectFactory::createObject('metafad.teca.DAM.services.ImportMedia');

		$count = 1;
		foreach($pages as $p) {
			$data = new stdClass();
			$imageDataDecoded = (is_string($p)) ? json_decode($p) : $p;
			if($p)
			{
				$obj = new StdClass();
				$obj->instituteKey = metafad_usersAndPermissions_Common::getInstituteKey();
				$obj->mediaId = $imageDataDecoded->id;

				$data->docstru_title = ($imageDataDecoded->title) ?: 'Senza titolo' ;
				$data->dam_media_id = $imageDataDecoded->id;
				$data->file = json_encode($obj);
				$content = file_get_contents($damService->streamUrlLocal($imageDataDecoded->id, 'original'));
				$data->md5 = md5($content);
				$data->filesize = mb_strlen($content, '8bit');
				$data->imagelength = $imageDataDecoded->height;
				$data->imagewidth = $imageDataDecoded->width;
				$data->originalSizeName = 'M';
				$data->sequence_number = $count;

				//NISO - EXIF
				//Salvo i dati NISO già nel file se presenti
				//TODO Questo codice è praticamente duplicato, sarebbe utile 
				//fare una cosa più pulita (non è del tutto identico però, per ora
				//ho fatto così ma con un po' di tempo sistemerò)
				$url = $damService->mediaUrl($imageDataDecoded->id) . '?bytestream=true';

				$bytestream = json_decode(file_get_contents($url));
				foreach ($bytestream->bytestream as $b) {
					if ($b->name == 'original') {
						$original = $b;
						break;
					}
				}
				if ($original) {
					$nisoUrl = $damService->mediaUrl($imageDataDecoded->id) . '/bytestream/' . $original->id . '/datastream/NisoImg';
					$niso = json_decode(file_get_contents($nisoUrl));

					/*
					if (!$niso->NisoImg->id) {
						$exifUrl = $damService->mediaUrl($imageDataDecoded->id) . '/bytestream/' . $original->id . '/datastream/Exif';
						$exif = json_decode(file_get_contents($exifUrl));
						$niso = $this->getNisoFromExif($exif);
					}
					*/
				}

				if ($niso) {
					$niso = $niso->NisoImg;
					$data->imagelength = $niso->image_length;
					$data->imagewidth = $niso->image_width;
					$data->source_xdimension = $niso->source_x_dimension;
					$data->source_ydimension = $niso->source_y_dimension;
					$data->xsamplingfrequency = $niso->x_sampling_frequency;
					$data->ysamplingfrequency = $niso->y_sampling_frequency;
					$data->samplingfrequencyunit = $niso->sampling_frequency_unit;
					$data->samplingfrequencyplane = $niso->sampling_frequency_plane;
					$data->bitpersample = $niso->bit_per_sample;
					$data->photometricinterpretation = $niso->photometric_interpretation;
					$data->imggroupID = $niso->groupid;
					$data->name = $niso->name;
					$data->mime = $niso->mime;
					$data->compression = $niso->compression;
					$data->sourcetype = $niso->source_type;
					$data->scanningagency = $niso->scanning_agency;
					$data->devicesource = $niso->device_source;
					$data->scanner_manufacturer = $niso->scanner_manufacturer;
					$data->scanner_model = $niso->scanner_model;
					$data->capture_software = $niso->capture_software;
				}
				//FINE NISO - EXIF

				$data->parentId = $rootId;
				$data->recordId = $rootId;
				$data->type = 'img';

				$data->altimg = $this->createAltImgSection($data);

				$data->order = $count;
				$this->saveContentForNode($data, $type);
				unset($data);
				$count++;
			}
		}
	}

	public function createAltImgSection($mediaModel)
	{
		$damService = __ObjectFactory::createObject('metafad.teca.DAM.services.ImportMedia');

		$usage = new stdClass();
		$usage->usage_value = 2;
		$mediaModel->usage = array($usage);

		//IMG GROUP, non credo sia fondamentale ma setto a S
		//dato che questo altimg corrisponde esattamente ad un 
		//ipotetico gruppo S
		$altimg = array();
		$o = new stdClass();
		$o->altimg_imggroupID = '';

		//usage
		$usage = new stdClass();
		$usage->altimg_usage_value = 3;
		$o->altimg_usage = array($usage);

		//file
		//sarebbe una versione resize dell'immagine "principale"
		$o->altimg_file = $mediaModel->file;
		$file = json_decode($mediaModel->file);
		$content = file_get_contents($damService->resizeStreamUrlLocal($file->mediaId, 'original',__Config::get('gruppometa.dam.resizeStreamS') ));
		$o->altimg_md5 = md5($content);
		$o->altimg_filesize = mb_strlen($content, '8bit');

		//Recupero NISO dello stream resized
		$nisoResize = json_decode(file_get_contents($damService->resizeInfoLocal($file->mediaId, 'original', __Config::get('gruppometa.dam.resizeStreamS'))));
		if(!$nisoResize->NisoImg)
		{
			$niso = $this->getNisoFromExif($nisoResize->Exif);
		}
		else
		{
			$niso = $nisoResize->NisoImg;
		}

		if($niso)
		{
			$o->altimg_imagelength = $niso->image_length;
			$o->altimg_imagewidth = $niso->image_width;
			$o->altimg_source_xdimension = $niso->source_x_dimension;
			$o->altimg_source_ydimension = $niso->source_y_dimension;
			$o->altimg_xsamplingfrequency = $niso->x_sampling_frequency;
			$o->altimg_ysamplingfrequency = $niso->y_sampling_frequency;
			$o->altimg_samplingfrequencyunit = $niso->sampling_frequency_unit;
			$o->altimg_samplingfrequencyplane = $niso->sampling_frequency_plane;
			$o->altimg_bitpersample = $niso->bit_per_sample;
			$o->altimg_photometricinterpretation = $niso->photometric_interpretation;
			$o->altimg_name = $niso->name;
			$o->altimg_mime = $niso->mime;
			$o->altimg_compression = $niso->compression;
			$o->altimg_sourcetype = $niso->source_type;
			$o->altimg_scanningagency = $niso->scanning_agency;
			$o->altimg_devicesource = $niso->device_source;
			$o->altimg_scanner_manufacturer = $niso->scanner_manufacturer;
			$o->altimg_scanner_model = $niso->scanner_model;
			$o->altimg_capture_software = $niso->capture_software;
		}

		$altimg[] = $o;

		return $altimg;
	}
}
