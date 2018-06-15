<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CaseUser extends Model
{
    protected $table = 'case_user';

    public static function getCaseuser($case_id)
    {
    	$query['case_id'] = $case_id;
        // $query1['utype'] = 'staff';
    	$caseuser = self::where($query)->get();
    	if($caseuser){
    		return $caseuser;
    	}else{
    		return FALSE;
    	}
    }

    public static function getCaseuserByID($user_id)
    {
        $query['user_id'] = $user_id;
        // $query1['utype'] = 'staff';
        $caseuser = self::where($query)->get();
        if($caseuser){
            return $caseuser;
        }else{
            return FALSE;
        }
    }

    
}
