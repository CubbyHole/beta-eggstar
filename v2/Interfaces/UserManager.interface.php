<?php
/**
 * Created by Notepad++.
 * User: Alban Truc
 * Date: 30/01/14
 * Time: 14:51
 */

/**
 * Interface UserManagerInterface
 * @interface
 * @author Alban Truc
 */
interface UserManagerInterface
{
    /**
     * Retrouver un User selon des critères donnés
     * @author Alban Truc
     * @param array $criteria critères de recherche
     * @param array $fieldsToReturn champs à récupérer
     * @since 31/03/2014
     * @return array
     */

    function find($criteria, $fieldsToReturn = array());

    /**
     * Retourne le premier User correspondant au(x) critère(s) donné(s)
     * @author Alban Truc
     * @param array $criteria critère(s) de recherche
     * @param array $fieldsToReturn champs à retourner
     * @since 31/03/2014
     * @return array
     */

    function findOne($criteria, $fieldsToReturn = array());

    /**
     * - Retrouver l'ensemble des User
     * - Gestion des exceptions et des erreurs
     * @author Alban Truc
     * @param array $fieldsToReturn champs à retourner
     * @since 11/03/2014
     * @return array
     */

    function findAll($fieldsToReturn = array());

    /**
     * - Retrouver un user par son ID.
     * - Gestion des exceptions et des erreurs
     * @author Alban Truc
     * @param array $fieldsToReturn champs à retourner
     * @param string|MongoId $id Identifiant unique de l'user à trouver
     * @since 02/2014
     * @return array
     */

    function findById($id, $fieldsToReturn = array());

    /**
     * - Retrouver un User selon certains critères et le modifier/supprimer
     * - Récupérer cet User ou sa version modifiée
     * - Gestion des exceptions et des erreurs
     * @author Alban Truc
     * @param array $searchQuery critères de recherche
     * @param array $updateCriteria les modifications à réaliser
     * @param array|NULL $fieldsToReturn pour ne récupérer que certains champs
     * @param array|NULL $options
     * @since 11/03/2014
     * @return array
     */

    function findAndModify($searchQuery, $updateCriteria, $fieldsToReturn = NULL, $options = NULL);

    /**
     * - Insère un nouvel utilisateur en base.
     * - Gestion des exceptions et des erreurs.
     * @author Alban Truc
     * @param array $user
     * @param array $options
     * @since 31/03/2014
     * @return TRUE|array contenant le message d'erreur dans un indexe 'error'
     */

    function create($user, $options = array('w' => 1));

    /**
     * Fonction d'update utilisant celle de {@see AbstractPdoManager}
     * @author Alban Truc
     * @param array $criteria description des entrées à modifier
     * @param array $update nouvelles valeurs
     * @param array|NULL $options
     * @since 31/03/2014
     * @return TRUE|array contenant le message d'erreur dans un indexe 'error'
     */

    function update($criteria, $update, $options = array('w' => 1));

    /**
     * - Supprime un/des utilisateur(s) correspondant à des critères données
     * - Gestion des exceptions et des erreurs
     * @author Alban Truc
     * @param array $criteria ce qu'il faut supprimer
     * @param array $options
     * @since 31/03/2014
     * @return TRUE|array contenant le message d'erreur dans un indexe 'error'
     */

    function remove($criteria, $options = array('w' => 1));

    /**
     * Authentifier un utilisateur:
     * - Récupère l'utilisateur inscrit avec l'e-mail indiquée. S'il y en a un:
     *  - Vérifie le mot de passe. S'il correspond:
     *      - Récupère son compte
     * @author Alban Truc
     * @param string $email
     * @param string $password
     * @since 02/2014
     * @return array
     */

    function authenticate($email, $password);
}