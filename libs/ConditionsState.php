<?php

namespace YesWiki\Lms;

class ConditionsState implements \JsonSerializable
{
    protected $conditionsMet;
    protected $errorStatus;
    protected $reactionsNeeded;
    protected $messages;
    protected $url;

    /**
     * construct
     */
    public function __construct()
    {
        $this->conditionsMet = true;
        $this->errorStatus = false;
        $this->reactionsNeeded = false;
        $this->messages = [];
        $this->url = null;
    }

    /**
     * getConditionsMet
     * @return bool
     */
    public function getConditionsMet(): bool
    {
        return $this->conditionsMet;
    }

    /**
     * getErrorStatus
     * @return bool
     */
    public function getErrorStatus(): bool
    {
        return $this->errorStatus;
    }

    /**
     * getReactionsNeeded
     * @return bool
     */
    public function getReactionsNeeded(): bool
    {
        return $this->reactionsNeeded;
    }

    /**
     * set Error
     */
    public function setError()
    {
        $this->errorStatus = true;
        $this->conditionsMet= false;
    }

    /**
     * set Reactions needed
     */
    public function setReactionsNeeded()
    {
        $this->reactionsNeeded = true;
    }

    /**
     * set not ok
     */
    public function setNotOk()
    {
        $this->conditionsMet = false;
    }
    /**
     * set url
     * @param string $url
     */
    public function setURL(string $url)
    {
        $this->url = $url;
    }

    /**
     * add message
     * @param string $message
     */
    public function addMessage(string $message)
    {
        $this->messages[] = $message;
    }

    /**
     * get messages
     * @return array $messages
     */
    public function getMessages():array
    {
        return $this->messages;
    }

    /**
     * get url
     * @return null|string $url
     */
    public function getURL()
    {
        return $this->url;
    }

    /**
     * get formatted messages
     * @return string
     */
    public function getFormattedMessages(): string
    {
        if (empty($this->getMessages())) {
            return '';
        }
        $output = '<ul>'."\n";
        foreach ($this->getMessages() as $message) {
            $output .= '  <li>'.$message."</li>\n";
        }
        $output .= '</ul>';
        return $output;
    }

    public function jsonSerialize()
    {
        return [
            'conditionsMet' => $this->getConditionsMet(),
            'errorStatus' => $this->getErrorStatus(),
            'reactionsNeeded' => $this->getReactionsNeeded()]
            + ($this->getURL() ? ['url' => $this->getURL()]:[])
            + [
            'messages' => $this->getMessages(),
            'formattedMessages' => $this->getFormattedMessages(),
            ];
    }
}
