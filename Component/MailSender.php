<?php

/*
 * (c) XM Media Inc. <dhein@xmmedia.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XM\MailManagerBundle\Component;

/**
 * MailSender class.
 * Used to send each email, including rendering the template/view.
 *
 * @author Darryl Hein, XM Media Inc. <dhein@xmmedia.com>
 */
class MailSender
{
    /**
     * @var MailManager
     */
    protected $mailManager;

    /**
     * @var \Twig_TemplateInterface
     */
    protected $template;

    /**
     * @var array
     */
    protected $templateParameters = [];

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
     * @var array
     */
    protected $bccAddresses = [];

    /**
     * The "rendered" email message parts.
     *
     * @var array
     */
    protected $messageParts = [
        'subject' => null,
        'body_html' => null,
        'body_text' => null,
    ];

    /**
     * Constructor.
     *
     * @param MailManager $mailManager
     */
    public function __construct(MailManager $mailManager)
    {
        $this->mailManager = $mailManager;
    }

    /**
     * Sets the from email & name.
     *
     * @param string $fromEmail The from email address.
     * @param string $fromName  The from name.
     * @return MailSender
     */
    public function setFrom($fromEmail, $fromName = null)
    {
        $translator = $this->mailManager->getTranslator();

        $this->fromEmail = $translator->trans($fromEmail);
        if (null !== $fromName) {
            $this->fromName = $translator->trans($fromName);
        }

        return $this;
    }

    /**
     * Sets the reply to address for the email.
     *
     * @param string $replyToEmail
     * @return MailSender
     */
    public function setReplyTo($replyToEmail)
    {
        $this->replyToEmail = $this->mailManager->getTranslator()
            ->trans($replyToEmail);

        return $this;
    }

    /**
     * Renders the template into the message parts.
     * Email templates are assumed to be in the Mail folder in views.
     * For the email template, if ".html.twig" is included, it will be assumed
     * it's the entire path to the email template, including filename and ext.
     *
     * @param string $template The email template name plus path.
     * @param array $parameters The parameters to be passed to the template.
     * @return MailSender
     */
    public function setTemplate($template, array $parameters = [])
    {
        if (strrpos($template, '.html.twig') === false) {
            $template = 'Mail/'.$template.'.html.twig';
        }

        // @todo loadTemplate is an internal method, so we shouldn't be calling it
        $template = $this->mailManager->getTwig()
            ->loadTemplate($template);
        $parameters = $this->mailManager->getTwig()
            ->mergeGlobals($parameters);

        // render the blocks of the email
        // @todo we can use hasBlock() to see if the template has each of these blocks
        $this->messageParts['subject']   = $template
            ->renderBlock('subject', $parameters);
        $this->messageParts['body_html'] = $template
            ->renderBlock('body_html', $parameters);
        $this->messageParts['body_text'] = $template
            ->renderBlock('body_text', $parameters);

        return $this;
    }

    /**
     * @param $subject
     * @return MailSender
     */
    public function setSubject($subject)
    {
        $this->messageParts['subject'] = trim($subject);

        return $this;
    }

    /**
     * @param $content
     * @return MailSender
     */
    public function setBodyHtml($content)
    {
        $this->messageParts['body_html'] = $content;

        return $this;
    }

    /**
     * @param $content
     * @return MailSender
     */
    public function setBodyText($content)
    {
        $this->messageParts['body_text'] = $content;

        return $this;
    }

    /**
     * Adds a BCC address to the email.
     *
     * @param string $address
     * @param string $name
     * @return MailSender
     */
    public function addBcc($address, $name = null)
    {
        $this->bccAddresses[$email] = $name;

        return $this;
    }

    /**
     * Creates the message and sends it.
     *
     * @param string|array $to The to email address(es).
     * @return int
     * @throws \Exception
     *
     * @see \Swift_Mailer::send()
     */
    public function send($to)
    {
        // will throw exception
        $this->testValidateMessageParts();

        $message = $this->createMessage($to);

        return $this->mailManager->send($message);
    }

    /**
     * Creates the Swift mailer Message object.
     * Sets the subject, from address, to address, reply to (optional),
     * and body (html or plain text).
     *
     * @param string $to
     * @return \Swift_Mime_SimpleMessage
     */
    protected function createMessage($to)
    {
        $message = (new \Swift_Message())
            ->setSubject($this->messageParts['subject'])
            ->setFrom($this->fromEmail, $this->fromName)
            ->setTo($to)
        ;
        if (!empty($this->replyToEmail)) {
            $message->setReplyTo($this->replyToEmail);
        }
        if (!empty($this->bccAddresses)) {
            foreach ($this->bccAddresses as $email => $name) {
                $message->addBcc($email, $name);
            }
        }
        if (!empty($this->messageParts['body_html'])) {
            $message->setBody($this->messageParts['body_html'], 'text/html');
        }
        // don't add if plain text version is empty string when trimmed
        if (trim($this->messageParts['body_text']) != '') {
            $message->addPart($this->messageParts['body_text'], 'text/plain');
        }

        return $message;
    }

    /**
     * Tests if the message is valid.
     * A valid contains a subject and either an HTML or plain text body.
     *
     * @throws \Exception
     */
    protected function testValidateMessageParts()
    {
        $messageParts = $this->messageParts;
        foreach ($messageParts as $key => $value) {
            $messageParts[$key] = trim($value);
        }

        if (empty($messageParts['subject'])) {
            throw new \Exception('Message subject needs to be set');
        }

        if (empty($messageParts['body_html']) && empty($messageParts['body_text'])) {
            throw new \Exception('HTML or plain text message parts needs to be set');
        }
    }
}