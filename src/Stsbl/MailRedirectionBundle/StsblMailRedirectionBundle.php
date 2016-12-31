<?php
// src/Stsbl/MailRedirectionBundle/StsblMailRedirectionBundle.php
namespace Stsbl\MailRedirectionBundle;

use Stsbl\MailRedirectionBundle\DependencyInjection\StsblMailRedirectionExtension;
use IServ\CoreBundle\Routing\AutoloadRoutingBundleInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
class StsblMailRedirectionBundle extends Bundle implements AutoloadRoutingBundleInterface
{
    public function getContainerExtension()
    {
        return new StsblMailRedirectionExtension();
    }
}
