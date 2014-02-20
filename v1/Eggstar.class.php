<?php
/**
 * Created by PhpStorm.
 * User: Alban Truc
 * Date: 26/12/13
 * Time: 16:45
 */
require_once 'API.class.php';
require 'required.php';

class Eggstar extends API
{

    /**
     * Constructeur de la classe Eggstar.
     * Vérifie la présence d'une clé d'API valide dans le cas où le endpoint n'est PAS users.
     * De fait, on ne peut pas demander de clé d'API à quelqu'un qui cherche à s'inscrire ou s'authentifier.
     * @author Alban Truc
     * @param $request Array Tableau contenant la requête.
     * @param $origin Array Permet d'identifier l'origine de la requête, pour l'instant inutilisé.
     * @since 19/02/2014
     */

    public function __construct($request, $origin)
    {
        //Apelle le constructeur de la classe API
        parent::__construct($request);

        if($this->endpoint != 'users')
        {
            if (!array_key_exists('apiKey', $this->request))

                throw new Exception('No API Key provided');

            else if (!$this->verifyKey($this->request['apiKey']))

                throw new Exception('Invalid API Key');

        }
    }

    /**
     * Vérifie la validité d'une clé d'API.
     * @author Alban Truc
     * @param $apiKey String Chaîne contenant la clé d'API
     * @since 19/02/2014
     * @return bool TRUE si la clé est valide.
     */

    private function verifyKey($apiKey)
    {
        //Connexion locale sur le port 27017
        $connection = new MongoClient();

        //Sélection de la base de données et de la collection
        $collection = $connection->nestbox->users;

        //On cherche l'utilisateur qui a l'apiKey renseignée
        $user = $collection->findOne(array('name' => $apiKey));

        if($user) return TRUE;
        else return FALSE;
    }

    /**
     * Endpoint users
     * @author Alban Truc
     * @since 19/02/2014:
     * Utilisation:
     * localhost doit être remplacé par l'url du serveur.
     *  AUTHENTIFICATION:
     *      GET http://localhost/eggstar/v1/users?email=125637@supinfo.com&password=zfije5rçf_heofuhf
     *          Ici endpoint = users et il n'y a rien dans args.
     *          Les valeurs d'email et password sont stockées dans le tableau request.
     *          Il faut envoyer le password déjà chiffré.
     *  INSCRIPTION:
     *      PUT http://localhost/eggstar/v1/users
     *          L'utilisateur à inscrire est envoyé en json dans le "request body".
     *          Les informations à envoyer sont: name, firstName, email et password.
     *          Exemple: {"name":"Trac","firstName":"Alba","email":"1256378@supinfo.com","password":"Sup1nfo#"}
     *          Le mot de passe doit être envoyé déjà chiffré.
     * L'inscritpion et l'authentification retournent les informations de l'utilisateur, son compte et son refplan;
     * le tout en json.
     * Exemple de retour: cf. auth_login_return.json dans le dossier Samples.
     */

    protected function users()
    {
        if ($this->method == 'GET')
        {
            //Cas d'une demande d'authentification
            if(isset($this->request['email']) && isset($this->request['password']))
            {
                $email = $this->request['email'];
                $password = $this->request['password'];

                //Appel de notre méthode d'authentification
                $userManager = new UserManager();
                $user = $userManager->authenticate($email, $password);

                return $user;
            }
        }
        //Cas d'une demande d'inscription
        else if($this->method == 'PUT')
        {
            //L'utilisateur à inscrire se trouve dans cette variable
            if(isset($this->file) && $this->file != '')
            {
                //Le true permet de récupérer un tableau associatif plutôt qu'un objet
                $content = json_decode($this->file, TRUE);

                $userManager = new UserManager();
                $result = $userManager->register(
                                                    $content['name'],
                                                    $content['firstName'],
                                                    $content['email'],
                                                    $content['password']
                                                );

                return $result;
            }
        }
        return 0;
    }
} 