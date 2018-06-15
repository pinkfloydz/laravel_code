<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Education extends Model
{
    protected $table = 'ocedi';
    protected $hidden = [
    	'created',
    	'modified',
    	'client_id',
    	'notes',
    	'id',
    ];
    public static function getEducation($id)
    {
    	// id = client id that refer to user id
    	$query['client_id'] = $id;
    	$education = self::where($query)->first();
    	if($education){
    		return $education;
    	}else{
    		return FALSE;
    	}
    }
}
