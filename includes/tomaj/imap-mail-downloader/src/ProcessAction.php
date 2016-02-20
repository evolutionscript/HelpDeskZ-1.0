<?php

namespace Tomaj\ImapMailDownloader;

/**
 * Class ProcessAction
 * @package Tomaj\ImapMailDownloader
 */
class ProcessAction
{
    /**
     * @const Process action move email to another folder on imap server.
     * @see ProcessAction::move()
     */
    const ACTION_MOVE = 'move';

    /**
     * @const Process action delete email
     * @see ProcessAction::delete()
     */
    const ACTION_DELETE = 'delete';

    /**
     * @const Process action call user supplied callback with
     */
    const ACTION_CALLBACK = 'callback';

    /**
     * @var string
     */
    private $action;

    /**
     * @var string|null
     */
    private $processedFolder;

    /**
     * @var callable|null
     */
    private $callback;


    /**
     * Create a default action and pack additional standard behaviours that can be used in
     * string based override commands
     * @param $action
     * @param null|string $processedFolder
     * @param null|string $callback
     * @return ProcessAction
     * @throws \Exception
     */
    public static function createDefault($action, $processedFolder = null, $callback = null)
    {

        if ($action == ProcessAction::ACTION_MOVE and !is_string($processedFolder)) {
            throw new \Exception("Invalid processed folder for action PROCESSED_ACTION_MOVE: {$processedFolder}");
        } elseif ($action == ProcessAction::ACTION_CALLBACK and !is_callable($callback)) {
            throw new \Exception("Invalid process callback for action PROCESSED_ACTION_CALLBACK");
        }

        $processAction = new ProcessAction();
        $processAction->action = $action;
        $processAction->processedFolder = $processedFolder;
        $processAction->callback = $callback;
        return $processAction;
    }

    /**
     * Create action that moves emails to another imap folder
     * @param $folder
     * @return ProcessAction
     */
    public static function move($folder)
    {
        $processAction = new ProcessAction();
        $processAction->action = ProcessAction::ACTION_MOVE;
        $processAction->processedFolder = $folder;
        return $processAction;
    }

    /**
     * Create action that deletes emails
     * @return ProcessAction
     */
    public static function delete()
    {
        $processAction = new ProcessAction();
        $processAction->action = ProcessAction::ACTION_DELETE;
        return $processAction;
    }

    /**
     * Create action that calls user supplied callback
     * The callable is passed two arguments, the imap mailbox resource and the email index, ie the signature is:
     *      void function (Resource $mailbox, int $emailIndex)
     * @param callable $callback
     * @return ProcessAction
     */
    public static function callback($callback)
    {
        $processAction = new ProcessAction();
        $processAction->action = ProcessAction::ACTION_CALLBACK;
        $processAction->callback = $callback;
        return $processAction;
    }

    /**
     * Action getter
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Move action folder target getter
     * @return null|string
     */
    public function getProcessedFolder()
    {
        return $this->processedFolder;
    }

    /**
     * Callback getter
     * @return callable|null
     */
    public function getCallback()
    {
        return $this->callback;
    }
}
