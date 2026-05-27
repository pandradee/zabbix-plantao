<?php declare(strict_types = 1);

namespace Modules\Plantao\Actions;

use CController,
    CControllerResponseData;

class PhonesList extends CController {

    public function init(): void {
        $this->disableCsrfValidation();
    }

    protected function checkInput(): bool {
        $fields = [
            'success' => 'string',
            'error'   => 'string',
        ];
        return $this->validateInput($fields);
    }

    public function checkPermissions(): bool {
        return ($this->getUserType() >= USER_TYPE_ZABBIX_ADMIN);
    }

    protected function doAction(): void {
        $users = [];
        $result = DBselect(
            'SELECT DISTINCT u.userid, u.name, u.surname, u.username,' .
            ' COALESCE(p.phone, \'\') AS phone' .
            ' FROM users u' .
            ' LEFT JOIN module_plantao_phones p ON p.userid = u.userid' .
            ' WHERE EXISTS (' .
            '  SELECT 1 FROM users_groups ug' .
            '  JOIN usrgrp g ON g.usrgrpid = ug.usrgrpid' .
            '  WHERE ug.userid = u.userid AND g.name LIKE \'%WISEDB%\'' .
            ' )' .
            ' ORDER BY u.name, u.surname'
        );
        while ($row = DBfetch($result)) {
            $users[] = $row;
        }

        $response = new CControllerResponseData([
            'users'   => $users,
            'success' => $this->getInput('success', ''),
            'error'   => $this->getInput('error', ''),
        ]);
        $response->setTitle('Telefones de Plantão');
        $this->setResponse($response);
    }
}
