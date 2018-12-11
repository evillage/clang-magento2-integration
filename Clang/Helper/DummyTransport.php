<?php
/**
 * Mail Transport
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Clang\Clang\Helper;

class DummyTransport implements \Magento\Framework\Mail\TransportInterface
{
    /**
     * NOT Sending a mail using this transport
     *
     * @return void
     * @throws \Magento\Framework\Exception\MailException
     */
    public function sendMessage()
    {
        // Not doing anything here. The sole purpose of the extension is to be able to send all message related data
        // to Clang to send e-mails or other messages with Clang, and not with Magento itself.
    }

    /**
     * Get message
     *
     * @return \Magento\Framework\Mail\MessageInterface
     * @since 101.0.0
     */
    public function getMessage()
    {
    }
}
