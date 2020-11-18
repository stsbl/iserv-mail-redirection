<?php

declare(strict_types=1);

namespace Stsbl\MailAliasBundle\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use IServ\Library\Config\Config;
use Stsbl\MailAliasBundle\Form\DataTransformer\GroupToRfc822Transformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/*
 * The MIT License
 *
 * Copyright 2020 Felix Jacobi.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * Field for entering group recipients for a mail alias
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @licnese MIT license <https://opensource.org/licenses/MIT>
 */
class GroupRecipientType extends AbstractType 
{
    /**
     * @var Config
     */
    private $config;
    
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(Config $config, EntityManagerInterface $em)
    {
        $this->config = $config;
        $this->em = $em;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $transformer = new GroupToRfc822Transformer($this->config, $this->em);
        
        $builder->add('groupRecipient', TextType::class, [
            'label' => false,
            'attr' => [
                'class' => 'mail-aliases-recipient-group-autocomplete',
                'placeholder' => _('Enter a search term...')
            ]
        ]);
        
        $builder->addModelTransformer($transformer);
    }
    
    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'mailredirection_grouprecipient';
    }
}
