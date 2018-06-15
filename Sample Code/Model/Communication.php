<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;
class Communication extends Model
{
	protected $table = 'communication';
    public $timestamps = false;
    protected $hidden = [
        'slug',
        // 'case_id',
        'tz',
        'created',
        'modified',
        // 'in_reply',
        // 'id',
        'message_type',
    ];

    protected $fillable = [
        'slug',
        'message_type',
        'in_reply',
        'case_id',
        'entry_date',
        'subject',
        'message',
        'created_by',
        'tz',
        'created',
        'modified',
    ];
    public static function getCommunication($communication_id)
    {
    	$query['id'] = $communication_id;
    	$communication = Self::where($query)->first();
    	if($communication){
    		return $communication;
    	}else{
    		return FALSE;
    	}
    }

    public static function getComByCase($case_id)
    {
        $query['case_id'] = $case_id;
        // $com = DB::table('communication as a')
        //     ->leftjoin('communication_user as b', 'a.id', '=', 'b.communication_id')
        //     ->where('a.case_id','=',$case_id)
        //     ->select('a.*', 'b.*')
        //     ->get();
        $com = self::where($query)->get();
        if(!$com){
            return FALSE;
        }else{            
            return $case_id;
        }
    }
}
