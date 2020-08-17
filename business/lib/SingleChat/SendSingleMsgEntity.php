<?php
class SendSingleMsgEntity
{
    private $send_to_user;
    private $msg_type;
    private $from_user_id;
    private $content;

    /**
     * @return mixed
     */
    public function getSendToUser()
    {
        return $this->send_to_user;
    }

    /**
     * @param mixed $send_to_user
     */
    public function setSendToUser($send_to_user): void
    {
        $this->send_to_user = $send_to_user;
    }

    /**
     * @return mixed
     */
    public function getMsgType()
    {
        return $this->msg_type;
    }

    /**
     * @param mixed $msg_type
     */
    public function setMsgType($msg_type): void
    {
        $this->msg_type = $msg_type;
    }

    /**
     * @return mixed
     */
    public function getFromUserId()
    {
        return $this->from_user_id;
    }

    /**
     * @param mixed $from_user_id
     */
    public function setFromUserId($from_user_id): void
    {
        $this->from_user_id = $from_user_id;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param mixed $content
     */
    public function setContent($content): void
    {
        $this->content = $content;
    }

}
