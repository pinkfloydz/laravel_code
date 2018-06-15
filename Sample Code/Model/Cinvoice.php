<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Cinvoice extends Model
{
	protected $table = 'cinvoice';
    public $timestamps = FALSE;
    protected $fillable = [
        'created_by',
        'slug', 
        'client_id',           
        'case_id',  
        'amount',
        'paid', 
        'ipay', 
        'notes',
        'paid_via_retainer',
        'balance',
        'invoice_date',
        'due_date',
        'to_address' ,
        'from_address' ,
        'lbal',
        'created',
        'status',
        'is_draft',
        'is_sent_no_clnt',
    ];
    public static function getCinvoice($user_id)
    {
    	$query['client_id'] = $user_id;
    	$cinvoice = Self::where($query)->get([
                'amount',
                'balance',
                'paid',
                'status',
        ]);
    	if(!$cinvoice->isEmpty()){
    		return $cinvoice;
    	}else{
    		return FALSE;
    	}
    }

    public static function getCinvoiceByCaseID($case_id)
    {
        $query['case_id'] = $case_id;
        $cinvoice = Self::where($query)->get();
        if($cinvoice->isEmpty()){
            return $cinvoice;
        }else{
            return FALSE;
        }
    }
    public static function getCinvoiceByQuery($query)
    {
        $cinvoice = Self::where($query)->get();
        if(!$cinvoice->isEmpty()){
            return $cinvoice;
        }else{
            return FALSE;
        }
    }
}
