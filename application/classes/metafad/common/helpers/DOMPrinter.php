<?php

/**
 * Created by PhpStorm.
 * User: marco
 * Date: 06/12/16
 * Time: 10.06
 */
class metafad_common_helpers_DOMPrinter extends GlizyObject
{
    /**
     * @var $tempDoc DOMDocument
     */
    private $tempDoc;

    public function __construct()
    {
        $this->tempDoc = new DOMDocument();
    }

    /**
     * @param DOMNode $node
     * @param string $filename
     * @param callable|null $preprocess
     */
    public function saveHTML(DOMNode $node, $filename, callable $preprocess = null){
        $clone = $node->cloneNode(true);

        if ($preprocess){
            $preprocess($clone);
        }

        $clone->ownerDocument->formatOutput = true;
        file_put_contents($filename, $clone->ownerDocument->saveXML($clone));

        unset($clone);
    }

}