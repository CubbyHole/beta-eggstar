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
                    if(isset($this->request['path']))
                    {
                        $path = $this->request['path'];
                        $elements = $elementManager->returnElementsDetails($idUser, $isOwner, $path);
                    }
                    else $elements = $elementManager->returnElementsDetails($idUser, $isOwner);

                    return $elements;
                }
                else return array('error' => 'You cannot process another user\'s data');
            }
        }
        elseif($this->method == 'POST')
        {
            //emplacement du serveur de fichier
            define('PATH', $_SERVER['DOCUMENT_ROOT'].'/Nestbox');

            /*
             * Le paramètre idUser est commun à tous les types d'actions prises en charge actuellement;
             * à savoir: - téléversement de fichier,
             *           - renommage de fichier/dossier,
             *           - déplacement de fichier/dossier,
             *           - suppression de fichier/dossier
             */
            if(isset($this->request['idUser']) && isset($this->request['path']) && isset($this->request['hash']))
            {
                $idUser = $this->request['idUser'];

                //vérifier si l'utilisateur a les droits nécessaires

                //si c'est le cas
                $path = '/'.$idUser.'/'.$this->request['path'];
                $this->request['hash'];

                $serverPath = PATH.$path;
                echo "omidf";
                if(isset($_FILES['uploadFile']))
                {
                    $file = $_FILES['uploadFile'];
                    var_dump($file);
                    ini_set("memory_limit",'0');
                    echo $this->file_get_size($file['tmp_name']);
                }

            }
        }
        return 0;
    }

    function file_get_size($file) {
        //open file
        $fh = fopen($file, "r");
        //declare some variables
        $size = "0";
        $char = "";
        //set file pointer to 0; I'm a little bit paranoid, you can remove this
        fseek($fh, 0, SEEK_SET);
        //set multiplicator to zero
        $count = 0;
        while (true) {
            //jump 1 MB forward in file
            fseek($fh, 1048576, SEEK_CUR);
            //check if we actually left the file
            if (($char = fgetc($fh)) !== false) {
                //if not, go on
                $count ++;
            } else {
                //else jump back where we were before leaving and exit loop
                fseek($fh, -1048576, SEEK_CUR);
                break;
            }
        }
        //we could make $count jumps, so the file is at least $count * 1.000001 MB large
        //1048577 because we jump 1 MB and fgetc goes 1 B forward too
        $size = bcmul("1048577", $count);
        //now count the last few bytes; they're always less than 1048576 so it's quite fast
        $fine = 0;
        while(false !== ($char = fgetc($fh))) {
            $fine ++;
        }
        //and add them
        $size = bcadd($size, $fine);
        fclose($fh);
        return $size;
    }
} 