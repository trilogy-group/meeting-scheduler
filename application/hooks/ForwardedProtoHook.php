<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ForwardedProtoHook {

    public function handleForwardedProto() {
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            $_SERVER['HTTPS'] = 'on';
        }
    }
}

