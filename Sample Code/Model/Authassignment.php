<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;
class Authassignment extends Model
{
    protected $table = 'authassignment';
    
    public static function getAuthassignment($user_id)
    {
    	$query['userid'] = $user_id;
    	$authassignment = SELF::where($query)->orderBy('itemname','asc')->get();
    	if($authassignment){
    		return $authassignment;
    	}else{
    		return FALSE;
    	}
    }

    public static function getRole($user_id)
    {
        $auth = self::getAuthassignment($user_id);
        if(!$auth->isEmpty()){
            $ret['none'] = 0;
            $ret['client'] = FALSE;
            $ret['att'] = FALSE;
            $ret['admin'] = FALSE;
            $ret['staff'] = FALSE;
                foreach ($auth as $key => $auth) {
                    if($auth->itemname == 'Client'){
                        $ret['client'] = TRUE;
                    }
                    if($auth->itemname == 'Attorney'){
                        $ret['att'] = TRUE;
                    }
                    if($auth->itemname == 'Admin'){
                        $ret['admin'] = TRUE;
                    }
                    if($auth->itemname == 'Staff'){
                        $ret['staff'] = TRUE;
                    }
                }
           
        }else{
            $ret['none'] = 1;
        }
        return $ret;
    }

    public static function getAllOTSstaff($user_id)
    {
         $staff = DB::table('authassignment as a')
                        ->distinct()
                        ->Join('users as b','a.userid','=','b.id')
                        ->select('userid')
                        ->where('a.itemname','<>','Client')
                        ->where('a.itemname','<>','Attorney')
                        ->where('a.userid','<>',$user_id)
                        ->where('b.is_active','<>','0')
                        // ->orderBy('b.id', 'desc')
                        // ->where('case_id','<>','null')
                        ->get();  
                       
        // $authassignment =  SELF::distinct()->select('userid')->where('itemname', '<>', 'Client')->get();
        if($staff){
            return $staff;
        }else{
            return FALSE;
        }
    }
}
