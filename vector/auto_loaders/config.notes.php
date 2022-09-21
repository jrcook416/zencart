<?php
$autoLoadConfig[300][] = array( 
    'autoType' => 'class', 
    'loadFile' => 'Notes.php',
    'classPath' => DIR_WS_CLASSES
);
                             
$autoLoadConfig[300][] = array(
    'autoType' => 'class',
    'loadFile' => 'observers/NotesObserver.php',
    'classPath' => DIR_WS_CLASSES
);
$autoLoadConfig[300][] = array(
    'autoType' => 'classInstantiate',
    'className' => 'NotesObserver',
    'objectName' => 'notes_observer'
);
