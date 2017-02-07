<?php
// src/Stsbl/MailRedirectionBundle/Form/Type/GroupRecipientType.php
namespace Stsbl\MailRedirectionBundle\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use IServ\CoreBundle\Service\Config;
use Stsbl\MailRedirectionBundle\Form\DataTransformer\UserToRfc822Transformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/*
 * The MIT License
 *
 * Copyright 2017 Felix Jacobi.
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
 * Field for entering user recipients for a mail alias
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @licnese MIT license <https://opensource.org/licenses/MIT>
 */
class UserRecipientType extends AbstractType 
{
    /**
     * @var Config
     */
    private $config;
    
    /**
     * @var ObjectManager
     */
    private $om;
    
    /**
     * The constructor
     * 
     * @param Config $config
     */
    public function __construct(Config $config, ObjectManager $om) 
    {
        $this->config = $config;
        $this->om = $om;
    }
    
    /**
     * @see \Symfony\Component\Form\AbstractType::buildForm()
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) 
    {
        $transformer = new UserToRfc822Transformer($this->config, $this->om);
        
        $builder->add('userRecipient', TextType::class, [
            'label' => false,
            'attr' => [
                'class' => 'mail-aliases-recipient-user-autocomplete',
                'placeholder' => _('Enter a search term...')
            ]
        ]);
        
        $builder->addModelTransformer($transformer);
    }
    
    /**
     * @see \Symfony\Component\Form\AbstractType::configureOptions()
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
           'data_class' => 'Stsbl\MailRedirectionBundle\Entity\UserRecipient' 
        ]);
    }
    
    /**
     * @see \Symfony\Component\Form\FormTypeInterface::getName()
     */
    public function getBlockPrefix()
    {
        return 'mailredirection_userrecipient';
    }
}
