<?php
class metafad_oaipmh_adapters_MagRecordIterator extends org_glizy_oaipmh2_core_AbstractRecordIterator implements org_glizy_oaipmh2_core_RecordIteratorInterface
{
    private $docs;

    /**
     * @param StdClass[] $docs
     */
    public function __construct($docs)
    {
        $this->docs = $docs;
    }


    /**
     * @return org_glizy_oaipmh2_models_VO_RecordVO
     */
    public function current()
    {
        $temp = $this->docs[$this->position];
        return org_glizy_oaipmh2_models_VO_RecordVO::create($temp->id, $temp->update_at_s, 'metafad.teca.MAG.models.Model', $temp);
    }

    /**
     * @return boolean
     */
    public function valid()
    {
        return isset($this->docs[$this->position]);
    }
}
