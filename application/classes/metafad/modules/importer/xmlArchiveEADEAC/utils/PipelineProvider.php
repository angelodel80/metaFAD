<?php

/**
 * Created by PhpStorm.
 * User: marco
 * Date: 13/02/17
 * Time: 9.39
 */
class metafad_modules_importer_xmlArchiveEADEAC_utils_PipelineProvider
{
    public static function getEACPipeline($filePath, $instituteKey, $jsonMaps){
        return <<<EOF
[
  {
    "obj": "metafad_common_importer_operations_ReadXML",
    "weight": 110,
    "params": {
      "filename": "$filePath"
    }
  },
  {
    "obj": "metafad_common_importer_operations_InstituteSetter",
    "weight": 10,
    "params": {
      "ignoreInput": true,
      "instituteKey": "$instituteKey"
    }
  },
  {
    "obj": "metafad_common_importer_operations_GetXMLNodeList",
    "weight": 250,
    "params": {
      "rootxpath": "/eacgrp/xw_doc/eac-cpf"
    }
  },
  {
    "obj": "metafad_common_importer_operations_Iterate",
    "weight": 6000,
    "params": {
      "operations": [
        {
          "obj": "metafad_common_importer_operations_EACInferModel",
          "params": {
            "xpathToMappingFile": {
              "self::node()[descendant::entityType[text()[normalize-space(.)='person']] and descendant::function[text()[normalize-space(.)='soggetto produttore']]]": "{$jsonMaps['entitaPersona']}",
              "self::node()[descendant::entityType[text()[normalize-space(.)='corporateBody']] and descendant::function[text()[normalize-space(.)='soggetto produttore']]]": "{$jsonMaps['entitaFamiglia']}",
              "self::node()[descendant::entityType[text()[normalize-space(.)='family']] and descendant::function[text()[normalize-space(.)='soggetto produttore']]]": "{$jsonMaps['entitaEnte']}",
              "self::node()[descendant::entityType[text()[normalize-space(.)='family' or normalize-space(.)='person']]]": "{$jsonMaps['antroponimo']}",
              "self::node()[descendant::entityType[text()[normalize-space(.)='corporateBody']]]": "{$jsonMaps['ente']}"
            }
          }
        },
        {
          "obj": "metafad_common_importer_operations_XmlToJson",
          "params": {
            "schemafile": "{$jsonMaps['void']}"
          }
        },
        {
          "obj": "metafad_common_importer_operations_ResolveMAG",
          "params": {}
        },
        {
          "obj": "metafad_common_importer_operations_SaveArchivistico"
        },
        {
          "obj": "metafad_common_importer_operations_LogInput",
          "params": {
            "instructions": {
              "message": "Salvato ID_DB: <##id##>, Model:<##model##>, extid:<##extid##>, intestazione:<##head##>",
              "valueSrc": {
                "id": "id",
                "head": "data->intestazione",
                "model": "data->__model",
                "extid": "data->externalID"
              }
            }
          }
        }
      ]
    }
  },
  {
    "obj": "metafad_common_importer_operations_FlushQueue",
    "weight": 10
  }
]
EOF;
    }

    /**
     * Restituisce la pipeline testuale
     * @param $filePath
     * @param $instituteKey
     * @param $jsonMaps
     * @param $fondoId string|int se 0 o null, non viene creato un fondo wrapper
     * @param $fondoText
     * @param null $dumpDir
     * @return string
     */
    public static function getEADPipeline($filePath, $instituteKey, $jsonMaps, $fondoId, $fondoText, $dumpDir = null){
        $dumpRow = realpath($dumpDir) ? "\"dumpdir\": \"$dumpDir\"," : "";

        $linkOperation = $fondoId ? "        {
          \"obj\": \"metafad_common_importer_operations_MergeObject\",
          \"params\": {
            \"overwrite\": false,
            \"object\": {
              \"parent\": {
                \"id\": \"$fondoId\",
                \"text\": \"$fondoText\"
              }
            }
          }
        }," : "";


        return <<<EOF
[
  {
    "obj": "metafad_common_importer_operations_ReadXML",
    "weight": 110,
    "params": {
      "filename": "$filePath"
    }
  },
  {
    "obj": "metafad_common_importer_operations_InstituteSetter",
    "weight": 10,
    "params": {
      "ignoreInput": true,
      "instituteKey": "$instituteKey"
    }
  },
  {
    "obj": "metafad_common_importer_operations_EADGetXMLNodeList",
    "weight": 400,
    "params": {
      "idxpath": "./@id",
      "rootxpath": "/rsp/dsc/c",
      "childxpath": "./dsc/c",
      $dumpRow
      "acronimoSistema": "PDN"
    }
  },
  {
    "obj": "metafad_common_importer_operations_Iterate",
    "weight": 7000,
    "params": {
      "operations": [
        {
          "obj": "metafad_common_importer_operations_EADXmlToJson",
          "params": {
            "schemafile": "{$jsonMaps['common']}",
            "ca_schemafile": "{$jsonMaps['CA']}",
            "ua_schemafile": "{$jsonMaps['UA']}",
            "ud_schemafile": "{$jsonMaps['UD']}"
          }
        },
{$linkOperation}
        {
          "obj": "metafad_common_importer_operations_EADPreSaveCorrections"
        },
        {
          "obj": "metafad_common_importer_operations_SaveArchivistico"
        },
        {
          "obj": "metafad_common_importer_operations_LogInput",
          "params": {
            "instructions": {
              "message": "Salvato ID_DB: <##id##>, Model:<##model##>, extid:<##extid##>",
              "valueSrc": {
                "id": "id",
                "model": "data->__model",
                "extid": "data->externalID"
              }
            }
          }
        }
      ]
    }
  },
  {
    "obj": "metafad_common_importer_operations_FlushQueue",
    "weight": 10
  }
]
EOF;

    }
}