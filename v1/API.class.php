<?php
/**
 * Created by PhpStorm.
 * User: Alban Truc
 * Date: 26/12/13
 * Time: 16:39
 * Classe réalisée avec l'aide de http://coreymaynard.com/blog/creating-a-restful-api-with-php/
 */

abstract class API
{

    //La méthode HTTP de la requête: soit GET, POST, PUT ou DELETE
    protected $method = '';

    //Le Modèle requêté par l'URI. Exemple: /users
    protected $endpoint = '';

    /**
     * Ce qu'il y a en plus après que le endpoint ait été retiré, par exemple un ID.
     * Structure: /<endpoint>/<arg0>/<arg1>/.../<argN>
     * ou /<endpoint>/<arg0>
     */
    protected $args = Array();

    //Contenu des requêtes PUT.
    protected $file = Null;

    /**
     * Constructeur de la classe API.
     * - Autorise les requêtes quelque soit leur origine (Cross-Origin Resource Sharing).
     * - Formate les données de la requête
     * @author Alban Truc
     * @param array $request Tableau contenant la requête.
     * @since 19/02/2014
     * @throws Exception
     */

    public function __construct($request)
    {
        //Les requêtes de toutes origines sont acceptées
        header('Access-Control-Allow-Orgin: *');

        //Tous les types de requêtes sont acceptées
        header('Access-Control-Allow-Methods: *');

        //Les données sont en JSON
        header('Content-Type: application/json; charset=utf-8');

        //Les arguments sont séparés par des /
        $this->args = explode('/', rtrim($request, '/'));

        //Le endpoint correspond au premier argument
        $this->endpoint = array_shift($this->args);

        //Récupère la méthode (GET, POST, PUT, DELETE, ...)
        $this->method = $_SERVER['REQUEST_METHOD'];

        /**
         * Les requêtes de type PUT et DELETE sont "cachées" dans une requête POST
         * par l'utilisation du header HTTP_X_HTTP_METHOD.
         */
        if ($this->method == 'POST' && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER))
        {
            if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE')

                $this->method = 'DELETE';

            else if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'PUT')

                $this->method = 'PUT';

            else throw new Exception('Unexpected Header');

        }

        //Récupération et "nettoyage" des données
        switch($this->method)
        {
            case 'DELETE':
            case 'POST':
                $this->request = $this->_sanitize($_POST);
                break;
            case 'GET':
                $this->request = $this->_sanitize($_GET);
                break;
            case 'PUT':
                $this->request = $this->_sanitize($_GET);
                $this->file = file_get_contents('php://input');
                break;
            default:
                $this->_response('Invalid Method', 405);
                break;
        }
    }

    /**
     * Détermine s'il y a dans la classe qui étend API une méthode pour l'endpoint requêté.
     *  Si oui, appelle la méthode correspondante. Si non, retourne une erreur 404.
     * @author Alban Truc
     * @since 19/02/2014
     * @return string
     */

    public function processAPI()
    {
        if ((int)method_exists($this, $this->endpoint) > 0)

            return $this->_response($this->{$this->endpoint}($this->args));

        else return $this->_response('No Endpoint: '.$this->endpoint.', 404');
    }

    /**
     * Renvoie le statut de la réponse dans les headers et affiche les données encodées en JSON.
     * @author Alban Truc
     * @param array $data Données à retourner au client
     * @param int $status Statut HTTP
     * @since 19/02/2014
     */

    private function _response($data, $status = 200)
    {
        header('Content-Type: application/json; charset=utf-8');
        header('HTTP/1.1 '.$status.' '.$this->_requestStatus($status));

        /** Cf. lien suivant pour les options utilisées:
         * http://www.php.net/manual/en/json.constants.php
         */
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Empêche l'injection de code malicieux.
     * @author http://css-tricks.com/snippets/php/sanitize-database-inputs/
     * @param string $data Données envoyées
     * @since 19/02/2014
     * @return string Données nettoyées
     */

    private function _cleanInput($data)
    {

        $search = array(
                            '@<script[^>]*?>.*?</script>@si',   // Retire code Javascript
                            '@<[\/\!]*?[^<>]*?>@si',            // Retire code HTML
                            '@<style[^>]*?>.*?</style>@siU',    // Retire code CSS
                            '@<![\s\S]*?--[ \t\n\r]*>@'         // Retire les lignes de commentaires multiples
                        );

        $output = preg_replace($search, '', $data);

        return $output;
    }

    /**
     * Pour protéger la base de données. Fait appel à cleanInput($data) si $data n'est pas un tableau.
     * @author Alban Truc
     * @param array|string $data Données envoyées
     * @since 19/02/2014
     * @return array|mixed Données nettoyées
     */

    private function _sanitize($data)
    {
        $clean_input = Array();

        if (is_array($data))
        {
            foreach ($data as $key => $value)
                $clean_input[$key] = $this->_sanitize($value);
        }
        else
        {
            if(get_magic_quotes_gpc())
                $data = trim(stripslashes($data));

            $data = trim(strip_tags($data));
            $clean_input = $this->_cleanInput($data);
        }

        return $clean_input;
    }

    /**
     * @author Alban Truc
     * @param int $code Code de statut HTTP
     * @since 19/02/2014
     * @return string Code "compréhensible" par l'humain
     */

    private function _requestStatus($code)
    {
        $status = array
        (
            100 => 'Continue',
            101 => 'Switching Protocols',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            306 => '(Unused)',
            307 => 'Temporary Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported'
        );

        return ($status[$code]) ? $status[$code] : $status[500];
    }
}