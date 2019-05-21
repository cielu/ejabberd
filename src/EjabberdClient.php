<?php

namespace Cielu\Ejabberd;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class EjabberdClient {

    protected $client ;

    protected $host ;

    /**
     * Ejabberd constructor.
     * @param $config
     * @throws Exception
     */
    public function __construct($config)
    {
        preg_match('/^(http|https):\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i', $config['baseUri'], $domains);
        // empty domains
        if(empty($domains)){
            return ['status' => 'error', 'message' => 'Invalid baseUri .'] ;
        }
        $this->host = $domains[2] ;
        // new GuzzleHttp client
        $this->client = new Client([
            'base_uri' => $config['baseUri'],
            'verify' => isset($config['verify']) ? $config['verify'] : false,
            'headers' => [
                'Authorization' => $config['authorization'],
                'X-Admin' => true
            ]
        ]);
    }

    /**
     * @param $uri
     * @param $json
     * @return mixed
     */
    public function httpPost($uri, array $json = [])
    {
        try{
            $response = $this->client->request('POST',$uri,[
                'json' => $json
            ])->getBody();
        } catch (ClientException $exception) {
            $response = $exception->getResponse()->getBody()->getContents();
        }
        return json_decode($response, true);
    }

    /**
     * Add an item to a user's roster (supports ODBC)
     * Group can be several groups separated by ; for example: "g1;g2;g3"
     * @param $localuser
     * @param $localserver
     * @param $user
     * @param $nick
     * @param $group
     * @param $subs
     * @return mixed
     */
    public function addRosteritem($localuser, $localserver, $user, $nick, $group, $subs)
    {
        return $this->httpPost('/api/add_rosteritem',[
            [
                'localuser' => $localuser,
                'localserver' => $localserver,
                'user' => $user,
                'server' => $this->host,
                'nick' => $nick,
                'group' => $group,
                'subs' => $subs
            ]
        ]);
    }

    /**
     * Store the database to backup file
     * @param $filePath Full path for the destination backup file
     * @return mixed
     */
    public function backup($filePath)
    {
        return $this->httpPost('/api/backup',[
            "file" => $filePath
        ]);
    }
    # ==============

    /**
     * @param $username
     * @param $password
     * @return mixed
     */
    public function register($username, $password)
    {
        return $this->httpPost('/api/register',[
            "user" => $username,
            "password" => $password ,
            "host" => $this->host,
        ]);
    }

}
