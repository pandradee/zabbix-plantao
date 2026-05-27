<?php declare(strict_types = 1);

namespace Modules\Plantao\Actions;

use CController,
    CControllerResponseData;

class PlantaoList extends CController {

    public function init(): void {
        $this->disableCsrfValidation();
    }

    protected function checkInput(): bool {
        $fields = [
            'month'   => 'int32',
            'year'    => 'int32',
            'success' => 'string',
            'error'   => 'string',
        ];
        return $this->validateInput($fields);
    }

    public function checkPermissions(): bool {
        return ($this->getUserType() >= USER_TYPE_ZABBIX_ADMIN);
    }

    protected function doAction(): void {
        $now   = new \DateTime();
        $month = (int) $this->getInput('month', $now->format('n'));
        $year  = (int) $this->getInput('year', $now->format('Y'));

        if ($month < 1)  { $month = 12; $year--; }
        if ($month > 12) { $month = 1;  $year++; }

        $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $date_start    = sprintf('%04d-%02d-01', $year, $month);
        $date_end      = sprintf('%04d-%02d-%02d', $year, $month, $days_in_month);

        $wisedb_filter =
            ' EXISTS (' .
            '  SELECT 1 FROM users_groups ug' .
            '  JOIN usrgrp g ON g.usrgrpid = ug.usrgrpid' .
            '  WHERE ug.userid = u.userid AND g.name LIKE \'%WISEDB%\'' .
            ' )';

        $sql =
            'WITH phone_map AS (' .
            '  SELECT userid, phone FROM module_plantao_phones' .
            ')' .
            ' SELECT' .
            '  \'sched\' AS row_type,' .
            '  s.schedule_date::text AS ref_date,' .
            '  s.scheduleid::text AS ref_id,' .
            '  u.userid::text AS userid,' .
            '  u.name, u.surname, u.username,' .
            '  COALESCE(pm.phone, \'\') AS phone,' .
            '  COALESCE(s.userid_reserva::text, \'\') AS userid_reserva,' .
            '  COALESCE(ur.name, \'\') AS reserva_name,' .
            '  COALESCE(ur.surname, \'\') AS reserva_surname,' .
            '  COALESCE(pr.phone, \'\') AS reserva_phone' .
            ' FROM module_plantao_schedule s' .
            ' JOIN users u ON u.userid = s.userid' .
            ' LEFT JOIN phone_map pm ON pm.userid = s.userid' .
            ' LEFT JOIN users ur ON ur.userid = s.userid_reserva' .
            ' LEFT JOIN phone_map pr ON pr.userid = s.userid_reserva' .
            ' WHERE s.schedule_date <= ' . zbx_dbstr($date_end) .
            '   AND (s.schedule_date + INTERVAL \'6 days\')::date >= ' . zbx_dbstr($date_start) .
            ' UNION ALL' .
            ' SELECT DISTINCT' .
            '  \'user\' AS row_type,' .
            '  \'\' AS ref_date,' .
            '  \'\' AS ref_id,' .
            '  u.userid::text AS userid,' .
            '  u.name, u.surname, u.username,' .
            '  COALESCE(pm.phone, \'\') AS phone,' .
            '  \'\' AS userid_reserva,' .
            '  \'\' AS reserva_name,' .
            '  \'\' AS reserva_surname,' .
            '  \'\' AS reserva_phone' .
            ' FROM users u' .
            ' LEFT JOIN phone_map pm ON pm.userid = u.userid' .
            ' WHERE ' . $wisedb_filter;

        $week_map = [];
        $users    = [];

        $result = DBselect($sql);
        while ($row = DBfetch($result)) {
            if ($row['row_type'] === 'sched') {
                $week_map[$row['ref_date']] = [
                    'week_start'     => $row['ref_date'],
                    'scheduleid'     => $row['ref_id'],
                    'userid'         => $row['userid'],
                    'name'           => $row['name'],
                    'surname'        => $row['surname'],
                    'username'       => $row['username'],
                    'phone'          => $row['phone'],
                    'userid_reserva' => $row['userid_reserva'],
                    'reserva_name'   => $row['reserva_name'],
                    'reserva_surname'=> $row['reserva_surname'],
                    'reserva_phone'  => $row['reserva_phone'],
                ];
            } else {
                $users[$row['userid']] = $row;
            }
        }

        uasort($users, fn($a, $b) => strcmp($a['name'] . $a['surname'], $b['name'] . $b['surname']));

        $response = new CControllerResponseData([
            'month'         => $month,
            'year'          => $year,
            'days_in_month' => $days_in_month,
            'week_map'      => $week_map,
            'users'         => $users,
            'success'       => $this->getInput('success', ''),
            'error'         => $this->getInput('error', ''),
        ]);
        $response->setTitle('Escala de Plantão');
        $this->setResponse($response);
    }
}
