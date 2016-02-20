<?php

namespace Tomaj\ImapMailDownloader;

class MailCriteria
{
    protected $from;

    protected $keyword;

    protected $unseen;

    protected $subject;

    protected $since;

    protected $text;

    public function getSearchString()
    {
        $parts = array();
        if ($this->from) {
            $from = addslashes($this->from);
            $parts[] = "FROM \"{$from}\"";
        }

        if ($this->keyword) {
            $keyword = addslashes($this->keyword);
            $parts[] = "KEYWORD \"{$keyword}\"";
        }

        if ($this->unseen) {
            $parts[] = "UNSEEN";
        }

        if ($this->subject) {
            $subject = addslashes($this->subject);
            $parts[] = "SUBJECT \"{$subject}\"";
        }

        if ($this->since) {
            $since = addslashes($this->since);
            $parts[] = "SINCE \"{$since}\"";
        }

        if ($this->text) {
            $text = addslashes($this->text);
            $parts[] = "TEXT \"{$text}\"";
        }

        if (count($parts)) {
            return implode(' ', $parts);
        }

        return 'ALL';
    }

    public function setFrom($from)
    {
        $this->from = $from;
        return $this;
    }

    public function setKeyword($keyword)
    {
        $this->keyword = $keyword;
        return $this;
    }

    public function setUnseen($unseen)
    {
        $this->unseen = $unseen;
        return $this;
    }

    public function setSince($since)
    {
        $this->since = $since;
        return $this;
    }

    public function setSubject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    public function setText($text)
    {
        $this->text = $text;
        return $this;
    }
}
