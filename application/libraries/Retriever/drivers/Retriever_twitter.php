<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Author:
 *    Vayn a.k.a. VT <vayn@vayn.de>
 *    http://elnode.com
 *
 *    File:             Retriever_twitter.php
 *    Create Date:      2011年03月23日 星期三 04时46分04秒
 */

class Retriever_twitter extends CI_Driver {
    protected $CI;

    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->helper('function');
    }

    public function retrieve_msg($setting) {
        $data = array();
        $api = "http://api.twitter.com/statuses/user_timeline/{$setting['twitter']}.json";

        // Get raw Twitter JSON data
        $ret = process($api);
        $primitive = json_decode($ret, true);

        // The latest tweet
        $data['latest'] = urlparse($primitive[0]['text']);

        // According to amount to filter tweets 
        $data['tweets'] = array();
        for ($i = 0; $i < $setting['amount']; $i++) {
            $data['tweets'][] = $primitive[$i];
        }

        // According to reply to filter tweets 
        if ($setting['reply'] == 'no') {
            $i = 0;
            $tmparr = array();
            foreach ($data['tweets'] as $key) {
                if ($key['in_reply_to_user_id'] != '') {
                    unset($data['tweets'][$i]);
                }
                else {
                    $tmparr[] = $data['tweets'][$i];
                }
                $i++;
            }
            if (empty($tmparr)) {
                $data['tweets'][] = $primitive[0];
            }
            else {
                $data['tweets'] = $tmparr;
            }
        }
        return $data;
    }

    public function retrieve_showimg($user, $uid) {
        $this->CI->config->load('custom');
        $dir = $this->CI->config->item('users_dir');
        $user_dir = $dir.$user.'/';

        $this->CI->load->model('Setting_model', '', True);
        $setting = $this->CI->Setting_model->get_setting($uid);
        $latest = $setting->latest;
        if (empty($latest)) {
            $latest = file_get_contents($dir.'latest');
        }
        $user = $setting->twitter;
        $api = 'http://api.twitter.com/1/users/profile_image/'.$user.'.json?size=normal';
        $ret = process($api);

        if (strpos($ret, 'PNG')) {
            file_put_contents($user_dir.'avatar.png', $ret);
            $format = 'png';
        }
        elseif (strpos($ret, 'GIF89')) {
            file_put_contents($user_dir.'avatar.gif', $ret);
            $format = 'gif';
        }
        else {
            if (@imagecreatefromstring($ret) === False) {
                copy(FCPATH.'/users/avatar.png', $user_dir.'avatar.png');
                $format = 'png';
            }
            else {
                file_put_contents($user_dir.'avatar.jpg', $ret);
                $format = 'jpg';
            }
        }
        $time = date('D M j T Y', time());
        nowIMG($user, $user_dir, $latest, $time, $format);
    }
}

/* End of file Retriever_twitter.php */
