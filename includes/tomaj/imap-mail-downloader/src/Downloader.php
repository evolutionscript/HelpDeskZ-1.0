<?php

namespace Tomaj\ImapMailDownloader;

/**
 * Class Downloader
 * @package Tomaj\ImapMailDownloader
 */
class Downloader
{
    /**
     * @const Fetch email overview
     * @see Downloader::fetch()
     */
    const FETCH_OVERVIEW    = 1;

    /**
     * @const Fetch email full headers part
     * @see Downloader::fetch()
     */
    const FETCH_HEADERS     = 2;

    /**
     * @const Fetch email body
     * @see Downloader::fetch()
     */
    const FETCH_BODY        = 4;

    /**
     * @const Fetch email full headers and body (equal to FETCH_HEADERS | FETCH_BODY
     * @see Downlaoder::fetch()
     */
    const FETCH_SOURCE      = 6;

    /**
     * @var string
     */
    private $host;

    /**
     * @var string
     */
    private $port;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;


    /**
     * @var string
     */
    private $inboxFolder = 'INBOX';

    /**
     * @var ProcessAction
     */
    private $defaultProcessAction;

    /**
     * @var bool
     * @see Downloader::setProcessedFoldersAutomake()
     */
    private $processedFoldersAutomake = true;

    /**
     * @var bool|array
     * @see Downloader::getAlerts()
     */
    private $alerts = false;

    /**
     * @var bool|array
     * @see Downloader::getErrors()
     */
    private $errors = false;


    /**
     * Downloader constructor.
     * @param $host
     * @param $port
     * @param $username
     * @param $password
     * @param null $defaultProcessAction
     * @see ProcessAction
     */
    public function __construct($host, $port, $username, $password, $defaultProcessAction = null)
    {
        if (!extension_loaded('imap')) {
            throw new \Exception('Extension \'imap\' must be loaded');
        }

        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;

        // if no valid default process action was passed, set up a predefined one
        if ($defaultProcessAction !== null and $defaultProcessAction instanceof ProcessAction) {
            $this->defaultProcessAction = $defaultProcessAction;
        } else {
            $this->defaultProcessAction = ProcessAction::move('INBOX/processed');
        }
    }

    /**
     * Set inbox folder
     * @param string $inboxFolder
     * @default "INBOX"
     * @return $this
     */
    public function setInboxFolder($inboxFolder = 'INBOX')
    {
        $this->inboxFolder = $inboxFolder;
        return $this;
    }

    /**
     * @param bool $enabled
     * @return $this
     */
    public function setProcessedFoldersAutomake($enabled)
    {
        $this->processedFoldersAutomake = $enabled;
        return $this;
    }

    /**
     * @param ProcessAction $processAction
     * @return $this
     * @throws \Exception
     */
    public function setDefaultProcessAction(ProcessAction $processAction)
    {
        if ($processAction === null or !($processAction instanceof  ProcessAction)) {
            throw new \Exception('Default processed action is invalid!');
        }
        $this->defaultProcessAction = $processAction;
        return $this;
    }

    /** Get IMAP alerts
     * @return array|bool
     * @see imap_alerts()
     */
    public function getAlerts()
    {
        return $this->alerts;
    }

    /** Get IMAP errors
     * @return array|bool
     * @see imap_errors()
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Fetch and process emails.
     *
     * The $callback has the signature: mixed function (Email $email)
     * Return values can be:
     *      false   do not process
     *      null (no return value specified)
     *              do not process
     *      true    apply default process action (@see Downloader::setDefaultProcessAction())
     *      (string) ProcessAction::ACTION_MOVE
     *              override default process action, move emails using folder provided by default process action
     *      (string) ProcessAction::ACTION_DELETE
     *              override default process action, delete emails
     *      (string) ProcessAction::ACTION_CALLBACK
     *              override default process action, feed email to callback provided by default process action
     *      ProcessAction
     *              override default process action with provided process action
     *
     * @param MailCriteria $criteria    search criteria
     * @param callable $callback        all found emails are passed to this callback
     * @param null|int $fetchParts      null = use default (FETCH_OVERVIEW | FETCH_BODY)
     * @throws \Exception
     * @throws ImapException
     */
    public function fetch(MailCriteria $criteria, $callback, $fetchParts = null)
    {
        $HOST = '{' . $this->host . ':' . $this->port . '}';
        $INBOX = $HOST . $this->inboxFolder;

        if ($fetchParts === null) {
            $fetchParts = self::FETCH_OVERVIEW | self::FETCH_BODY;
        }

        $exception = null;
        $mailbox = null;
        try {
            $mailbox = imap_open($INBOX, $this->username, $this->password);
            if (!$mailbox) {
                throw new ImapException("Cannot connect to imap server: {$HOST}'");
            }


            // if default folder is set, check for its existence
            if ($this->defaultProcessAction->getProcessedFolder() !== null) {
                $this->checkProcessedFolder(
                    $mailbox,
                    $this->defaultProcessAction->getProcessedFolder(),
                    $this->processedFoldersAutomake
                );
            }

            $emails = $this->fetchEmails($mailbox, $criteria);

            if ($emails) {
                foreach ($emails as $emailIndex) {

                    // fetch only wanted parts
                    $overview = $fetchParts & self::FETCH_OVERVIEW ? imap_fetch_overview($mailbox, $emailIndex, 0) : null;
                    $headers = $fetchParts & self::FETCH_HEADERS ? imap_fetchheader($mailbox, $emailIndex, 0) : null;
                    $body = $fetchParts & self::FETCH_BODY ? imap_body($mailbox, $emailIndex) : null;

                    // construct email object with retrieved parts
                    $email = new Email($overview, $body, $headers);

                    // call user supplied callback with given email
                    $processAction = $callback($email);

                    // only process email if action is not strictly false or strictly null
                    if ($processAction !== false and $processAction !== null) {

                        $processAction = $this->normalizeProcessAction($processAction);

                        $this->processEmail($mailbox, $emailIndex, $processAction);
                    }
                }
            }
        } catch (\Exception $e) {
            // exceptions will be thrown at end of method, but to suppress any unwanted output and
            // properly close any imap resource do not throw immediately
            $exception = $e;
        }

        $this->alerts = imap_alerts();
        $this->errors = imap_errors();

        if (is_resource($mailbox)) {
            imap_close($mailbox);
        }

        // because finally statements are only supported from PHP5.5+ this is more of a workaround..
        if ($exception !== null) {
            throw $exception;
        }
    }

    /** Checks the existence of a folder on the imap server, and optionally creates it
     * @param $mailbox  Imap mailbox resource
     * @param $processedFolder name of folder to check and optionally create
     * @param bool $automake
     * @throws \Exception
     * @throws ImapException
     */
    private function checkProcessedFolder($mailbox, $processedFolder, $automake = false)
    {
        $HOST = '{' . $this->host . ':' . $this->port . '}';
        $list = imap_getmailboxes($mailbox, $HOST, $processedFolder);
        if (!is_array($list) || count($list) == 0) {
            if ($automake) {
                $res = imap_createmailbox($mailbox, $HOST . $processedFolder);
                if (!$res) {
                    throw new ImapException("Failed to create imap folder '{$processedFolder}'");
                }
            } else {
                throw new \Exception("You need to create imap folder '{$processedFolder}'");
            }
        }
    }

    /** Returns list of email indices that match the search criteria
     * @param $mailbox Imap mailbox resource
     * @param $criteria
     * @return array|bool
     */
    private function fetchEmails($mailbox, $criteria)
    {
        $emails = imap_search($mailbox, $criteria->getSearchString());
        if (!$emails) {
            return false;
        }
        rsort($emails);
        return $emails;
    }

    /**
     * Attempts to resolve any process action into actual ProcessAction instances
     * @param mixed $processAction
     * @return false|ProcessAction
     * @throws \Exception
     */
    private function normalizeProcessAction($processAction)
    {
        // apply default process action if passed true
        if (is_bool($processAction) and $processAction) {
            return $this->defaultProcessAction;
        }
        // leave actual ProcessAction object as is
        if ($processAction instanceof ProcessAction) {
            return $processAction;
        }
        // turn pure callables into corresponding ProcessAction instances
        if (is_callable($processAction)) {
            return ProcessAction::callback($processAction);
        }
        // attempt to turn string actions into appropriate ProcessAction instances
        // only the predefined actions are allowed.
        if (is_string($processAction)) {
            switch ($processAction) {
                case ProcessAction::ACTION_MOVE:
                    return ProcessAction::move($this->defaultProcessAction->getProcessedFolder());

                case ProcessAction::ACTION_DELETE:
                    return ProcessAction::delete();

                case ProcessAction::ACTION_CALLBACK:
                    return ProcessAction::callback($this->$this->defaultProcessedAction->getCallback());

                default:
                    throw new \Exception("Unexpected process action: {$processAction}");
            }
        }

        throw new \Exception("Invalid process action: ".strval($processAction));
    }

    /**
     * Apply given process action to email
     *
     * @param $mailbox
     * @param $emailIndex
     * @param ProcessAction $processAction
     * @throws \Exception
     */
    private function processEmail($mailbox, $emailIndex, ProcessAction $processAction)
    {
        switch ($processAction->getAction()) {
            case ProcessAction::ACTION_MOVE:
                $this->checkProcessedFolder(
                    $mailbox,
                    $processAction->getProcessedFolder(),
                    $this->processedFoldersAutomake
                );
                $res = imap_mail_move($mailbox, $emailIndex, $processAction->getProcessedFolder());
                if (!$res) {
                    throw new ImapException("Unexpected error: Cannot move email to ".$processAction->getProcessedFolder());
                    break;
                }
                break;

            case ProcessAction::ACTION_DELETE:
                $res = imap_delete($mailbox, $emailIndex);
                if (!$res) {
                    throw new ImapException("Unexpected error: Cannot delete email.");
                }
                break;

            case ProcessAction::ACTION_CALLBACK:
                call_user_func_array($processAction->getCallback(), array($mailbox, $emailIndex));
                break;
        }
    }
}
