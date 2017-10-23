<?php
namespace App\Model;

use ActiveRecord\Model;

class Customer extends \ActiveRecord\Model
{
	// a Customer can have many payments
	static $has_many = array(
        array('payments')
    );

}
