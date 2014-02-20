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
     * @author Alban Truc
     * @param $email
     * @param $password
     * @since 02/2014
     * @return mixed
     */

    function authenticate($email, $password);

    /**
     * @author Alban Truc
     * @param $name
     * @param $firstName
     * @param $email
     * @param $password
     * @since 02/2014
     * @return mixed
     */

    function register($name, $firstName, $email, $password);

    /**
     * @author Alban Truc
     * @param $name
     * @param $firstName
     * @param $email
     * @param $password
     * @since 02/2014
     * @return mixed
     */

    function addFreeUser($name, $firstName, $email, $password);

    /**
     * @author Alban Truc
     * @param $email
     * @since 02/2014
     * @return mixed
     */

    function checkEmailAvailability($email);
}
?>