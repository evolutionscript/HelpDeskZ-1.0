<?php

namespace Tomaj\ImapMailDownloader;

/**
 * Class Email
 * @package Tomaj\ImapMailDownloader
 */
class Email
{
    private $from;

    private $to;

    private $date;

    private $messageId;

    private $references;

    private $inReplyTo;

    private $size;

    private $uid;

    private $msgNo;

    private $recent;

    private $flagged;

    private $answered;

    private $deleted;

    private $seen;

    private $draft;

    private $headers;

    private $body;

    public function __construct($overview = null, $body = null, $headers = null)
    {
        if ($overview !== null) {
            $options = $overview[0];

            $this->from = $options->from;
            $this->to = $options->to;
            $this->date = $options->date;
            $this->messageId = $options->message_id;
            if (isset($options->references)) {
                $this->references = $options->references;
            }
            if (isset($options->in_reply_to)) {
                $this->inReplyTo = $options->in_reply_to;
            }
            $this->size = $options->size;
            $this->uid = $options->uid;
            $this->msgNo = $options->msgno;
            $this->recent = $options->recent;
            $this->flagged = $options->flagged;
            $this->answered = $options->answered;
            $this->deleted = $options->deleted;
            $this->seen = $options->seen;
            $this->draft = $options->draft;
        }

        $this->headers = $headers;
        $this->body = $body;
    }

    /**
     * @return mixed
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @return mixed
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * @return mixed
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @return mixed
     */
    public function getMessageId()
    {
        return $this->messageId;
    }

    /**
     * @return mixed
     */
    public function getReferences()
    {
        return $this->references;
    }

    /**
     * @return mixed
     */
    public function getInReplyTo()
    {
        return $this->inReplyTo;
    }

    /**
     * @return mixed
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @return mixed
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * @return mixed
     */
    public function getMsgNo()
    {
        return $this->msgNo;
    }

    /**
     * @return mixed
     */
    public function getRecent()
    {
        return $this->recent;
    }

    /**
     * @return mixed
     */
    public function getFlagged()
    {
        return $this->flagged;
    }

    /**
     * @return mixed
     */
    public function getAnswered()
    {
        return $this->answered;
    }

    /**
     * @return mixed
     */
    public function getDeleted()
    {
        return $this->deleted;
    }

    /**
     * @return mixed
     */
    public function getSeen()
    {
        return $this->seen;
    }

    /**
     * @return mixed
     */
    public function getDraft()
    {
        return $this->draft;
    }

    /**
     * @return mixed
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @return mixed
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getSource()
    {
        if ($this->headers == null) {
            throw new \Exception("Email was not created with full headers");
        }
        if ($this->body == null) {
            throw new \Exception("Email was not created with a body");
        }

        return $this->headers . $this->body;
    }
}
