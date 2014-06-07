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
        //Pour s'assurer que les lourds fichiers ne dépassent pas la limitation de mémoire.
        ini_set("memory_limit",'0');

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

                    if(isset($this->request['name']))
                        $elementName = $this->request['name'];
                    else $elementName = NULL;

                    if(isset($this->request['path']))
                        $path = $this->request['path'];
                    else $path = 'all';

                    $elements = $elementManager->returnElementsDetails($idUser, $isOwner, $path, $elementName);

                    return $elements;
                }
                else return array('error' => 'You cannot process another user\'s data');
            }
        }
        elseif($this->method == 'POST')
        {
            $options = array();

            if(isset($this->request['returnImpactedElements']))
                $options['returnImpactedElements'] = $this->request['returnImpactedElements'];

            if(isset($this->request['returnPastedElements']))
                $options['returnPastedElements'] = $this->request['returnPastedElements'];

            if(isset($this->request['keepRights']))
                $options['keepRights'] = $this->request['keepRights'];

            $this->request['options'] = $options;

            return handleActions($this->request);
//            $elementManager = new ElementManager();
//
//            //emplacement du serveur de fichier
//            define('PATH', $_SERVER['DOCUMENT_ROOT'].'/Nestbox');
//
//            if(isset($this->request['idUser']))
//                $idUser = $this->request['idUser'];
//
//            if(isset($this->request['idElement']))
//                $idElement = $this->request['idElement'];
//
//            /*
//             * Correspond au chemin donné en paramètre d'URL.
//             * Peut représenter le chemin indiqué en base ou, dans le cas d'une demande de déplacement,
//             * la destination voulue.
//             */
//            if(isset($this->request['path']))
//            {
//                $pathGiven = $this->request['path'];
//
//                //chemin complet sur le serveur de fichier
//                $fileServerPath = PATH.'/'.$idUser.'/'.$pathGiven;
//
//                //Récupérer l'id de l'élément correspondant au chemin donné
//                $elementDestinationCriteria = array(
//                    'state' => (int)1,
//                    'serverPath' => $pathGiven
//                );
//
//                $destinationElement = $elementManager->find($elementDestinationCriteria);
//                $idDestinationElement = $destinationElement['_id'];
//            }
//
//            /*
//             * idUser est commun à tous les types d'actions prises en charge actuellement;
//             * à savoir: - téléversement de fichier,
//             *           - renommage de fichier/dossier,
//             *           - déplacement de fichier/dossier,
//             *           - suppression de fichier/dossier
//             */
//            if(isset($idUser))
//            {
//                if(isset($idElement)) //tous les cas sauf le téléversement
//                {
//                    /*
//                     * vérifier si l'utilisateur a les droits nécessaires,
//                     * dans les cas suivants :
//                     *  l'utilisateur est propriétaire
//                     *  ou le code de refRight est 11 (lecture et écriture)
//                     */
//                    $element = $elementManager->findById($idElement);
//
//                    if(!array_key_exists('error', $element))
//                    {
//                        if($element['idOwner'] == $idUser)
//                            $isOwner = TRUE;
//                        else
//                        {
//                            $rightManager = new RightManager();
//                            $hasRight = $rightManager->hasRightOnElement($idUser, $idElement, '11');
//                        }
//                        if($isOwner || $hasRight)
//                        {
//                            $criteria = array(
//                                '_id' => new MongoId($idElement),
//                                'state' => (int)1,
//                            );
//
//                            $options = array(
//                                'new' => TRUE
//                            );
//
//                            if(isset($this->request['name'])) //cas du renommage
//                            {
//                                $newFileName = $this->request['name'];
//                                //modification du nom du fichier sur les serveurs de fichiers
//                                return renameElement($idUser, $idElement, $newFileName);
//
//                                //génération du nouveau hash du fichier
//                                $newFileHash = 'test';
//
//                                //modification en base
//                                $update = array(
//                                    '$set' => array(
//                                        'name' => $newFileName,
//                                        'hash' => $newFileHash,
//                                        'downloadLink' => ''
//                                    )
//                                );
//                            }
//                            elseif(isset($pathGiven)) //cas du déplacement
//                            {
//                                if($destinationElement['_id'] == $idUser)
//                                    $isOwnerOfDestination = TRUE;
//                                else
//                                    //l'utilisateur a-t-il les droits sur le dossier de destination?
//                                    $hasRightOnDestination = $rightManager->hasRightOnElement($idUser, $idDestinationElement, '11');
//
//                                if($isOwnerOfDestination || $hasRightOnDestination)
//                                {
//                                    //déplacement côté serveur
//                                    $update = array(
//                                        '$set' => array(
//                                            'serverPath' => $pathGiven,
//                                            'downloadLink' => ''
//                                        )
//                                    );
//                                }
//                                else return array('error' => 'Access of destination denied');
//                            }
//                            else //cas de la suppression
//                            {
//                                $update = array(
//                                    '$set' => array(
//                                        'state' => (int)0,
//                                        'downloadLink' => ''
//                                    )
//                                );
//                            }
//
//                            return $elementManager->findAndModify($criteria, $update, NULL, $options);
//                        }
//                        else return array('error' => 'Access denied');
//                    }
//                    else return array('error' => 'Element does not exist');
//                }
//                elseif(isset($pathGiven) && isset($this->request['hash']) && isset($_FILES['uploadFile'])) //cas du téléversement
//                {
//                    var_dump($idDestinationElement); exit();
//
//                    $hash = $this->request['hash'];
//
//                    $file = $_FILES['uploadFile'];
//
//                    echo $this->file_get_size($file['tmp_name']);
//                }
//            }
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