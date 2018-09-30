<?php
if(!function_exists("ssh_run")) {
    function ssh_run($rsa_key,$username,$host,$command,$base_dir="")
    {
        $key = new \phpseclib\Crypt\RSA();
        $key->loadKey($rsa_key);

        $ssh = new \phpseclib\Net\SSH2($host);

        if (!$ssh->login($username, $key)) {
            return "login failed for $username@$host";
        }

        $prefix = "";
        if($base_dir) {
            $prefix = "cd $base_dir && ";
        }

        $results = [];
        foreach($command as $item_cmd) {
            if(is_array($item_cmd) && count($item_cmd)) {
                $results[] = $ssh->exec($prefix . implode(" && ",$item_cmd));
            } else {
                if($item_cmd) {
                    $results[] = $ssh->exec($prefix . $item_cmd);
                }
            }
        }

        return $results;
    }
}

if(!function_exists('request_post')) {
    function request_post($url,$params)
    {
        $client = new GuzzleHttp\Client();
        $response = $client->request('POST', $url, [
            'form_params' => $params
        ]);

        return $response->getBody()->getContents();
    }
}
