<?php

namespace JambageCom\Transactor\Api;

/***************************************************************
*  Copyright notice
*
*  (c) 2023 Franz Holzinger (franz@ttproducts.de)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
* Model class of the address read in from the gateway
*
* @author	Franz Holzinger <franz@ttproducts.de>
* @package TYPO3
* @subpackage transactor
*/



class Address implements \TYPO3\CMS\Core\SingletonInterface {
    protected $name = null;
    protected $user_id = null;
    protected $email = null;
    protected $email_verified = null;
    protected $street = null;
    protected $zip = null;
    protected $city = null;
    protected $state = null;
    protected $country = null;



    public function setName ($name)
    {
        $this->name = $name;
    }

    public function getName ()
    {
        return $this->name;
    }

    public function setUserId ($user_id)
    {
        $this->user_id = $user_id;
    }

    public function getUserId ()
    {
        return $this->user_id;
    }

    public function setEmail ($email)
    {
        $this->email = $email;
    }

    public function getEmail ()
    {
        return $this->email;
    }

    public function setEmailVerified ($email_verified)
    {
        $this->email_verified = $email_verified;
    }

    public function getEmailVerified ()
    {
        return $this->email_verified;
    }

    public function setStreet ($street)
    {
        $this->street = $street;
    }

    public function getStreet ()
    {
        return $this->street;
    }

    public function setZip ($zip)
    {
        $this->zip = $zip;
    }

    public function getZip ()
    {
        return $this->zip;
    }

    public function setCity ($city)
    {
        $this->city = $city;
    }

    public function getCity ()
    {
        return $this->city;
    }

    public function setState ($state)
    {
        $this->state = $state;
    }

    public function getState ()
    {
        return $this->state;
    }

    public function setCountry ($country)
    {
        $this->country = $country;
    }

    public function getCountry ()
    {
        return $this->country;
    }

}
