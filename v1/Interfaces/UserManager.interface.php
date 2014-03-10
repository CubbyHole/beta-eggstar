<?php
/**
 * Created by Notepad++.
 * User: Alban Truc
 * Date: 30/01/14
 * Time: 14:51
 */

interface UserManagerInterface
{
    /**
     * Authentifier un utilisateur:
     * - Récupère l'utilisateur inscrit avec l'e-mail indiquée. S'il y en a un:
     *  - Vérifie le mot de passe. S'il correspond:
     *      - Récupère son compte
     * @author Alban Truc
     * @param $email
     * @param $password
     * @since 02/2014
     * @return array des infos de l'user et son compte ou array contenant le message d'erreur
     */

    function authenticate($email, $password);

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
     * @param $geolocation
     * @since 02/2014
     * @return array contenant le résultat de la requête ou le message d'erreur
     *
     * IMPORTANT: ne pas oublier de gérer l'envoi d'e-mail d'inscription!
     */

    function register($name, $firstName, $email, $password, $geolocation);

    /**
     * - Insère un compte gratuit.
     * - Insère l'utilisateur qui va posséder ce compte.
     * - Gestion des exceptions MongoCursor: http://www.php.net/manual/en/class.mongocursorexception.php
     * - Gestion des erreurs, avec notamment:
     *       Annulation de l'insertion du compte gratuit si l'insertion de l'utilisateur a échoué
     * @author Alban Truc
     * @param $name
     * @param $firstName
     * @param $email
     * @param $password
     * @param $geolocation
     * @since 02/2014
     * @return bool TRUE si l'insertion a réussi, FALSE sinon
     */

    function addFreeUser($name, $firstName, $email, $password, $geolocation);

    /**
     * Vérifier la disponibilité d'une adresse e-mail
     * @author Alban Truc
     * @param $email
     * @since 02/2014
     * @return bool FALSE si email déjà prise, TRUE sinon
     */

    function checkEmailAvailability($email);

    /**
     * - Génère un GUID
     * - Supprime les tirets et accolades
     * @author Alban Truc
     * @since 23/02/2014
     * @return String
     */

    public function generateGUID();
}
?>