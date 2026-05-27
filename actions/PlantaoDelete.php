<?php declare(strict_types = 1);

namespace Modules\Plantao\Actions;

use CController,
    CControllerResponseRedirect,
    CUrl;

class PlantaoDelete extends CController {

    public function init(): void {
        $this->disableCsrfValidation();
    }

    protected function checkInput(): bool {
        $fields = [
            'scheduleid' => 'required|int32',
            'month'      => 'int32',
            'year'       => 'int32',
        ];
        return $this->validateInput($fields);
    }

    public function checkPermissions(): bool {
        return ($this->getUserType() >= USER_TYPE_ZABBIX_ADMIN);
    }

    protected function doAction(): void {
        $scheduleid = (int) $this->getInput('scheduleid');
        $month      = (int) $this->getInput('month', date('n'));
        $year       = (int) $this->getInput('year', date('Y'));

        DBexecute(
            'DELETE FROM module_plantao_schedule WHERE scheduleid = ' . $scheduleid
        );

        $this->setResponse(new CControllerResponseRedirect(
            (new CUrl('zabbix.php'))
                ->setArgument('action', 'plantao.list')
                ->setArgument('month', $month)
                ->setArgument('year', $year)
                ->setArgument('success', 'Plantão removido.')
        ));
    }
}
