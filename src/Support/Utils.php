<?php
/**
 * Created by PhpStorm.
 * User: alaminahmed
 * Date: 2019-03-18
 * Time: 10:30
 */

namespace W3\Support;


class Utils
{

    /**
     *
     * @return string
     */
    static public function client_ip() : string
    {
        $client  = @$_SERVER['HTTP_CLIENT_IP'];
        $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        $remote  = $_SERVER['REMOTE_ADDR'];

        if(filter_var($client, FILTER_VALIDATE_IP)) {
            $ip = $client;
        } elseif(filter_var($forward, FILTER_VALIDATE_IP)) {
            $ip = $forward;
        } else {
            $ip = $remote;
        }

        return $ip;
    }
}

