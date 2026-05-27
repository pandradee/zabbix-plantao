<?php declare(strict_types = 1);

namespace Modules\Plantao\Actions;

use CController,
    CControllerResponseRedirect,
    CUrl,
    CWebUser;

class PlantaoSave extends CController {

    public function init(): void {
        $this->disableCsrfValidation();
    }

    protected function checkInput(): bool {
        $fields = [
            'schedule_date'    => 'required|string',
            'userid'           => 'required|string',
            'userid_reserva'   => 'string',
            'month'            => 'int32',
            'year'             => 'int32',
        ];
        return $this->validateInput($fields);
    }

    public function checkPermissions(): bool {
        return ($this->getUserType() >= USER_TYPE_ZABBIX_ADMIN);
    }

    protected function doAction(): void {
        $date          = trim($this->getInput('schedule_date'));
        $userid        = (int) $this->getInput('userid');
        $userid_reserva = (int) $this->getInput('userid_reserva', '0');
        $month         = (int) $this->getInput('month', date('n'));
        $year          = (int) $this->getInput('year', date('Y'));

        $redirect = (new CUrl('zabbix.php'))
            ->setArgument('action', 'plantao.list')
            ->setArgument('month', $month)
            ->setArgument('year', $year);

        
        $dt = \DateTime::createFromFormat('Y-m-d', $date);
        if (!$dt || $dt->format('Y-m-d') !== $date) {
            $this->setResponse(new CControllerResponseRedirect(
                (clone $redirect)->setArgument('error', 'Data inválida.')
            ));
            return;
        }

        
        $dow = (int) $dt->format('N'); 
        if ($dow > 1) {
            $dt->modify('-' . ($dow - 1) . ' days');
        }
        $week_monday = $dt->format('Y-m-d');
        $week_sunday = (clone $dt)->modify('+6 days')->format('Y-m-d');

        
        $phone_row = DBfetch(DBselect(
            'SELECT phone FROM module_plantao_phones WHERE userid=' . $userid
        ));

        if (!$phone_row || empty($phone_row['phone'])) {
            $this->setResponse(new CControllerResponseRedirect(
                (clone $redirect)->setArgument('error',
                    'Técnico não possui telefone cadastrado. Acesse Plantão → Telefones.')
            ));
            return;
        }

        $phone = $phone_row['phone'];

        
        $phone_reserva = '';
        if ($userid_reserva > 0) {
            $reserva_row = DBfetch(DBselect(
                'SELECT phone FROM module_plantao_phones WHERE userid=' . $userid_reserva
            ));
            $phone_reserva = $reserva_row ? $reserva_row['phone'] : '';
        }

        
        $existing = DBfetch(DBselect(
            'SELECT scheduleid FROM module_plantao_schedule' .
            ' WHERE schedule_date = ' . zbx_dbstr($week_monday)
        ));

        $reserva_sql = $userid_reserva > 0
            ? ', userid_reserva = ' . $userid_reserva
            : ', userid_reserva = NULL';

        if ($existing) {
            DBexecute(
                'UPDATE module_plantao_schedule' .
                ' SET userid = ' . $userid .
                $reserva_sql .
                ', created_by = ' . (int) CWebUser::$data['userid'] .
                ', created_at = ' . time() .
                ' WHERE scheduleid = ' . (int) $existing['scheduleid']
            );
        } else {
            $reserva_val = $userid_reserva > 0 ? $userid_reserva : 'NULL';
            DBexecute(
                'INSERT INTO module_plantao_schedule (userid, userid_reserva, schedule_date, created_by, created_at)' .
                ' VALUES (' . $userid . ', ' . $reserva_val . ', ' . zbx_dbstr($week_monday) .
                ', ' . (int) CWebUser::$data['userid'] . ', ' . time() . ')'
            );
        }

        
        $today = date('Y-m-d');
        if ($today >= $week_monday && $today <= $week_sunday) {
            $this->updateMediaTypes($phone, $phone_reserva);
            $msg = 'Plantão salvo e media types LIGMEE atualizados! (' . $week_monday . ' a ' . $week_sunday . ')';
        } else {
            $msg = 'Plantão salvo para a semana ' . $week_monday . ' a ' . $week_sunday . '.';
        }

        $this->setResponse(new CControllerResponseRedirect(
            (clone $redirect)->setArgument('success', $msg)
        ));
    }

    private function updateMediaTypes(string $phone, string $phone_reserva): void {
        
        DBexecute(
            'UPDATE media_type_param' .
            ' SET value = ' . zbx_dbstr($phone) .
            ' WHERE mediatypeid IN (102, 105)' .
            " AND name = 'destination_number'"
        );

        
        
        $reserva_num = $phone_reserva !== '' ? $phone_reserva : '+55819XXXXXX';
        DBexecute(
            'UPDATE media_type_param' .
            ' SET value = ' . zbx_dbstr($reserva_num) .
            ' WHERE mediatypeid IN (108, 109)' .
            " AND name = 'destination_number'"
        );
    }
}
