<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Client;
class Cases extends Model
{
    public $timestamps = FALSE;
    public static function getCases($client_id)
    {
    	$query['user_id'] = $client_id;
    	$case = Self::where($query)->orderBy('id','desc')->get();
    	if($case){
    		return $case;
    	}else{
    		return FALSE;
    	}
    }

    public static function getCasebyClient($id)
    {
        $client = Client::getClientProfile($id);
        if($client){
            $return = FALSE;
            $cases = Cases::getCases($client->id);
            foreach ($cases as $key => $case) {
                $return[] = $case;
            }
            
            return $return;
        }else{
            return FALSE;
        }   
    }

    public static function getCasesByCaseID($case_id)
    {
        $query['id'] = $case_id;
        $case = Self::where($query)->orderBy('id','desc')->get();
        if($case){
            return $case;
        }else{
            return FALSE;
        }
    }

    public static function getCasebyQuery($query)
    {
        $case = Self::where($query)->orderBy('id','desc')->get();
        if($case){
            return $case;
        }else{
            return FALSE;
        }
    }
}
