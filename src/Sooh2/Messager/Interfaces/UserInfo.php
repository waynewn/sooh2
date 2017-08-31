<?php
namespace Sooh2\Messager\Interfaces;

/**
 * 用于获取用户信息的类
 *
 * @author simon.wang
 */
interface UserInfo {

    /**
     * 获取手机号
     */
    public function getPhone();
    /**
     * 获取Email
     */
    public function getEmail();
    /**
     * 获取用户在系统内部的id（比如站内信使用）
     */
    public function getInnerUserId();
    /**
     * 获取用户在系统外部的id（比如推送使用）
     */
    public function getOuterUserId();
}
