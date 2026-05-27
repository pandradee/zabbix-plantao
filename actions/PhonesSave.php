<?php declare(strict_types = 1);

namespace Modules\Plantao\Actions;

use CController,
    CControllerResponseRedirect,
    CUrl;

class PhonesSave extends CController {

    public function init(): void {
        $this->disableCsrfValidation();
    }

    protected function checkInput(): bool {
        $fields = [
            'userid' => 'required|string',
            'phone'  => 'required|string',
        ];
        return $this->validateInput($fields);
    }

    public function checkPermissions(): bool {
        return ($this->getUserType() >= USER_TYPE_ZABBIX_ADMIN);
    }

    protected function doAction(): void {
        $userid = (int) $this->getInput('userid');
        $phone  = trim($this->getInput('phone'));

        $redirect = (new CUrl('zabbix.php'))
            ->setArgument('action', 'phones.list');

        if (empty($phone)) {
            
            DBexecute('DELETE FROM module_plantao_phones WHERE userid = ' . $userid);
            $redirect->setArgument('success', 'Telefone removido.');
        } else {
            
            $existing = DBfetch(DBselect(
                'SELECT userid FROM module_plantao_phones WHERE userid = ' . $userid
            ));

            if ($existing) {
                DBexecute(
                    'UPDATE module_plantao_phones' .
                    ' SET phone = ' . zbx_dbstr($phone) .
                    ' WHERE userid = ' . $userid
                );
            } else {
                DBexecute(
                    'INSERT INTO module_plantao_phones (userid, phone)' .
                    ' VALUES (' . $userid . ', ' . zbx_dbstr($phone) . ')'
                );
            }
            $redirect->setArgument('success', 'Telefone atualizado.');
        }

        $this->setResponse(new CControllerResponseRedirect($redirect));
    }
}
