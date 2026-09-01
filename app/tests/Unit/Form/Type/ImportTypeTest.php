<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Tests\Unit\Form\Type;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Stsbl\IServ\MailRedirection\Form\Type\ImportType;
use Stsbl\IServ\MailRedirection\Model\Import;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;

#[CoversClass(ImportType::class)]
final class ImportTypeTest extends TestCase
{
    public function testConfiguresImportDataClass(): void
    {
        $type = new ImportType($this->createMock(RouterInterface::class));
        $resolver = new OptionsResolver();
        $type->configureOptions($resolver);

        self::assertSame(Import::class, $resolver->resolve()['data_class']);
    }

    public function testBuildsTheRemoteFileImportForm(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router->expects(self::once())->method('generate')->with('admin_mailalias_import')->willReturn('/admin/mailalias/import');
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())->method('setAction')->with('/admin/mailalias/import')->willReturnSelf();
        $builder->expects(self::exactly(3))->method('add')->willReturnSelf();

        (new ImportType($router))->buildForm($builder, []);
    }
}
