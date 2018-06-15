<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Documents extends Model
{
	protected $table = 'document';
    public $timestamps = false;
    protected $hidden = [
        'user_id',
    ];
	protected $fillable = [
        'user_id',
        'dname',
        'doc_name',
        'notes',
        'created_by',
        'doc_type',
        'doc_size',
        'created',
        'modified',
        'filename',
        'case_id',
        'display_name',
        'to_client',
        'slug',
    ];
    public static function getDocs($user_id)
    {
    	$query['user_id'] = $user_id;
    	// $query['case_id'] = $case_id;
    	$docs = Self::where($query)->get([
                'id',
                'dname',
                'doc_name',
                'created_by',
                'notes',
                'created',
        ]);
    	if($docs){
    		return $docs;
    	}else{
    		return FALSE;
    	}
    }

    public static function setDocs($data)
    {
    	return $data;
    }

    public static function getCTSDocs($user_id,$case_id,$entry_time)
    {
        $query['created_by'] = $user_id;
        $query['case_id'] = $case_id;
        $docs = Self::where($query)
        ->where('created','>=',$entry_time)
        ->get([
                'user_id',
                'id',
                'dname',
                'doc_name',
                'notes',
                'created',
                'created_by',
                'to_client',
        ]);
        if($docs){
            return $docs;
        }else{
            return FALSE;
        }
    }

    
}
