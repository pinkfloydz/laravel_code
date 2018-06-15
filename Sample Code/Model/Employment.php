<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Employment extends Model
{
   protected $table = 'ocei';
    protected $hidden = [
    	'created',
    	'modified',
    	'client_id',
    	'notes',
    	'id',
    ];
    public static function getEmployment($id)
    {
    	// id = client id that refer to user id
    	$query['client_id'] = $id;
    	$employment = self::where($query)->first();
    	if($employment){
    		return $employment;
    	}else{
    		return FALSE;
    	}
    }
}
