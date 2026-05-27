<?php declare(strict_types = 1);

namespace Modules\Plantao\Actions;

use CController,
    CControllerResponseRedirect,
    CUrl;

class PlantaoApply extends CController {

    public function init(): void {
        $this->disableCsrfValidation();
    }

    protected function checkInput(): bool {
        $fields = [
            'month' => 'int32',
            'year'  => 'int32',
        ];
        return $this->validateInput($fields);
    }

    public function checkPermissions(): bool {
        return ($this->getUserType() >= USER_TYPE_ZABBIX_ADMIN);
    }

    protected function doAction(): void {
        $month = (int) $this->getInput('month', date('n'));
        $year  = (int) $this->getInput('year', date('Y'));
        $today = date('Y-m-d');

        $redirect = (new CUrl('zabbix.php'))
            ->setArgument('action', 'plantao.list')
            ->setArgument('month', $month)
            ->setArgument('year', $year);

        
        $row = DBfetch(DBselect(
            'SELECT s.userid, s.userid_reserva, s.schedule_date::text AS week_start,' .
            ' COALESCE(p.phone, \'\') AS phone,' .
            ' u.name, u.surname' .
            ' FROM module_plantao_schedule s' .
            ' JOIN users u ON u.userid = s.userid' .
            ' LEFT JOIN module_plantao_phones p ON p.userid = s.userid' .
            ' WHERE s.schedule_date <= ' . zbx_dbstr($today) .
            '   AND (s.schedule_date + INTERVAL \'6 days\')::date >= ' . zbx_dbstr($today) .
            ' LIMIT 1'
        ));

        if (!$row) {
            $this->setResponse(new CControllerResponseRedirect(
                (clone $redirect)->setArgument('error',
                    'Nenhum técnico escalado para a semana atual (hoje: ' . $today . ').')
            ));
            return;
        }

        if (empty($row['phone'])) {
            $this->setResponse(new CControllerResponseRedirect(
                (clone $redirect)->setArgument('error',
                    'Técnico ' . $row['name'] . ' ' . $row['surname'] . ' não tem telefone cadastrado.')
            ));
            return;
        }

        
        DBexecute(
            'UPDATE media_type_param' .
            ' SET value = ' . zbx_dbstr($row['phone']) .
            ' WHERE mediatypeid IN (102, 105)' .
            " AND name = 'destination_number'"
        );

        $msg = 'Media types LIGMEE atualizados → ' .
               $row['name'] . ' ' . $row['surname'] . ' (' . $row['phone'] . ')';

        
        
        $reserva_num  = '+55819XXXXXX';
        $reserva_info = ' | Reserva → genérico (' . $reserva_num . ')';

        if (!empty($row['userid_reserva'])) {
            $reserva_row = DBfetch(DBselect(
                'SELECT u.name, u.surname, COALESCE(p.phone, \'\') AS phone' .
                ' FROM users u' .
                ' LEFT JOIN module_plantao_phones p ON p.userid = u.userid' .
                ' WHERE u.userid = ' . (int) $row['userid_reserva']
            ));

            if ($reserva_row && !empty($reserva_row['phone'])) {
                $reserva_num  = $reserva_row['phone'];
                $reserva_info = ' | Reserva → ' .
                                $reserva_row['name'] . ' ' . $reserva_row['surname'] .
                                ' (' . $reserva_row['phone'] . ')';
            }
        }

        DBexecute(
            'UPDATE media_type_param' .
            ' SET value = ' . zbx_dbstr($reserva_num) .
            ' WHERE mediatypeid IN (108, 109)' .
            " AND name = 'destination_number'"
        );

        $msg .= $reserva_info;

        $this->setResponse(new CControllerResponseRedirect(
            (clone $redirect)->setArgument('success', $msg)
        ));
    }
}
