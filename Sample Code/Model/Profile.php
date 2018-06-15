<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $table = "profile";
    protected $hidden = [
        // "id",
    ];
    public static function getProfile($id='')
    {
    	$return = Profile::where('user_id',$id)->first([
            'id',
            'user_id',
            'first_name',
            'middle_name',
            'last_name',
            'phone1',
            'fax',
            'mobile',
            'image',
        ]);
    	if($return){
    		return $return;
    	}else{
    		return FALSE;
    	}
    }
}
