<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserDetails_model extends Model
{
    protected $table = 'user_data';
    protected $fillable = [
    	'first_name',
    	'last_name',
    	'email',
    	'password'
    ];
}
