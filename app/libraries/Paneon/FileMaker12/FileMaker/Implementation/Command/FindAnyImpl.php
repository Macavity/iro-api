<?php

namespace Paneon\FileMaker12;

require_once dirname(__FILE__) . '/FindImpl.php';

class FileMaker_Command_FindAny_Implementation extends FileMaker_Command_Find_Implementation
{
    public function __construct($V0ab34ca9, $Vc6140495)
    {
        new FileMaker_Command_Find_Implementation($V0ab34ca9, $Vc6140495);
    }

    function &execute()
    {
        $V21ffce5b = $this->_getCommandParams();
        $V21ffce5b['-findany'] = TRUE;
        $V0f635d0e = $this->_fm->_execute($V21ffce5b);
        if (FileMaker::isError($V0f635d0e)) {
            return $V0f635d0e;
        }
        return $this->_getResult($V0f635d0e);
    }
}
