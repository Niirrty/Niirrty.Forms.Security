<?php
/**
 * @author     Ni Irrty <niirrty+code@gmail.com>
 * @copyright  © 2017-2021, Ni Irrty
 * @package    Niirrty\Forms\Security
 * @since      2017-11-03
 * @version    0.4.0
 */


namespace Niirrty\Forms\Security;


/**
 * This interface must be the base of all Niirrty\Forms\Security main classes
 *
 * @package Niirrty\Forms\Security
 */
interface ISecurityCheck
{


    /**
     * Returns the state, if the implementing form security check is based on an usable request. It says nothing about
     * the request validity!
     *
     * @return bool
     */
    public function isRequest(): bool;

    /**
     * Returns the state, if the implementing form security check is based on an VALID request.
     *
     * @return bool
     */
    public function isValidRequest(): bool;

    /**
     * Reloads the states isRequest and isValidRequest, after you have made some changes by the set methods.
     */
    public function reload();


}

