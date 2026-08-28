<?php

declare(strict_types=1);

use IServ\Bundle\AdminIntegration\Config\MenuConfigurator;
use IServ\Bundle\AdminIntegration\Config\MenuIcon;
use IServ\Bundle\AdminIntegration\Menu\Domain\AdminPage;

return static function (MenuConfigurator $config): void {
    $config
        ->get(AdminPage::USERS->id())
        ->add(
            key: 'mail_aliases_admin',
            label: _('Mail aliases'),
            url: '/admin/mailalias',
            icon: new MenuIcon('img/mail-redirection.svg', '/usr/share/iserv/stsbl-iserv-mail-redirection/app/public/static/manifest.json'),
            accessExpr: 'user.hasPrivilege("f5f8da73-2a25-44ec-af7b-3acc8aa7fce4")',
            moduleId: 'stsbl/mail-redirection',
            weight: 1000,
        )
    ;
};
