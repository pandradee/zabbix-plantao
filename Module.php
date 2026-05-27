<?php declare(strict_types = 1);

namespace Modules\Plantao;

use Zabbix\Core\CModule,
    APP,
    CMenuItem,
    CMenu,
    CWebUser;

class Module extends CModule {

    public function init(): void {
        // Apenas admins e super admins veem o menu
        if (CWebUser::$data['type'] < USER_TYPE_ZABBIX_ADMIN) {
            return;
        }

        $menu = APP::Component()->get('menu.main');

        $menu->insertAfter(_('Reports'),
            (new CMenuItem(_('Plantão')))->setIcon('zi-calendar-check')
                ->setSubMenu((new CMenu())
                    ->add((new CMenuItem(_('Escala')))->setAction('plantao.list'))
                    ->add((new CMenuItem(_('Telefones')))->setAction('phones.list'))
                )
        );
    }

    public function onInstall(): void {
        DBexecute(
            'CREATE TABLE IF NOT EXISTS module_plantao_phones (' .
            'userid BIGINT NOT NULL,' .
            'phone VARCHAR(100) NOT NULL DEFAULT \'\',' .
            'PRIMARY KEY (userid)' .
            ')'
        );

        DBexecute(
            'CREATE TABLE IF NOT EXISTS module_plantao_schedule (' .
            'scheduleid BIGSERIAL NOT NULL,' .
            'userid BIGINT NOT NULL,' .
            'schedule_date DATE NOT NULL,' .
            'created_by BIGINT NOT NULL DEFAULT 0,' .
            'created_at INTEGER NOT NULL DEFAULT 0,' .
            'PRIMARY KEY (scheduleid),' .
            'UNIQUE (schedule_date)' .
            ')'
        );
    }

    public function onUninstall(): void {
        DBexecute('DROP TABLE IF EXISTS module_plantao_schedule');
        DBexecute('DROP TABLE IF EXISTS module_plantao_phones');
    }
}
