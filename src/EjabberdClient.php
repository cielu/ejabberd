<?php

namespace Cielu\Ejabberd;

use Exception;
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
            'verify' => $config['verify'] ?? false,
            'headers' => [
                'Authorization' => $config['authorization'],
                'X-Admin' => true
            ]
        ]);
    }

    /**
     * @param $uri
     * @param array $json
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
        $result = json_decode($response, true);
        # if array
        if (is_array($result) && isset($result['status']) && $result['status'] == 'error'){
            return $result ;
        }
        return [ 'status' => 'success' , 'ejabberd' => $result];
    }

    /**
     * Add an item to a user's roster (supports ODBC)
     * Group can be several groups separated by ; for example: "g1;g2;g3"
     * @param string $localuser User name
     * @param string $user
     * @param string|null $nickname
     * @param string $group group like: 'family','friend','job','etc'
     * @param string $subs none | from | to | both
     * @param $localserver Server name
     * @param $server Contact server name
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function addRosterItem(string $localuser,string $user,string $nickname = '', $group = '', $subs = 'both',$localserver = null,$server = null)
    {
        return $this->httpPost('/api/add_rosteritem',[
            'localuser' => $localuser ,
            'user' => $user ,
            'nick' => $nickname ,
            'subs' => $subs ,
            'group' => $group ,
            'server' => $server ?? $this->host ,
            'localserver' => $localserver ?? $this->host
        ]);
    }

    /**
     * Store the database to backup file
     * @param string $filePath Full path for the destination backup file
     * @return mixed Raw result string
     */
    public function backup(string $filePath)
    {
        return $this->httpPost('/api/backup',[
            "file" => $filePath
        ]);
    }

    /**
     * Ban an account: kick sessions and set random password
     * @param string $localuser User name to ban
     * @param string|null $reason
     * @param null $host
     * @return mixed  Status code (0 on success, 1 otherwise)
     */
    public function banAccount(string $localuser,string $reason = null,$host = null)
    {
        return $this->httpPost('/api/ban_account',[
            "user" => $localuser,
            "reason" => $reason,
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * Change the password of an account
     * @param string $user User name
     * @param string $newPass
     * @param null $host
     * @return mixed  Status code (0 on success, 1 otherwise)
     */
    public function changePassword(string $user,string $newPass,$host = null)
    {
        return $this->httpPost('/api/change_password',[
            "user" => $user,
            "newpass" => $newPass,
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * Change an option in a MUC room
     * @param string $roomName Room name
     * @param string $option Option name
     * @param string $value Value to assign
     * @param null $service MUC service
     * @return mixed  Status code (0 on success, 1 otherwise)
     */
    public function changeRoomOption(string $roomName,string $option = 'members_only',$value = 'true',$service = null)
    {
        return $this->httpPost('/api/change_room_option',[
            "name" => $roomName,
            "option" => $option,
            "value" => $value,
            "service" => $service ?? 'conference.' . $this->host,
        ]);
    }

    /**
     * Check if an account exists or not
     * @param string $user User name to check
     * @param string|null $host  Server to check
     * @return mixed  Status code (0 on success, 1 otherwise)
     */
    public function checkAccount(string $user,string $host = null)
    {
        return $this->httpPost('/api/check_account',[
            "user" => $user,
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * Check if a password is correct
     * @param string $user User name to check
     * @param string $password
     * @param string|null $host Server to check
     * @return mixed  Status code (0 on success, 1 otherwise)
     */
    public function checkPassword(string $user,string $password,string $host = null)
    {
        return $this->httpPost('/api/check_password',[
            "user" => $user,
            "password" => $password,
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * Check if the password hash is correct
     * Allows hash methods from crypto application
     * @param string $user
     * @param string $passwordhash Password's hash value
     * @param string $hashmethod Name of hash method
     * @param null $host
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function checkPasswordHash(string $user, string $passwordhash, string $hashmethod = 'md5', $host = null)
    {
        return $this->httpPost('/api/check_password_hash',[
            "user" => $user,
            "passwordhash" => $passwordhash,
            "hashmethod" => $hashmethod,
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * Clear database cache on all nodes
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function clearCache()
    {
        return $this->httpPost('/api/clear_cache');
    }

    /**
     * Recompile and reload Erlang source code file
     * file : "/home/me/srcs/ejabberd/mod_example.erl"
     * @param string $file Filename of erlang source file to compile
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function compile(string $file)
    {
        return $this->httpPost('/api/compile',[
            "file" => $file,
        ]);
    }

    /**
     * List all established sessions
     * @return mixed [sessions::string] : List of users sessions
     */
    public function connectedUsers()
    {
        return $this->httpPost('/api/connected_users');
    }

    /**
     * List all established sessions and their information
     * @return mixed [{jid::string, connection::string, ip::string, port::integer, priority::integer, node::string, uptime::integer, status::string, resource::string, statustext::string}]
     */
    public function connectedUsersInfo()
    {
        return $this->httpPost('/api/connected_users_info');
    }

    /**
     * Get the number of established sessions
     * @return mixed integer
     */
    public function connectedUsersNumber()
    {
        return $this->httpPost('/api/connected_users_number');
    }

    /**
     * Get the list of established sessions in a vhost
     * @param null $host
     * @return mixed [sessions::string]
     */
    public function connectedUsersVhost($host = null)
    {
        return $this->httpPost('/api/connected_users_vhost',[
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * Convert the passwords in 'users' ODBC table to SCRAM
     * @param null $host
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function convertToScram($host = null)
    {
        return $this->httpPost('/api/convert_to_scram',[
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * Convert the input file from Erlang to YAML format
     * "in": "/etc/ejabberd/ejabberd.cfg",
     * "out": "/etc/ejabberd/ejabberd.yml"
     * @param string $in Full path to the original configuration file
     * @param string $out And full path to final file
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function convertToYaml(string $in , string $out)
    {
        return $this->httpPost('/api/convert_to_yaml',[
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * Create a MUC room name@service in host
     * @param string $roomName
     * @param string $host Server host
     * @param string $service MUC service
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function createRoom(string $roomName, string $host = null, string $service = null)
    {
        return $this->httpPost('/api/create_room',[
            "name" => $roomName ,
            "host" => $host ?? $this->host,
            "service" => $service ?? 'conference.' . $this->host
        ]);
    }

    /**
     * Create a MUC room name@service in host with given options
     * @param string $roomName
     * @param array $options [["name" => "members_only", "value" => "true"] ]
     * @param string $host null
     * @param string|null $service
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function createRoomWithOpts(string $roomName, array $options,string $host = null , string $service = null)
    {
        return $this->httpPost('/api/create_room_with_opts',[
            "name" => $roomName,
            "options" => $options,
            "service" => $service ?? 'conference.' . $this->host,
            "host" => $host ?? $this->host,
        ]);
    }

    /**
     * Create the rooms indicated in file
     * Provide one room JID per line. Rooms will be created after restart.
     * @param string $file Path to the text file with one room JID per line
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function createRoomsFile(string $file)
    {
        return $this->httpPost('/api/create_rooms_file',[
            "file" => $file,
        ]);
    }

    /**
     * Delete expired offline messages from database
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function deleteExpiredMessages()
    {
        return $this->httpPost('/api/delete_expired_messages');
    }

    /**
     * Delete elements in Mnesia database for a given vhost
     * @param null $host Vhost which content will be deleted in Mnesia database
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function deleteMnesia($host = null)
    {
        return $this->httpPost('/api/delete_mnesia',[
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * Delete MAM messages older than DAYS
     * Valid message TYPEs: "chat", "groupchat", "all".
     * @param string $type Type of messages to delete (chat, groupchat, all)
     * @param int $days Days to keep messages
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function deleteOldMamMessages(string $type = 'all',int $days = 31)
    {
        return $this->httpPost('/api/delete_old_mam_messages',[
            "type" => $type,
            "days" => $days
        ]);
    }

    /**
     * Delete offline messages older than DAYS
     * @param int $days Days to keep messages
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function deleteOldMessages(int $days = 31)
    {
        return $this->httpPost('/api/delete_old_messages',[
            "days" => $days
        ]);
    }

    /**
     * Remove push sessions older than DAYS
     * @param int $days Days to keep messages
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function deleteOldPushSessions(int $days = 31)
    {
        return $this->httpPost('/api/delete_old_push_sessions',[
            "days" => $days
        ]);
    }

    /**
     * Delete users that didn't log in last days, or that never logged
     * To protect admin accounts, configure this for example: access_rules: protect_old_users: - allow: admin - deny: all
     * @param int $days Last login age in days of accounts that should be removed
     * @return mixed Raw result string
     */
    public function deleteOldUsers(int $days = 31)
    {
        return $this->httpPost('/api/delete_old_users',[
            "days" => $days
        ]);
    }

    /**
     * Delete users that didn't log in last days in vhost, or that never logged
     * To protect admin accounts, configure this for example: access_rules: delete_old_users: - deny: admin - allow: all
     * @param int $days Last login age in days of accounts that should be removed
     * @param null $host
     * @return mixed Raw result string
     */
    public function deleteOldUsersVhost(int $days = 31,$host = null)
    {
        return $this->httpPost('/api/delete_old_users_vhost',[
            "days" => $days,
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * Delete an item from a user's roster (supports ODBC)
     * @param string $localuser User name
     * @param string $contactUser Contact user name
     * @param string|null $localServer Server name
     * @param null $server Server name
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function deleteRosterItem(string $localuser, string $contactUser, string $localServer = null, $server = null)
    {
        return $this->httpPost('/api/delete_rosteritem',[
            "localuser" => $localuser,
            "user" => $contactUser,
            "localserver" => $localServer ?? $this->host,
            "server" => $server ?? $this->host
        ]);
    }

    /**
     * Destroy a MUC room
     * @param string $roomName
     * @param string|null $service
     * @return mixed  Status code (0 on success, 1 otherwise)
     */
    public function destroyRoom(string $roomName, string $service = null)
    {
        return $this->httpPost('/api/destroy_room',[
            "name" => $roomName,
            "service" => $service ?? 'conference.' . $this->host
        ]);
    }

    /**
     * Destroy the rooms indicated in file
     * Provide one room JID per line.
     * @param string $filePath Path to the text file with one room JID per line
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function destroyRoomsFile(string $filePath)
    {
        return $this->httpPost('/api/destroy_rooms_file',[
            "file" => $filePath, // "/home/ejabberd/rooms.txt"
        ]);
    }

    /**
     * Dump the database to a text file
     * @param string|null $filePath Full path for the text file
     * @return mixed Raw result string
     */
    public function dump(string $filePath)
    {
        return $this->httpPost('/api/dump',[
            "file" => $filePath, // "/home/ejabberd/rooms.txt"
        ]);
    }

    /**
     * Dump a table to a text file
     * @param string $file Full path for the text file
     * @param string $table Table name
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function dumpTable(string $file, string $table)
    {
        return $this->httpPost('/api/dump_table',[
            "file" => $file,
            "table" => $table
        ]);
    }

    /**
     * Export virtual host information from Mnesia tables to SQL file
     * Configure the modules to use SQL, then call this command.
     * @param string $filePath
     * @param null $host
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function export2sql(string $filePath, $host = null)
    {
        return $this->httpPost('/api/export2sql',[
            "file" => $filePath,
            "host" => $host ?? $this->host,
        ]);
    }

    /**
     * Export data of all users in the server to PIEFXIS files (XEP-0227)
     * @param string $dir Full path to a directory
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function exportPiefxis(string $dir)
    {
        return $this->httpPost('/api/export_piefxis',[
            "dir" => $dir,
        ]);
    }

    /**
     * Export data of users in a host to PIEFXIS files (XEP-0227)
     * @param string $dir Full path to a directory
     * @param string $host Vhost to export
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function exportPiefxisHost(string $dir, $host = null)
    {
        return $this->httpPost('/api/export_piefxis_host',[
            "dir" => $dir,
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * Generates html documentation for ejabberd_commands
     * @param string $filePath Path to file where generated documentation should be stored
     * @param string $regexp Regexp matching names of commands or modules that will be included inside generated document
     * @param string $examples Comma separated list of languages (chosen from java, perl, xmlrpc, json)that will have example invocation include in markdown document
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function genHtmlDocForCommands(string $filePath, string $regexp, string $examples = 'java,json')
    {
        return $this->httpPost('/api/gen_html_doc_for_commands',[
            "file" => $filePath,
            "regexp" => $regexp,
            "examples" => $examples,
        ]);
    }

    /**
     * Generates markdown documentation for ejabberd_commands
     * @param string $filePath Path to file where generated documentation should be stored
     * @param string $regexp Regexp matching names of commands or modules that will be included inside generated document
     * @param string $examples Comma separated list of languages (chosen from java, perl, xmlrpc, json)that will have example invocation include in markdown document
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function genMarkdownDocForCommands(string $filePath, string $regexp, string $examples = 'java,json')
    {
        return $this->httpPost('/api/gen_markdown_doc_for_commands',[
            "file" => $filePath,
            "regexp" => $regexp,
            "examples" => $examples,
        ]);
    }

    /**
     * Gets certificates for all or the specified domains {all|domain1;domain2;...}.
     * @param string $domains Domains for which to acquire a certificate
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function getCertificates(string $domains)
    {
        return $this->httpPost('/api/get_certificates',[
            "domains" => $domains, // all | www.example.com;www.example1.net
        ]);
    }

    /**
     * Get the Erlang cookie of this node
     * @return mixed cookie :: string : Erlang cookie used for authentication by ejabberd
     */
    public function getCookie()
    {
        return $this->httpPost('/api/get_cookie');
    }

    /**
     * Get last activity information
     * Timestamp is UTC and XEP-0082 format, for example: 2017-02-23T22:25:28.063062Z ONLINE
     * @param string $user
     * @param null $host
     * @return mixed last_activity :: {timestamp::string, status::string} : Last activity timestamp and status
     */
    public function getLast(string $user, $host = null)
    {
        return $this->httpPost('/api/get_last',[
            "user" => $user,
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * Get the current loglevel
     * @return mixed leveltuple :: {levelnumber::integer, levelatom::string, leveldesc::string} : Tuple with the log level number, its keyword and description
     */
    public function getLoglevel()
    {
        return $this->httpPost('/api/get_loglevel');
    }

    /**
     * Get the number of unread offline messages
     * @param string $user
     * @param null $server
     * @return mixed Number
     */
    public function getOfflineCount(string $user, $server = null)
    {
        return $this->httpPost('/api/get_offline_count',[
            "user" => $user,
            "server" => $server ?? $this->host
        ]);
    }

    /**
     * Retrieve the resource with highest priority, and its presence (show and status message) for a given user.
     * The 'jid' value contains the user jid with resource. The 'show' value contains the user presence flag. It can take limited values: - available - chat (Free for chat) - away - dnd (Do not disturb) - xa (Not available, extended away) - unavailable (Not connected)
     * 'status' is a free text defined by the user client.
     * @param string $user
     * @param null $server
     * @return mixed {jid::string, show::string, status::string}
     */
    public function getPresence(string $user, $server = null)
    {
        return $this->httpPost('/api/get_presence',[
            "user" => $user,
            "server" => $server ?? $this->host
        ]);
    }

    /**
     * Get affiliation of a user in MUC room
     * @param string $roomName
     * @param string $jid User jid : user@example.com
     * @param $service MUC service
     * @return mixed Affiliation of the user
     */
    public function getRoomAffiliation(string $roomName, string $jid, $service = null)
    {
        return $this->httpPost('/api/get_room_affiliation',[
            "name" => $roomName ,
            "jid" => $jid ,
            "service" => $service ?? 'conference.' . $this->host
        ]);
    }

    /**
     * Get affiliation of a user in MUC room
     * @param string $roomName
     * @param $service MUC service
     * @return mixed [{username::string, domain::string, affiliation::string, reason::string}] : The list of affiliations with username, domain, affiliation and reason
     */
    public function getRoomAffiliations(string $roomName, $service = null)
    {
        return $this->httpPost('/api/get_room_affiliations',[
            "name" => $roomName ,
            "service" => $service ?? 'conference.' . $this->host
        ]);
    }

    /**
     * Get the list of occupants of a MUC room
     * @param string $roomName
     * @param $service MUC service
     * @return mixed  [{jid::string, nick::string, role::string}] : The list of occupants with JID, nick and affiliation
     */
    public function getRoomOccupants(string $roomName,$service = null)
    {
        return $this->httpPost('/api/get_room_occupants',[
            "name" => $roomName ,
            "service" => $service ?? 'conference.' . $this->host
        ]);
    }

    /**
     * Get the number of occupants of a MUC room
     * @param string $roomName
     * @param $service MUC service
     * @return mixed Number of room occupants
     */
    public function getRoomOccupantsNumber(string $roomName,$service = null)
    {
        return $this->httpPost('/api/get_room_occupants_number',[
            "name" => $roomName ,
            "service" => $service ?? 'conference.' . $this->host
        ]);
    }

    /**
     * Get options from a MUC room
     * @param string $roomName
     * @param $service MUC service
     * @return mixed [{name::string, value::string}] : List of room options tuples with name and value
     */
    public function getRoomOptions(string $roomName,$service = null)
    {
        return $this->httpPost('/api/get_room_options',[
            "name" => $roomName ,
            "service" => $service ?? 'conference.' . $this->host
        ]);
    }

    /**
     * Get roster of a local user
     * @param string $user
     * @param null $server
     * @return mixed [{jid::string, nick::string, subscription::string, ask::string, group::string}]
     */
    public function getRoster(string $user, $server = null)
    {
        return $this->httpPost('/api/get_roster',[
            "user" => $user,
            "server" => $server ?? $this->host
        ]);
    }

    /**
     * List subscribers of a MUC conference
     * @param string $roomName
     * @param string $service MUC service
     * @return mixed [jid::string] : The list of users that are subscribed to that room
     */
    public function getSubscribers(string $roomName, $service = null)
    {
        return $this->httpPost('/api/get_subscribers',[
            "name" => $roomName,
            "service" => $service ?? 'conference.' . $this->host
        ]);
    }

    /**
     * Get the list of rooms where this user is occupant
     * @param string $user Username
     * @param null $host Server host
     * @return mixed  [room::string]
     * [
     *   "room1@muc.example.com",
     *   "room2@muc.example.com"
     * ]
     */
    public function getUserRooms(string $user, $host = null)
    {
        return $this->httpPost('/api/get_user_rooms',[
            "user" => $user,
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * Get content from a vCard field
     * Some vcard field names in get/set_vcard are: FN - Full Name NICKNAME - Nickname BDAY - Birthday TITLE - Work: Position ROLE - Work: Role Some vcard field names and subnames in get/set_vcard2 are: N FAMILY - Family name N GIVEN - Given name N MIDDLE - Middle name ADR CTRY - Address: Country ADR LOCALITY - Address: City TEL HOME - Telephone: Home TEL CELL - Telephone: Cellphone TEL WORK - Telephone: Work TEL VOICE - Telephone: Voice EMAIL USERID - E-Mail Address ORG ORGNAME - Work: Company ORG ORGUNIT - Work: Department
     * For a full list of vCard fields check XEP-0054: vcard-temp at
     * @param string $user
     * @param string $nickname
     * @param null $host Server name
     * @return mixed Field content
     * {"content": "User 1"}
     */
    public function getVcard(string $user,string $nickname, $host = null)
    {
        return $this->httpPost('/api/get_vcard',[
            "user" => $user,
            "name" => $nickname,
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * Check if the password hash is correct
     * Allows hash methods from crypto application
     * @param string $user
     * @param string $nickname
     * @param string $subname
     * @param null $host
     * @return mixed Field content
     *  {"content": "Schubert"}
     */
    public function getVcard2(string $user,string $nickname,string $subname, $host = null)
    {
        return $this->httpPost('/api/get_vcard',[
            "user" => $user,
            "name" => $nickname,
            "subname" => $subname,
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * Get multiple contents from a vCard field
     * Some vcard field names and subnames in get/set_vcard2 are: N FAMILY - Family name N GIVEN - Given name N MIDDLE - Middle name ADR CTRY - Address: Country ADR LOCALITY - Address: City TEL HOME - Telephone: Home TEL CELL - Telephone: Cellphone TEL WORK - Telephone: Work TEL VOICE - Telephone: Voice EMAIL USERID - E-Mail Address ORG ORGNAME - Work: Company ORG ORGUNIT - Work: Department
     * Some vcard field names in get/set_vcard are: FN - Full Name NICKNAME - Nickname BDAY - Birthday TITLE - Work: Position ROLE - Work: Role For a full list of vCard fields check XEP-0054
     * @param string $user
     * @param string $nickname
     * @param string $subname
     * @param null $host
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function getVcard2Multi(string $user,string $nickname,string $subname, $host = null)
    {
        return $this->httpPost('/api/get_vcard2_multi',[
            "user" => $user,
            "name" => $nickname,
            "subname" => $subname,
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * Import users data from jabberd14 spool dir
     * @param string $filePath  Full path to the jabberd14 spool directory
     * @return mixed Raw result string
     */
    public function importDir(string $filePath)
    {
        return $this->httpPost('/api/import_dir',[
            "file" => $filePath,  // "/var/lib/ejabberd/jabberd14/"
        ]);
    }

    /**
     * Import user data from jabberd14 spool file
     * @param string $filePath  Full path to the jabberd14 spool file
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function importFile(string $filePath)
    {
        return $this->httpPost('/api/import_file',[
            "file" => $filePath,  // "/var/lib/ejabberd/jabberd14.spool"
        ]);
    }

    /**
     * Import users data from a PIEFXIS file (XEP-0227)
     * @param string $filePath
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function importPiefxis(string $filePath)
    {
        return $this->httpPost('/api/import_piefxis',[
            "file" => $filePath,  // "/var/lib/ejabberd/example.com.xml"
        ]);
    }

    /**
     * Import data from Prosody
     * Note: this method requires ejabberd compiled with optional tools support and package must provide optional luerl dependency.
     * @param string $dir
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function importProsody(string $dir)
    {
        return $this->httpPost('/api/import_prosody',[
            "dir" => $dir,  // "/var/lib/prosody/datadump/"
        ]);
    }

    /**
     * Number of incoming s2s connections on the node
     * @return mixed s2s_incoming :: integer
     * {"s2s_incoming": 1}
     */
    public function incomingS2sNumber()
    {
        return $this->httpPost('/api/incoming_s2s_number');
    }

    /**
     * Install the database from a fallback file
     * @param string $file Full path to the fallback file
     * @return mixed Raw result string
     * "Success"
     */
    public function installFallback(string $file)
    {
        return $this->httpPost('/api/install_fallback',[
            "file" => $file, // "/var/lib/ejabberd/database.fallback"
        ]);
    }

    /**
     * Join this node into the cluster handled by Node
     * @param string $node Nodename of the node to join
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function joinCluster(string $node)
    {
        return $this->httpPost('/api/join_cluster',[
            "node" => $node // "ejabberd1@machine7"
        ]);
    }

    /**
     * Kick a user session
     * @param string $user User name
     * @param string $reason  Reason for closing session
     * @param string $resource User's resource
     * @param null $host  Server name
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function kickSession(string $user, string $resource , string $reason, $host = null)
    {
        return $this->httpPost('/api/kick_session',[
            "user" => $user,
            "resource" => $resource,
            "reason" => $reason,
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * Disconnect user's active sessions
     * @param string $user
     * @param null $host
     * @return mixed Number of resources that were kicked
     *  {"num_resources": 3}
     */
    public function kickUser(string $user, $host = null)
    {
        return $this->httpPost('/api/kick_user',[
            "user" => $user,
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * Remove and shutdown Node from the running cluster
     * This command can be run from any running node of the cluster, even the node to be removed.
     * @param null $node Nodename of the node to kick from the cluster
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function leaveCluster($node = null)
    {
        return $this->httpPost('/api/leave_cluster',[
            "node" => $node ?? "ejabberd@" . $this->host
        ]);
    }

    /**
     * Lists all curently handled certificates and their respective domains in {plain|verbose} format
     * @param string $option
     * @return mixed [certificate::string]
     */
    public function listCertificates(string $option)
    {
        return $this->httpPost('/api/list_certificates',[
            "option" => $option,
        ]);
    }

    /**
     * List nodes that are part of the cluster handled by Node
     * @return mixed [node::string]
     * [
     *    "ejabberd1@machine7",
     *    "ejabberd1@machine8"
     * ]
     */
    public function listCluster()
    {
        return $this->httpPost('/api/list_cluster');
    }

    /**
     * Restore the database from a text file
     * @param string $filePath Full path to the text file
     * @return mixed Status code (0 on success, 1 otherwise)
     * "Success"
     */
    public function load(string $filePath)
    {
        return $this->httpPost('/api/load',[
            "file" => $filePath, // "/var/lib/ejabberd/database.txt"
        ]);
    }

    /**
     * Change the erlang node name in a backup file
     * @param string $oleNodeName Name of the old erlang node
     * @param string $newNodeName Name of the new node
     * @param string $oldBackup Path to old backup file
     * @param string $newBackup Path to the new backup file
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function mnesiaChangeNodename(string $oleNodeName, string $newNodeName, string $oldBackup,string $newBackup)
    {
        return $this->httpPost('/api/mnesia_change_nodename',[
            "oldnodename" => $oleNodeName, // "ejabberd@machine1"
            "newnodename" => $newNodeName, // "ejabberd@machine2"
            "oldbackup" => $oldBackup,  //"/var/lib/ejabberd/old.backup"
            "newbackup" => $newBackup // "/var/lib/ejabberd/new.backup"
        ]);
    }

    /**
     * Check the contributed module repository compliance
     * @param string $module Module name
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function moduleCheck(string $module)
    {
        return $this->httpPost('/api/module_check',[
            "module" => $module,
        ]);
    }

    /**
     * Compile, install and start an available contributed module
     * @param string $module Module name
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function moduleInstall(string $module)
    {
        return $this->httpPost('/api/module_install',[
            "module" => $module,
        ]);
    }

    /**
     * Uninstall a contributed module
     * @param string $module Module name
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function moduleUninstall(string $module)
    {
        return $this->httpPost('/api/module_uninstall',[
            "module" => $module, // "mod_rest"
        ]);
    }

    /**
     * Upgrade the running code of an installed module
     * In practice, this uninstalls and installs the module
     * @param string $module Module name
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function moduleUpgrade(string $module)
    {
        return $this->httpPost('/api/module_upgrade',[
            "module" => $module, // "mod_rest"
        ]);
    }

    /**
     * List the contributed modules available to install
     * @return mixed [{name::string, summary::string}] : List of tuples with module name and description
     *   {
     *      "mod_cron": "Execute scheduled commands",
     *      "mod_rest": "ReST frontend"
     *   }
     */
    public function modulesAvailable()
    {
        return $this->httpPost('/api/modules_available');
    }

    /**
     * List the contributed modules already installed
     * @return mixed [{name::string, summary::string}] : List of tuples with module name and description
     *   {
     *      "mod_cron": "Execute scheduled commands",
     *      "mod_rest": "ReST frontend"
     *   }
     */
    public function modulesInstalled()
    {
        return $this->httpPost('/api/modules_installed');
    }

    /**
     * Update the module source code from Git
     * A connection to Internet is required
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function modulesUpdateSpecs()
    {
        return $this->httpPost('/api/modules_update_specs');
    }

    /**
     * List existing rooms ('global' to get all vhosts)
     * @param null $host  Server domain where the MUC service is, or 'global' for all
     * @return mixed Status code (0 on success, 1 otherwise)
     *  [
     *     "room1@muc.example.com",
     *     "room2@muc.example.com"
     *  ]
     */
    public function mucOnlineRooms($host = null)
    {
        return $this->httpPost('/api/muc_online_rooms',[
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * List existing rooms ('global' to get all vhosts) by regex
     * @param string $regex  Regex pattern for room name
     * @param string $host Server domain where the MUC service is, or 'global' for all
     * @return mixed [{jid::string, public::string, participants::integer}] : List of rooms with summary
     *  [
     *   {
     *       "jid": "room1@muc.example.com",
     *       "public": "true",
     *       "participants": 10
     *   },
     *   {
     *      "jid": "room2@muc.example.com",
     *      "public": "false",
     *      "participants": 10
     *    }
     *  ]
     */
    public function mucOnlineRoomsByRegex(string $regex, $host = null)
    {
        return $this->httpPost('/api/muc_online_rooms_by_regex',[
            "regex" => $regex,
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * Register a nick to a User JID in the MUC service of a server
     * @param string $jid
     * @param string $nick
     * @param null $service
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function mucRegisterNick(string $jid, string $nick, $service = null)
    {
        return $this->httpPost('/api/muc_register_nick',[
            "jid" => $jid,
            "nick" => $nick,
            "serverhost" => $service ?? 'conference.' . $this->host,
        ]);
    }

    /**
     * Unregister the nick registered by that account in the MUC service
     * @param string $jid
     * @param null $service
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function mucUnregisterNick(string $jid,  $service = null)
    {
        return $this->httpPost('/api/muc_unregister_nick',[
            "jid" => $jid,
            "serverhost" => $service ?? 'conference.' . $this->host,
        ]);
    }

    /**
     * Get number of users active in the last days (only Mnesia)
     * @param int $days Number of days to calculate sum
     * @param null $host Name of host to check
     * @return mixed Number of users active on given server in last n days
     * {"users": 123}
     */
    public function numActiveUsers(int $days = 3, $host = null)
    {
        return $this->httpPost('/api/num_active_users',[
            "days" =>  $days,
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * Get the number of resources of a user
     * @param string $user User name
     * @param null $host  Server name
     * @return mixed Number of active resources for a user
     *  {"resources": 5}
     */
    public function numResources(string $user, $host = null)
    {
        return $this->httpPost('/api/num_resources',[
            "user" => $user,
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * Issue an oauth token for the given jid
     * @param string $jid Jid for which issue token
     * @param string $scopes List of scopes to allow, separated by ';'
     * @param int $ttl Time to live of generated token in seconds
     * @return mixed {token::string, scopes::string, expires_in::string}
     */
    public function oauthIssueToken(string $jid, string $scopes,int $ttl = 3600)
    {
        return $this->httpPost('/api/oauth_issue_token',[
            "jid" => $jid,
            "scopes" => $scopes,
            "ttl" => $ttl,
        ]);
    }

    /**
     * List oauth tokens, user, scope, and seconds to expire (only Mnesia)
     * List oauth tokens, their user and scope, and how many seconds remain until expirity
     * @return mixed  [{token::string, user::string, scope::string, expires_in::string}]
     */
    public function oauthListTokens()
    {
        return $this->httpPost('/api/oauth_list_tokens');
    }

    /**
     * Revoke authorization for a token (only Mnesia)
     * @param string $token
     * @return mixed [{token::string, user::string, scope::string, expires_in::string}] : List of remaining tokens
     */
    public function oauthRevokeToken(string $token)
    {
        return $this->httpPost('/api/oauth_revoke_token',[
            "token" => $token
        ]);
    }

    /**
     * Number of outgoing s2s connections on the node
     * @return mixed {"s2s_outgoing": 1}
     */
    public function outgoingS2sNumber()
    {
        return $this->httpPost('/api/outgoing_s2s_number');
    }

    /**
     * Send a IQ set privacy stanza for a local account
     * @param string $localUser
     * @param string $xmlquery
     * @param null $host
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function privacySet(string $localUser, string $xmlquery, $host = null)
    {
        return $this->httpPost('/api/privacy_set',[
            "user" => $localUser,
            "xmlquery" => $xmlquery,
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * Get some information from a user private storage
     * @param string $localUser
     * @param string $element
     * @param string $ns
     * @param null $host
     * @return mixed {"res": "aaaaa"}
     */
    public function privateGet(string $localUser, string $element, string $ns, $host = null)
    {
        return $this->httpPost('/api/private_get',[
            "user" => $localUser,
            "element" => $element,
            "ns" => $ns,
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * Set to the user private storage
     * @param string $localUser
     * @param string $element
     * @param null $host
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function privateSet(string $localUser, string $element, $host = null)
    {
        return $this->httpPost('/api/private_set',[
            "user" => $localUser,
            "element" => $element ,// "<query xmlns='jabber:iq:private'> <storage xmlns='storage:rosternotes'/></query>"
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * List/delete rosteritems that match filter (only Mnesia)
     * Explanation of each argument: - action: what to do with each rosteritem that matches all the filtering options - subs: subscription type - asks: pending subscription - users: the JIDs of the local user - contacts: the JIDs of the contact in the roster
     * Allowed values in the arguments: ACTION = list | delete SUBS = SUB[:SUB]* | any SUB = none | from | to | both ASKS = ASK[:ASK]* | any ASK = none | out | in USERS = JID[:JID]* | any CONTACTS = JID[:JID]* | any JID = characters valid in a JID, and can use the globs: *, ?, ! and [...]
     * This example will list roster items with subscription 'none', 'from' or 'to' that have any ask property, of local users which JID is in the virtual host 'example.org' and that the contact JID is either a bare server name (without user part) or that has a user part and the server part contains the word 'icq': list none:from:to any *@param string $action
     * @param string $subs
     * @param string $asks
     * @param string $users
     * @param string $contacts
     * @return mixed  [{user::string, contact::string}]
     * @example.org :@icq
     */
    public function processRosterItems(string $action, string $subs, string $asks,string $users, string $contacts)
    {
        return $this->httpPost('/api/process_rosteritems',[
            "action" => $action,
            "subs" => $subs,
            "asks" => $asks,
            "users" => $users,
            "contacts" => $contacts,
        ]);
    }

    /**
     * Add all the users to all the users of Host in Group
     * @param string $host Server name
     * @param string $group Group name
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function pushAllToAll(string $group,string $host = null)
    {
        return $this->httpPost('/api/push_alltoall',[
            "group" => $group,
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * Push template roster from file to a user
     * The text file must contain an erlang term: a list of tuples with username, servername, group and nick. Example: [{"user1", "localhost", "Workers", "User 1"}, {"user2", "localhost", "Workers", "User 2"}].
     * @param string $user
     * @param string $file
     * @param null $host
     * @return mixed  Status code (0 on success, 1 otherwise)
     */
    public function pushRoster(string $user, string $file, $host = null)
    {
        return $this->httpPost('/api/push_roster',[
            "user" => $user,
            "file" => $file, // "/home/ejabberd/roster.txt",
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * Push template roster from file to all those users
     * The text file must contain an erlang term: a list of tuples with username, servername, group and nick. Example: [{"user1", "localhost", "Workers", "User 1"}, {"user2", "localhost", "Workers", "User 2"}].
     * @param string $file
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function pushRosterAll(string $file)
    {
        return $this->httpPost('/api/push_roster_all',[
            "file" => $file, // "/home/ejabberd/roster.txt",
        ]);
    }

    /**
     * @param $username
     * @param $password
     * @param null $host
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function register($username, $password,$host = null)
    {
        return $this->httpPost('/api/register',[
            "user" => $username,
            "password" => $password ,
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * List all registered users in HOST
     * @param null $host
     * @return mixed [username::string] : List of registered accounts usernames
     */
    public function registeredUsers($host = null)
    {
        return $this->httpPost('/api/registered_users',[
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * List all registered vhosts in SERVER
     * @return mixed List of available vhosts
     */
    public function registeredVhosts()
    {
        return $this->httpPost('/api/registered_vhosts');
    }

    /**
     * Reload config file in memory
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function reloadConfig()
    {
        return $this->httpPost('/api/reload_config');
    }

    /**
     * Remove mam archive for user
     * @param string $localUser Username
     * @param null $server
     * @return mixed "MAM archive removed"
     */
    public function removeMamForUser(string $localUser, $server = null)
    {
        return $this->httpPost('/api/remove_mam_for_user',[
            "user" => $localUser,
            "server" => $server ?? $this->host
        ]);
    }

    /**
     * Remove mam archive for user with peer
     * @param string $user
     * @param string $with
     * @param null $server
     * @return mixed "MAM archive removed"
     */
    public function removeMamForUserWithPeer(string $user, string $with, $server = null)
    {
        return $this->httpPost('/api/remove_mam_for_user_with_peer',[
            "user" => $user,
            "with" => $with,
            "server" => $server ?? $this->host
        ]);
    }

    /**
     * Renews all certificates that are close to expiring
     * @return mixed {"certificates": "aaaaa"}
     */
    public function renewCertificates()
    {
        return $this->httpPost('/api/renew_certificates');
    }

    /**
     * Reopen the log files
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function reopenLog()
    {
        return $this->httpPost('/api/reopen_log');
    }

    /**
     * Resource string of a session number
     * @param string $user
     * @param int $num ID of resource to return
     * @param null $host
     * @return mixed {"resource": "Psi"}
     */
    public function resourceNum(string $user, int $num, $host = null)
    {
        return $this->httpPost('/api/resource_num',[
            "user" => $user,
            "num" => $num,
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * Restart ejabberd gracefully
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function restart()
    {
        return $this->httpPost('/api/restart');
    }

    /**
     * Stop an ejabberd module, reload code and start
     * @param string $module module
     * @param null $host Server name
     * @return mixed Returns integer code:
     * 0: code reloaded, module restarted
     * 1: error: module not loaded
     * 2: code not reloaded, but module restarted
     */
    public function restartModule(string $module, $host = null)
    {
        return $this->httpPost('/api/restart_module',[
            "module" => $module,
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * Restore the database from backup file
     * @param string $file Full path to the backup file
     * @return mixed Raw result string
     * "Success"
     */
    public function restore(string $file)
    {
        return $this->httpPost('/api/restore',[
            "file" => $file // "/var/lib/ejabberd/database.backup"
        ]);
    }

    /**
     * Revokes the selected certificate
     * @param string $domain_or_file The domain or file (in pem format) of the certificate in question {domain:Domain | file:File}
     * @return mixed  Raw result string
     */
    public function revokeCertificate(string $domain_or_file)
    {
        return $this->httpPost('/api/revoke_certificate',[
            "domain_or_file" => $domain_or_file,
        ]);
    }

    /**
     * Destroy the rooms that are unused for many days in host
     * @param int $days
     * @param null $host
     * @return mixed List of unused rooms that has been destroyed
     */
    public function roomsUnusedDestroy(int $days = 31, $host = null)
    {
        return $this->httpPost('/api/rooms_unused_destroy',[
            "days" => $days,
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * List the rooms that are unused for many days in host
     * @param int $days
     * @param null $host
     * @return mixed List of unused rooms
     */
    public function roomsUnusedList(int $days = 31, $host = null)
    {
        return $this->httpPost('/api/rooms_unused_list',[
            "days" => $days,
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * Rotate the log files
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function rotateLog()
    {
        return $this->httpPost('/api/rotate_log');
    }

    /**
     * Send a direct invitation to several destinations
     * Password and Message can also be: none. Users JIDs are separated with :
     * @param string $roomName
     * @param string $users
     * @param string $password
     * @param string $reason
     * @param null $service
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function sendDirectInvitation(string $roomName, string $users, string $password = '', $reason = '',$service = null)
    {
        return $this->httpPost('/api/send_direct_invitation',[
            "name" => $roomName,
            "users" => $users, //"user2@localhost:user3@example.com"
            "password" => $password,
            "reason" => $reason,
            "service" => $service ?? 'conference.' . $this->host,
        ]);
    }

    /**
     * Send a message to a local or remote bare of full JID
     * @param string $type Message type: normal, chat, headline
     * @param string $from
     * @param string $to
     * @param string $subject
     * @param string $body
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function sendMessage(string $from, string $to,string $subject,string $body,string $type = 'chat')
    {
        return $this->httpPost('/api/send_message',[
            "from" => $from,
            "to" => $to,
            "subject" => $subject,
            "body" => $body,
            "type" => $type,
        ]);
    }

    /**
     * Send a stanza; provide From JID and valid To JID
     * @param string $from
     * @param string $to
     * @param string $stanza
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function sendStanza(string $from, string $to, string $stanza)
    {
        return $this->httpPost('/api/send_stanza',[
            "from" => $from,
            "to" => $to,
            "stanza" => $stanza, // "<message><ext attr='value'/></message>"
        ]);
    }

    /**
     * Send a stanza as if sent from a c2s session
     * @param string $localUser
     * @param string $stanza
     * @param string $resource
     * @param null $host
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function sendStanzaC2s(string $localUser, string $stanza, string $resource, $host = null)
    {
        return $this->httpPost('/api/send_stanza_c2s',[
            "user" => $localUser,
            "stanza" => $stanza, // "<message to='user1@localhost'><ext attr='value'/></message>"
            "resource" => $resource, // "bot"
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * Set last activity information
     * Timestamp is the seconds since 1970-01-01 00:00:00 UTC, for example: date +%s
     * @param string $user
     * @param string $timestamp Number of seconds since epoch
     * @param string $status
     * @param null $host
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function setLast(string $user, string $timestamp, string $status, $host = null)
    {
        return $this->httpPost('/api/set_last',[
            "user" => $user,
            "timestamp" => $timestamp,
            "status" => $status, // GoSleeping
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * Set the loglevel (0 to 5)
     * @param int $loglevel  Integer of the desired logging level, between 1 and 5
     * @return mixed The type of logger module used
     * {"logger": "lager"}
     */
    public function setLogLevel(int $loglevel)
    {
        return $this->httpPost('/api/set_loglevel',[
            "loglevel" => $loglevel
        ]);
    }

    /**
     * Set master node of the clustered Mnesia tables
     * If you provide as nodename "self", this node will be set as its own master.
     * @param string $nodeName Name of the erlang node that will be considered master of this node
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function setMaster(string $nodeName)
    {
        return $this->httpPost('/api/set_master',[
            "nodename" => $nodeName,
        ]);
    }

    /**
     * Set nickname in a user's vCard
     * @param string $localUser
     * @param string $nickname
     * @param null $host
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function setNickname(string $localUser, string $nickname, $host = null)
    {
        return $this->httpPost('/api/set_nickname',[
            "user" => $localUser,
            "nickname" => $nickname,
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * Set presence of a session
     * @param string $localUser User name
     * @param string $resource Resource
     * @param string $type Type: available, error, probe
     * @param string $show Show: away, chat, dnd, xa.
     * @param string $status Status text
     * @param string $privority Priority, provide this value as an integer
     * @param null $host
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function setPresence(string $localUser, string $resource, string $type,string $show,string $status,string $privority, $host = null)
    {
        return $this->httpPost('/api/set_presence',[
            "user" => $localUser,
            "resource" => $resource,
            "type" => $type,
            "show" => $show,
            "status" => $status,
            "priority" => $privority,
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * Change an affiliation in a MUC room
     * @param string $jid User JID
     * @param string $roomName
     * @param string $affiliation
     * @param null $service MUC service
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function setRoomAffiliation(string $jid, string $roomName, string $affiliation, $service = null)
    {
        return $this->httpPost('/api/set_room_affiliation',[
            "jid" => $jid,
            "name" => $roomName,
            "affiliation" => $affiliation,
            "service" => $service ?? 'conference.' . $this->host,
        ]);
    }

    /**
     * Set content in a vCard field
     * Some vcard field names in get/set_vcard are: FN - Full Name NICKNAME - Nickname BDAY - Birthday TITLE - Work: Position ROLE - Work: Role Some vcard field names and subnames in get/set_vcard2 are: N FAMILY - Family name N GIVEN - Given name N MIDDLE - Middle name ADR CTRY - Address: Country ADR LOCALITY - Address: City TEL HOME - Telephone: Home TEL CELL - Telephone: Cellphone TEL WORK - Telephone: Work TEL VOICE - Telephone: Voice EMAIL USERID - E-Mail Address ORG ORGNAME - Work: Company ORG ORGUNIT - Work: Department
     * For a full list of vCard fields check XEP-0054: vcard-temp at http://www.xmpp.org/extensions/xep-0054.html
     * @param string $localUser
     * @param string $name
     * @param string $content
     * @param null $host
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function setVcard(string $localUser, string $name, string $content, $host = null)
    {
        return $this->httpPost('/api/set_vcard',[
            "user" => $localUser,
            "name" => $name,
            "content" => $content,
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * Set content in a vCard subfield
     * Some vcard field names and subnames in get/set_vcard2 are: N FAMILY - Family name N GIVEN - Given name N MIDDLE - Middle name ADR CTRY - Address: Country ADR LOCALITY - Address: City TEL HOME - Telephone: Home TEL CELL - Telephone: Cellphone TEL WORK - Telephone: Work TEL VOICE - Telephone: Voice EMAIL USERID - E-Mail Address ORG ORGNAME - Work: Company ORG ORGUNIT - Work: Department
     * Some vcard field names in get/set_vcard are: FN - Full Name NICKNAME - Nickname BDAY - Birthday TITLE - Work: Position ROLE - Work: Role For a full list of vCard fields check XEP-0054: vcard-temp at http://www.xmpp.org/extensions/xep-0054.html
     * @param string $localUser
     * @param string $name
     * @param string $subName
     * @param string $content
     * @param null $host
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function setVcard2(string $localUser, string $name, string $subName,string $content, $host = null)
    {
        return $this->httpPost('/api/set_vcard2',[
            "user" => $localUser,
            "name" => $name,
            "name" => $subName,
            "content" => $content,
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * Set multiple contents in a vCard subfield
     * Some vcard field names and subnames in get/set_vcard2 are: N FAMILY - Family name N GIVEN - Given name N MIDDLE - Middle name ADR CTRY - Address: Country ADR LOCALITY - Address: City TEL HOME - Telephone: Home TEL CELL - Telephone: Cellphone TEL WORK - Telephone: Work TEL VOICE - Telephone: Voice EMAIL USERID - E-Mail Address ORG ORGNAME - Work: Company ORG ORGUNIT - Work: Department
     * Some vcard field names in get/set_vcard are: FN - Full Name NICKNAME - Nickname BDAY - Birthday TITLE - Work: Position ROLE - Work: Role For a full list of vCard fields check XEP-0054: vcard-temp at http://www.xmpp.org/extensions/xep-0054.html
     * @param string $localUser
     * @param string $name
     * @param string $subName
     * @param array $content ['aaa','bbb']
     * @param null $host
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function setVcard2Multi(string $localUser, string $name, string $subName,array $content, $host = null)
    {
        return $this->httpPost('/api/set_vcard2_multi',[
            "user" => $localUser,
            "name" => $name,
            "name" => $subName,
            "content" => $content,
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * Create a Shared Roster Group
     * If you want to specify several group identifiers in the Display argument, put \ " around the argument and separate the identifiers with \ \ n For example: ejabberdctl srg_create group3 myserver.com name desc \"group1\ngroup2\"
     * @param string $group Group identifier
     * @param string $groupName Group name
     * @param string $description Group description
     * @param string $display Groups to display
     * @param null $host
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function srgCreate(string $group, string $groupName, string $description,string $display, $host = null)
    {
        return $this->httpPost('/api/srg_create',[
            "group" => $group,
            "name" => $groupName,
            "description" => $description,
            "display" => $display,
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * Delete a Shared Roster Group
     * @param string $group Group identifier
     * @param null $host Group server name
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function srgDelete(string $group, $host = null)
    {
        return $this->httpPost('/api/srg_delete',[
            "group" => $group,
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * Get info of a Shared Roster Group
     * @param string $group
     * @param null $host
     * @return mixed List of group informations, as key and value
     */
    public function srgGetInfo(string $group, $host = null)
    {
        return $this->httpPost('/api/srg_get_info',[
            "group" => $group,
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * Get members of a Shared Roster Group
     * @param string $group
     * @param null $host
     * @return mixed List of group identifiers
     */
    public function srgGetMembers(string $group, $host = null)
    {
        return $this->httpPost('/api/srg_get_members',[
            "group" => $group,
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * List the Shared Roster Groups in Host
     * @param null $host
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function srgList($host = null)
    {
        return $this->httpPost('/api/srg_list',[
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * Add the JID user@host to the Shared Roster Group
     * @param string $localUser
     * @param string $group
     * @param string $grouphost
     * @param null $host
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function srgUserAdd(string $localUser, string $group, $host = null, string $grouphost = null)
    {
        return $this->httpPost('/api/srg_user_add',[
            "user" => $localUser,
            "group" => $group,
            "host" => $host ?? $this->host,
            "grouphost" => $grouphost ?? $this->host,
        ]);
    }

    /**
     * Delete this JID user@host from the Shared Roster Group
     * @param string $localUser
     * @param string $group
     * @param string|null $grouphost
     * @param null $host
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function srgUserDel(string $localUser, string $group, $host = null, string $grouphost = null)
    {
        return $this->httpPost('/api/srg_user_del',[
            "user" => $localUser,
            "group" => $group,
            "host" => $host ?? $this->host,
            "grouphost" => $grouphost ?? $this->host,
        ]);
    }

    /**
     * Get statistical value: registeredusers onlineusers onlineusersnode uptimeseconds processes
     * @param string $userName
     * @return mixed {"stat": 6}
     */
    public function stats(string $userName)
    {
        return $this->httpPost('/api/stats',[
            "name" => $userName,
        ]);
    }

    /**
     * Get statistical value for this host: registeredusers onlineusers
     * @param string $userName
     * @param null $host
     * @return mixed Integer statistic value
     *  {"stat": 6}
     */
    public function statsHost(string $userName, $host = null)
    {
        return $this->httpPost('/api/stats_host',[
            "name" => $userName,
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * Get status of the ejabberd server
     * @return mixed Raw result string
     * "The node ejabberd@localhost is started with status: startedejabberd X.X is running in that node"
     */
    public function status()
    {
        return $this->httpPost('/api/status');
    }

    /**
     * List of logged users with this status
     * @param string $status
     * @return mixed  [{user::string, host::string, resource::string, priority::integer, status::string}]
     */
    public function statusList(string $status = 'dnd')
    {
        return $this->httpPost('/api/status_list',[
            "status" => $status, // "dnd"
        ]);
    }

    /**
     * List of users logged in host with their statuses
     * @param string $status
     * @param null $host
     * @return mixed  [{user::string, host::string, resource::string, priority::integer, status::string}]
     */
    public function statusListHost(string $status = 'dnd', $host = null)
    {
        return $this->httpPost('/api/status_list_host',[
            "status" => $status, // "dnd"
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * Number of logged users with this status
     * @param string $status Status type to check
     * @return mixed Number of connected sessions with given status type
     * {"users": 23}
     */
    public function statusNum(string $status)
    {
        return $this->httpPost('/api/status_num',[
            "status" => $status,
        ]);
    }

    /**
     * Number of logged users with this status in host
     * @param string $status Status type to check
     * @param null $host Server name
     * @return mixed  Number of connected sessions with given status type
     * {"users": 23}
     */
    public function statusNumHost(string $status = 'dnd', $host = null)
    {
        return $this->httpPost('/api/status_num_host',[
            "status" => $status, // "dnd"
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * Stop ejabberd gracefully
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function stop()
    {
        return $this->httpPost('/api/stop');
    }

    /**
     * Inform users and rooms, wait, and stop the server
     * Provide the delay in seconds, and the announcement quoted, for example: ejabberdctl stop_kindly 60 \"The server will stop in one minute.\"
     * @param string $announcement  Announcement to send, with quotes
     * @param int $delay Seconds to wait
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function stopKindly(string $announcement = "Server will stop now.",int $delay = 60)
    {
        return $this->httpPost('/api/stop_kindly',[
            "announcement" => $announcement,
            "delay" => $delay,
        ]);
    }

    /**
     * Stop all s2s outgoing and incoming connections
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function stopS2sConnections()
    {
        return $this->httpPost('/api/stop_s2s_connections');
    }

    /**
     * Subscribe to a MUC conference
     * @param string $user Full JID, including some resource
     * @param string $nick Password's hash value
     * @param string $roomName
     * @param $nodes
     * @return mixed  The list of nodes that has subscribed
     */
    public function subscribeRoom(string $user, string $nick, string $roomName, $nodes = "urn:xmpp:mucsub:nodes:messages,urn:xmpp:mucsub:nodes:affiliations")
    {
        return $this->httpPost('/api/subscribe_room',[
            "user" => $user, // "tom@localhost/dummy"
            "nick" => $nick,
            "room" => strpos($roomName,'conference') !== false ? $roomName : $roomName . '@conference.' . $this->host,
            "nodes" => $nodes // "urn:xmpp:mucsub:nodes:messages,urn:xmpp:mucsub:nodes:affiliations"
        ]);
    }

    /**
     * Unregister a user
     * @param string $localUser
     * @param null $host
     * @return mixed Raw result string
     * "Success"
     */
    public function unregister(string $localUser, $host = null)
    {
        return $this->httpPost('/api/unregister',[
            "user" => $localUser,
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * Check if the password hash is correct
     * Allows hash methods from crypto application
     * @param string $userJID
     * @param string $roomName the room to subscribe
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function unsubscribeRoom(string $userJID, string $roomName)
    {
        return $this->httpPost('/api/unsubscribe_room',[
            "user" => $userJID, //  "tom@localhost",
            "room" => strpos($roomName,'conference') !== false ? $roomName : $roomName . '@conference.' . $this->host,
        ]);
    }

    /**
     * Update the given module, or use the keyword: all
     * @param string $module
     * @return Raw result string
     * "Success"
     */
    public function update(string $module)
    {
        return $this->httpPost('/api/update',[
            "module" => $module,
        ]);
    }

    /**
     * List modified modules that can be updated
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function updateList()
    {
        return $this->httpPost('/api/update_list');
    }

    /**
     * Convert SQL DB to the new format
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function updateSql()
    {
        return $this->httpPost('/api/update_sql');
    }

    /**
     * List user's connected resources
     * @param string $user User name
     * @param null $host Server name
     * @return mixed Status code (0 on success, 1 otherwise)
     */
    public function userResources(string $user, $host = null)
    {
        return $this->httpPost('/api/user_resources',[
            "user" => $user,
            "host" => $host ?? $this->host
        ]);
    }

    /**
     * Get information about all sessions of a user
     * @param string $user
     * @param null $host
     * @return mixed [{connection::string, ip::string, port::integer, priority::integer, node::string, uptime::integer, status::string, resource::string, statustext::string}]
     */
    public function userSessionsInfo(string $user, $host = null)
    {
        return $this->httpPost('/api/user_sessions_info',[
            "user" => $user,
            "host" => $host ?? $this->host
        ]);
    }

}
