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
    public function setFrom($fromEmail, $fromName)
    {
        $translator = $this->mailManager->getTranslator();

        $this->fromEmail = $translator->trans($fromEmail);
        $this->fromName = $translator->trans($fromName);

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

        $template = $this->mailManager->getTwig()
            ->loadTemplate($template);

        // render the blocks of the email
        $this->messageParts['subject']  = $template
            ->renderBlock('subject', $parameters);
        $this->messageParts['body_html'] = $template
            ->renderBlock('body_html', $parameters);
        $this->messageParts['body_text'] = $template
            ->renderBlock('body_text', $parameters);

        return $this;
    }

    /**
     * Renders the template, creates the message and sends it.
     *
     * @param string|array $to The to email address(es).
     * @return int
     * @throws \Exception
     *
     * @see \Swift_Mailer::send()
     */
    public function send($to)
    {
        if (!$this->template instanceof \Twig_TemplateInterface) {
            throw new \Exception('The template must be set for sending email');
        }

        $message = \Swift_Message::newInstance()
             ->setSubject($this->messageParts['subject'])
             ->setFrom($this->fromEmail, $this->fromName)
             ->setTo($to)
        ;
        if (!empty($this->replyToEmail)) {
            $message->setReplyTo($this->replyToEmail);
        }
        if (!empty($this->messageParts['body_html'])) {
            $message->setBody($this->messageParts['body_html'], 'text/html');
        }
        if (!empty($this->messageParts['body_text'])) {
            $message->addPart($this->messageParts['body_text'], 'text/plain');
        }

        return $this->mailManager->send($message);
    }
}