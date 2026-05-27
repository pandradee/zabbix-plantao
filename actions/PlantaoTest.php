<?php declare(strict_types = 1);

namespace Modules\Plantao\Actions;

use CController,
    CControllerResponseData;

class PlantaoTest extends CController {

    public function init(): void {
        $this->disableCsrfValidation();
    }

    protected function checkInput(): bool { return true; }
    public function checkPermissions(): bool { return true; }

    protected function doAction(): void {
        global $DB;

        $info = [];

        
        $r1 = pg_query($DB['DB'], 'SELECT 1 AS a');
        $info['raw_no_free'] = $r1 ? pg_fetch_assoc($r1) : 'FALHOU';
        

        
        $r2 = DBselect('SELECT 1 AS b');
        if ($r2 !== false) {
            $row2 = DBfetchArray($r2);
            $info['dbselect'] = $row2 ?: 'SEM LINHA';
        } else {
            $info['dbselect'] = 'DBselect retornou false';
        }

        $this->setResponse(new CControllerResponseData([
            'info' => $info,
        ]));
    }
}
