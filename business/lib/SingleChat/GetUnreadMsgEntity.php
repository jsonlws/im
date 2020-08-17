<?php

/**
 * 获取未读消息实体类
 * Class GetUnreadMsgEntity
 */
class GetUnreadMsgEntity
{
    private $uid;

    /**
     * @return mixed
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * @param mixed $uid
     */
    public function setUid($uid): void
    {
        $this->uid = $uid;
    }
}
