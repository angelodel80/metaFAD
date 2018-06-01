<?php
class archivi_models_vo_DocumentObjectVO extends org_glizy_dataAccessDoctrine_vo_DocumentObjectVO
{
    function __construct($data)
    {
        $languageProxy = __ObjectFactory::createObject('org.glizycms.languages.models.proxy.LanguagesProxy');

        $index = null;

        if (is_array($data[self::DOCUMENT_DETAIL_STATUS])) {
            $index = array_search('PUBLISHED', $data[self::DOCUMENT_DETAIL_STATUS]);
            $this->hasPublishedVersion = $index !== FALSE;

            $indexDraft = array_search('DRAFT', $data[self::DOCUMENT_DETAIL_STATUS]);
            $this->hasDraftVersion = $indexDraft !== FALSE;

            // se ci sono entrambe le versioni PUBLISHED e DRAFT, prevale quella più recente
            if ($this->hasPublishedVersion && $this->hasDraftVersion) {
                $timestamp0 = strtotime($data['document_detail_modificationDate'][0]);
                $timestamp1 = strtotime($data['document_detail_modificationDate'][1]);

                $index = ($timestamp0 > $timestamp1) ? $index : $indexDraft;
            } else if (!$this->hasPublishedVersion) {
                $index = $indexDraft;
            }
        }

        if ($index && $data['document_detail_FK_language_id'][$index] != $languageProxy->getLanguageId()) {
           $data['document_detail_FK_language_id'][$index] = $languageProxy->getLanguageId();
           $data['document_detail_translated'][$index] = 0;
        }

        foreach ($data as $k => $v) {
            if (!is_null($index) && is_array($v)) {
                $this->data[$k] = $v[$index];
            } else {
                $this->data[$k] = $v;
            }
        }
    }
}