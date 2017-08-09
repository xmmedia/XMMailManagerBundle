<?php

/*
 * (c) XM Media Inc. <dhein@xmmedia.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XM\MailManagerBundle\Component;

use Symfony\Component\Translation\TranslatorInterface;

/**
 * MailManager class.
 * This is used as the service and to create the sender object.
 *
 * @author Darryl Hein, XM Media Inc. <dhein@xmmedia.com>
 */
class MailManager
{
    /**
     * @var \Swift_Mailer
     */
    protected $mailer;

    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var string
     */
    protected $fromEmail;

    /**
     * @var string
     */
    protected $fromName;

    /**
     * @var string
     */
    protected $replyToEmail;

    /**
     * MailManager constructor.
     *
     * @param \Swift_Mailer $mailer
     * @param \Twig_Environment $twig
     * @param TranslatorInterface $translator
     */
    public function __construct(
        \Swift_Mailer $mailer,
        \Twig_Environment $twig,
        TranslatorInterface $translator
    ) {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->translator = $translator;
    }

    /**
     * Sets the from email & name.
     *
     * @param string $fromEmail The from email address.
     * @param string $fromName  The from name.
     */
    public function setFrom($fromEmail, $fromName)
    {
        $this->fromEmail = $fromEmail;
        $this->fromName = $fromName;
    }

    /**
     * Sets the reply to address for the email.
     *
     * @param string $replyToEmail
     * @return MailManager
     */
    public function setReplyTo($replyToEmail)
    {
        $this->replyToEmail = $replyToEmail;

        return $this;
    }

    /**
     * Creates a sender instance and sets the from email & name
     * and reply to address.
     *
     * @return MailSender
     */
    public function getSender()
    {
        $sender = $this->createSender();

        $sender->setFrom($this->fromEmail, $this->fromName);

        if ($this->replyToEmail) {
            $sender->setReplyTo($this->replyToEmail);
        }

        return $sender;
    }

    /**
     * Sends an email.
     *
     * @param \Swift_Message $message
     * @return int
     */
    public function send(\Swift_Message $message)
    {
        return $this->mailer->send($message);
    }

    /**
     * Retrieves the translator interface.
     *
     * @return TranslatorInterface
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * Retrieves the twig environment used to render the email template/view.
     *
     * @return \Twig_Environment
     */
    public function getTwig()
    {
        return $this->twig;
    }

    /**
     * Create the mail sender.
     *
     * @return MailSender
     */
    protected function createSender()
    {
        return new MailSender($this);
    }
}