<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Communication_user extends Model
{
	protected $table = 'communication_user';
    public $timestamps = false;
	protected $hidden = [        
        'communication_id',
        'user_id',
        'id',
    ];

    protected $fillable = [
        'user_id',
        'communication_id',
    ];
    public static function getCommunication_user($user_id)
    {
    	$query['user_id'] = $user_id;
    	$communication_user = Self::where($query)->get();
    	if(!$communication_user->isEmpty()){
    		return $communication_user;
    	}else{
    		return FALSE;
    	}
    }

    public static function getComUserbyCom($com_id)
    {
        $query['communication_id'] = $com_id;
        $communication_user = Self::where($query)->get();
        if(!$communication_user->isEmpty()){
            return $communication_user;
        }else{
            return FALSE;
        }
    }
}
