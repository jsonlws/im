<?php
class MsgToWithdrawEntity
{
    private $msg_id;
    private $send_to_user;

    /**
     * @return mixed
     */
    public function getMsgId()
    {
        return $this->msg_id;
    }

    /**
     * @param mixed $msg_id
     */
    public function setMsgId($msg_id): void
    {
        $this->msg_id = $msg_id;
    }

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

}