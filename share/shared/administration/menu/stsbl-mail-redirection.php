<?php

declare(strict_types=1);

use IServ\Bundle\AdminIntegration\Config\MenuConfigurator;
use IServ\Bundle\AdminIntegration\Menu\Domain\AdminPage;

return static function (MenuConfigurator $config): void {
    $config
        ->get(AdminPage::USERS->id())
        ->add(
            key: 'mail_aliases_admin',
            label: _('Mail aliases'),
            url: '/admin/mailalias',
            icon: 'fa-envelope',
            accessExpr: 'user.hasPrivilege("f5f8da73-2a25-44ec-af7b-3acc8aa7fce4")',
            moduleId: 'stsbl/mail-redirection',
        )
    ;
};
