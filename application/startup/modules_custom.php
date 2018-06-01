<?php
$application = org_glizy_ObjectValues::get('org.glizy', 'application' );
if ($application) {
    __Paths::addClassSearchPath( __Paths::get( 'APPLICATION_CLASSES' ).'userModules/' );
// start archivi//
archivi_Module::registerModule();
// end archivi//
// start AUT300//
AUT300_Module::registerModule();
// end AUT300//
// start AUT400//
AUT400_Module::registerModule();
// end AUT400//
// start BIB300//
BIB300_Module::registerModule();
// end BIB300//
// start BIB400//
BIB400_Module::registerModule();
// end BIB400//
// start SchedaF400//
SchedaF400_Module::registerModule();
// end SchedaF400//
// start SchedaOA300//
SchedaOA300_Module::registerModule();
// end SchedaOA300//
// start SchedaS300//
SchedaS300_Module::registerModule();
// end SchedaS300//
// start SchedaD300//
SchedaD300_Module::registerModule();
// end SchedaD300//
//modules_custom.php
}
