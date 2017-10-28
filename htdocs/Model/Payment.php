<?php
namespace App\Model;

use ActiveRecord\Model;

class Payment extends \ActiveRecord\Model
{
    // Payment belongs to a Customer
	static $belongs_to = array(
		array('customer')
    );

}
