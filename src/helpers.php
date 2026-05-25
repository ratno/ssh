<?php
if(!function_exists("ssh_run")) {
    function ssh_run($rsa_key,$username,$host,$command,$base_dir="")
    {
        $key = \phpseclib3\Crypt\RSA::load($rsa_key);

        $port = 22;
        if (str_contains($host, ':')) {
            [$host, $port] = explode(':', $host, 2);
            $port = (int) $port;
        }

        $ssh = new \phpseclib3\Net\SSH2($host, $port);

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
