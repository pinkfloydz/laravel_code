<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $table = 'invoice';
    public $timestamps = FALSE;
    protected $hidden = [
        // 'id',
        // 'case_id',
        // 'cinvoice_id',
        // 'to_invoice',
        // 'client_id',
        // 'created_by',
    ];
    protected $fillable = [
        'description',
        'unit',
        'unit_price',
        'case_id',
        'created_by',
        'invoice_date',
        'client_id',
        'created',
        'modified',

    ];
    public static function getInvoice($user_id)
    {
    	$query['client_id'] = $user_id;
    	$invoice = Self::where($query)->get();
    	if($invoice){
    		return $invoice;
    	}else{
    		return FALSE;
    	}
    }

    public static function getCreateInvoiceList($user_id,$case_id,$client_id)
    {
        $query['created_by'] = $user_id;
        $query['case_id'] = $case_id;
        $query['client_id'] = $client_id;
        $invoice = Self::where($query)->orderBy('id','asc')->get([
            'id',
            'invoice_date',
            'created_by',            
            'description',
            'unit_price',
            'unit',
            'to_invoice',
        ]);
        
        if(!$invoice->isEmpty()){
            return $invoice;
        }else{
            return FALSE;
        }
    }

    public static function getInvoiceByCaseID($case_id)
    {
        $query['case_id'] = $case_id;
        $invoice = Self::where($query)->get();
        if(!$invoice->isEmpty()){
            return $invoice;
        }else{
            return FALSE;
        }
    }
}
