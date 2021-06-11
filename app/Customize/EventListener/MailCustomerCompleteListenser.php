<?php

namespace Customize\EventListener;

use Customize\Common\Constant;
use Eccube\Event\EventArgs;
use Eccube\Event\EccubeEvents;
use Swift_Message;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MailCustomerCompleteListenser implements EventSubscriberInterface
{

    /**
     * カスタマーコンプリートBccメールを追加
     *
     * @param EventArgs $event
     * @return void
     */
    public function addCustomerCompleteBccMail(EventArgs $event)
    {
        $bccMails = env('MAIL_CUSTOMER_COMPLETE_BCC_MAILS');

        if ($bccMails) {
            $bccMails = \explode(',', $bccMails);
            $bccMails = array_map(function($m) {
                return trim($m);
            }, array_filter($bccMails));
        }

        if (empty($bccMails)) {
            return false;
        }

        /** @var Swift_Message */
        $message = $event->getArgument('message');
        $message->setBcc($bccMails);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            EccubeEvents::MAIL_CUSTOMER_COMPLETE => 'addCustomerCompleteBccMail'
        ];
    }
}
