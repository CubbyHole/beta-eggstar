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
     * Vérifie la présence d'une clé d'API valide dans le cas où il ne s'agit pas d'une authentification.
     * De fait, on ne peut pas demander de clé d'API à quelqu'un qui cherche à s'authentifier.
     * @author Alban Truc
     * @param array $request Tableau contenant la requête.
     * @param array $origin Permet d'identifier l'origine de la requête, pour l'instant inutilisé.
     * @since 19/02/2014
     * @throws Exception
     */

    public function __construct($request, $origin)
    {
        //Appelle le constructeur de la classe API
        parent::__construct($request);

        if($this->endpoint != 'users' || ($this->endpoint == 'users' && $this->method != 'GET'))
        {
            if (!array_key_exists('apiKey', $this->request))

                throw new Exception('No API Key provided');

            else if ($this->verifyKey($this->request['apiKey']) !== TRUE)

                throw new Exception('Invalid API Key');
        }
    }

    /**
     * Vérifie la validité d'une clé d'API.
     * @author Alban Truc
     * @param string $apiKey Chaîne contenant la clé d'API
     * @since 19/02/2014
     * @return bool TRUE si la clé est valide.
     */

    private function verifyKey($apiKey)
    {
        $userManager = new UserManager();
        $user = $userManager->findOne(array('apiKey' => $apiKey));

        if(!(isset($user['error']))) //Si un utilisateur avec cette clé existe = la clé fournie est valide
            return TRUE;
        else
            return $user;
    }

    /**
     * Endpoint users
     * @author Alban Truc
     * @since 19/02/2014:
     * Utilisation:
     * localhost doit être remplacé par l'url du serveur.
     *  AUTHENTIFICATION:
     *      GET http://localhost:8080/eggstar/v2/users?email=125637@supinfo.com&password=zfije5rçf_heofuhf
     *          Ici endpoint = users et il n'y a rien dans args.
     *          Les valeurs d'email et password sont stockées dans le tableau request.
     *          Il faut envoyer le password déjà chiffré.
     * L'inscritpion retourne les informations de l'utilisateur, son compte et son refplan;
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
        return 0;
    }

    protected function elements()
    {
        if($this->method == 'GET')
        {
            //cas de demande de récupération des éléments d'un utilisateur
            if(isset($this->request['idUser']) && isset($this->request{'isOwner'}))
            {
                $idUser = $this->request['idUser'];
                $isOwner = $this->request['isOwner'];
                $token = $this->request['apiKey'];

                $criteria = array(
                    '_id' => new MongoId($idUser),
                    'apiKey' => $token
                );

                $userManager = new UserManager();
                $user = $userManager->findOne($criteria);

                if(!(array_key_exists('error', $user)))
                {
                    $elementManager = new ElementManager();
                    $elements = $elementManager->returnElementsDetails($idUser, $isOwner);
                    return $elements;
                }
                else return array('error' => 'You cannot process another user\'s data');
            }
        }
        return 0;
    }
} 