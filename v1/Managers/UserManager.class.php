<?php
/**
 * Created by Notepad++.
 * User: Alban Truc
 * Date: 30/01/14
 * Time: 14:51
 */

require_once 'AbstractManager.class.php';
require_once 'AccountManager.class.php';
require_once '.\Interfaces\UserManager.interface.php';

class UserManager extends AbstractManager implements UserManagerInterface
{

	protected $userCollection;
	protected $accountManager;

    /**
     * Constructeur:
     * - Apelle le constructeur de AbstractManager (gestion des accès de la BDD).
     * - Initialise la collection user.
     * - Crée un objet AccountManager (l'user a une clé étrangère de account).
     * @author Alban Truc
     * @since 01/2014
     */

	public function __construct()
	{
		parent::__construct();
		$this->userCollection = $this->getCollection('user');
		$this->accountManager = new AccountManager();
	}

    /**
     * - Insère un compte gratuit.
     * - Insère l'utilisateur qui va posséder ce compte.
     * - Gestion des exceptions MongoCursor: http://de3.php.net/manual/en/class.mongocursorexception.php
     * - Annule l'insertion du compte gratuit si l'insertion de l'utilisateur a échoué?
     * @author Alban Truc
     * @param $name
     * @param $firstName
     * @param $email
     * @param $password
     * @since 02/2014
     * @return bool TRUE si l'insertion a réussi, FALSE sinon
     */

	public function addFreeUser($name, $firstName, $email, $password)
	{
		
		//Caractéristiques du compte gratuit
		$account = array(
							'_id' => new MongoId(),
							'state' => 'ok', //à changer?
							'idRefPlan' => '52eb5e743263d8b6a4395df0', //id du plan gratuit
							'storage' => '5', //à changer
							'ratio' => '4', //à changer
							'startDate' => '11/02/2014', //à changer, penser à utiliser MongoDate
							'endDate' => '11/03/2014' //à changer, penser à utiliser MongoDate
						);
							
		$isAccountAdded = $this->accountManager->createAccount($account);
			
		if($isAccountAdded == TRUE) //inutile d'ajouter un utilisateur si l'ajout d'account a échoué
		{
			//Caractéristiques de l'utilisateur
			$user = array(
							'_id' => new MongoId(),
							'state' => 'ok', //à changer?
							'isAdmin' => 'false', //à changer? Y a-t-il une classe Mongo pour les booléens?
							'idAccount' => $account['_id'],
							'name' => $name,
							'firstName' => $firstName,
							'password' => $password,
							'email' => $email,
							'geolocation' => 'somewhere', //à changer, utiliser mon code de géolocalisation
							'apiKey' => '3456f1fdsq', //utiliser du GUID
						 );
			try
			{
                /**
                 * w = 1 est optionnel, il est déjà à 1 par défaut. Cela permet d'avoir un retour du status de l'insertion.
                 * Plus d'informations sur toutes les options, voir chapitre "Parameters":
                 * http://www.php.net/manual/en/mongocollection.insert.php
                 */
				$info = $this->userCollection->insert($user, array('w' => 1));
			}
			catch(MongoCursorException $e)
            {
                return array('error' => $e->getMessage());
			}

            /**
             * Gestion de ce qui est retourné grâce à l'option w.
             * Plus d'informations sur les retours, voir chapitre "Return Values":
             * http://www.php.net/manual/en/mongocollection.insert.php
             */
			if(!(empty($info)) && $info['ok'] == '1' && $info['err'] === NULL) return TRUE;

			else //échec de l'insertion de l'utilisateur
			{
				//annuler l'insertion de l'account?
				return array('error' => $info['err']);
			}
		}
		else return $isAccountAdded; //Message d'erreur approprié
			
	}

    /**
     * Authentifier un utilisateur:
     * - Récupère l'utilisateur inscrit avec l'e-mail indiquée. S'il y en a un:
     *  - Vérifie le mot de passe. S'il correspond:
     *      - Récupère son compte
     * @author Alban Truc
     * @param $email
     * @param $password
     * @since 02/2014
     * @return array des infos de l'user et son compte | array contenant le message d'erreur
     */

    public function authenticate($email, $password)
	{
        //Récupère l'utilisateur inscrit avec l'e-mail indiquée.
		$query = array('email' => $email);

        /**
         * Doc du findOne: http://www.php.net/manual/en/mongo.tutorial.findone.php
         * Utilisé lorsqu'on attend un résultat unique (notre cas) ou si l'on ne veut que le 1er résultat.
         */
		$result = $this->userCollection->findOne($query);
		
		if($result) //Si l'utilisateur existe
		{
            if($result['password'] == $password)
            {
                //Cast le MongoId en string
			    $result['_id'] = (string) $result['_id'];

			    //On récupère le compte correspondant à l'utilisateur
			    $account = $this->accountManager->findById($result['idAccount']);
			
			    if($account) //Si le compte existe
			    {
                    //On retourne toutes les infos du compte plutôt que (seulement) son ID
                    unset($result['idAccount']);
				    $result['account'] = $account;

                    return $result;
			    }
			    else
                {
                    $errorInfo = 'Account with ID '.$result['idAccount'].' of user '.$result['_id'].' not found';
                    return array('error' => $errorInfo);
                }
            }
            else
            {
                $errorInfo = 'Password given ('.$password.') does not match with password in database ('.$result['password'].')';
                return array('error' => $errorInfo);
            }
		}
		else
        {
            $errorInfo = 'No user found for the following e-mail: '.$email;
            return array('error' => $errorInfo);
        }
	}

    /**
     * Vérifier la disponibilité d'une adresse e-mail
     * @author Alban Truc
     * @param $email
     * @since 02/2014
     * @return bool FALSE si email déjà prise, TRUE sinon
     */

    public function checkEmailAvailability($email)
	{
		$query = array('email' => $email);

		$result = $this->userCollection->findOne($query);

        //False parce qu'on ne veut pas inscrire deux personnes pour la même e-mail
		if($result) return FALSE;
        else return TRUE;
	}

    /**
     * Inscription:
     * - Vérifie certains critères sur les paramètres fournis
     * - Appelle la fonction de vérification de disponibilité de l'e-mail
     * - Appelle la fonction d'ajout d'un free user
     * - Appelle la fonction d'authentification qui retourne (si tout va bien) l'utilisateur inscrit à l'instant
     * @author Alban Truc
     * @param $name
     * @param $firstName
     * @param $email
     * @param $password
     * @since 02/2014
     * @return array
     *
     * IMPORTANT: ne pas oublier de gérer l'envoi d'e-mail d'inscription!
     */

    public function register($name, $firstName, $email, $password)
	{
		if( 
			!(empty($name)) &&
			!(empty($firstName)) &&
			!(empty($email)) &&
			!(empty($password))
		   )
		{
			if(
				strlen($email) <= 26 &&
				filter_var($email, FILTER_VALIDATE_EMAIL) && //http://in2.php.net/manual/en/function.filter-var.php
				(2 <= strlen($name) && strlen($name) <= 15) &&
				(2 <= strlen($firstName) && strlen($firstName) <= 15)
			   )
			{
                if(self::checkEmailAvailability($email) != FALSE)
				{
					$isRegisterValid = self::addFreeUser($name, $firstName, $email, $password);
					
					if($isRegisterValid == TRUE)
					{
						return self::authenticate($email, $password);
					}
                    else return $isRegisterValid; //contient le message d'erreur approprié
				}
                else
                {
                    $errorInfo = 'Email already used';
                    return array('error' => $errorInfo);
                }
			}
            else
            {
                $errorInfo = 'E-mail address not valid or lenght specifications not respected';
                return array('error' => $errorInfo);
            }
		}
        else
        {
            $errorInfo = 'Some fields are empty';
            return array('error' => $errorInfo);
        }
	
	}
	
}

?>