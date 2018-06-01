<?php
require_once("core/core.inc.php");

glz_require_once_dir('application/classes/metafad/oaipmh/core');
$application = org_glizy_ObjectFactory::createObject('metafad.oaipmh.core.Application', 'application', '');

/*
- ICAR ha previsto 4 tipologie diverse di oggetto esportato
(CA fino a livello sottoserie, Sogg. Cons, Sogg. Prod,, Strumenti Ric.)
I produttori e conservatori sono distinti per cui andrà gestito nella nostra scheda entità unica.
Inoltre, avremo quindi 4 profili diversi da mostrare
*/

$application->addMetadataFormat(
    'ead-san',
    'http://san.beniculturali.it/schema/ead-san.xsd',
    'http://san.mibac.it/ead-san/',
    'ead-san',
    'http://san.mibac.it/ead-san/'
);

$application->addMetadataFormat(
    'eac-san',
    'http://san.beniculturali.it/schema/eac-san.xsd',
    'http://san.mibac.it/eac-san/',
    'eac-san',
    'http://san.mibac.it/eac-san/'
);

$application->addMetadataFormat(
    'scons-san',
    'http://san.beniculturali.it/schema/scons-san.xsd',
    'http://san.mibac.it/scons-san/',
    'scons-san',
    'http://san.mibac.it/scons-san/'
);

$application->addMetadataFormat(
    'ricerca-san',
    'http://san.beniculturali.it/schema/ricerca-san.xsd',
    'http://san.mibac.it/ricerca-san/',
    'ricerca-san',
    'http://san.mibac.it/ricerca-san/'
);

$application->addSet('ead-san', 'metafad.oaipmh.sets.ComplessoArchivistico');
$application->addSet('eac-san', 'metafad.oaipmh.sets.SoggettoProduttore');
$application->addSet('scons-san', 'metafad.oaipmh.sets.SoggettoConservatore');
$application->addSet('ricerca-san', 'metafad.oaipmh.sets.StrumentiRicerca');
$application->run();


