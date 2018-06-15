<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Http\Requests;
use App\Client;
use App\Invoice;
use App\Cinvoice;
use App\Authassignment;
use App\User;
use App\Profile;
use App\Cases;
use App\Transactions;
use App\Expense;
use Validator;
use DB;

class InvoiceApiController extends Controller
{



    public function getInvoice(Request $request){
    	$data = $request->all();
    	$id = $data['user_id'];
    	if(!isset($id))
          return response()->json(['Message'=>'Please check Api Parameter','status'=>FALSE], 400);
      	
		$invoice = Invoice::getInvoice($id);
		$auth = Authassignment::getAuthassignment($id);
        if(!$auth)
            return response()->json(['Message'=>'Not a Client','status'=>FALSE], 400);

            foreach ($auth as $key => $auth) {
                if($auth->itemname == 'Client'){
                    $profile = Client::getClientProfile($id);  
                    if(!$profile)
                        return response()->json(['Message'=>'Clinet id is not ID','status'=>FALSE], 400);

                    if($invoice){
						$cinvoice = Cinvoice::getCinvoice($data['user_id']);
						// $return['id'] =$data['user_id'];

						// $return['invoice'] = $invoice;
						// $cinvoice->fname;
						$user = User::getUserbyID($id);
						// $return['cinvoice'] = $cinvoice;
                        if($cinvoice){
    						foreach ($cinvoice as $key => $cinvoice) {
    							if($cinvoice->status == '') $cinvoice->status = 'Paid';
    							$return[] = array_merge($profile->toArray(),$user->toArray(),$cinvoice->toArray());
    						}
                            return response()->json(['status'=>TRUE,'data'=>$return],200);
						}else{
                            return response()->json(['Message'=>'No invoice available','status'=>FALSE], 400);
                        }

						// $return = array_merge($invoice->toArray(),$cinvoice->toArray());
						
					}else{
						return response()->json(['Message'=>'No invoice available','status'=>FALSE], 400);
					}      
                }else{
                    return response()->json(['Message'=>'Not a Client','status'=>FALSE], 400);
                }
        }
        return response()->json(['Message'=>'User not Exist','status'=>FALSE], 400);
	}

    public function getCreateInvoiceList(Request $request){
    	$data = $request->all();
    	$validator = Validator::make($data, [
            'case_id' => 'required',
            'user_id' => 'required',
            'client_id' => 'required',
        ]);

        
        if ($validator->fails()) {
            return response()->json(['Message'=>'Please check Api Parameter','status'=>FALSE], 400);
        }else{
        	$role = Authassignment::getRole($data['user_id']);
        	if($role['none'] == 0){
        	if(!$role['client']){
        		$invoice = Invoice::getCreateInvoiceList($data['user_id'],$data['case_id'],$data['client_id']);
        		if($invoice){
	        		foreach ($invoice as $key => $value) {
	        			$profile = Profile::getProfile($value->created_by);
	        			$value->created_by = ['first_name'=>$profile->first_name,'middle_name'=>$profile->middle_name,'last_name'=>$profile->last_name];
	        			$value->amount = $value->unit * $value->unit_price;
	        			// $value->slug =  uniqid();
	        			$value->type = 'Service'; // FIXED for now
	        			unset($value->unit_price);
	        			unset($value->unit);
	        		}
                    $return['invoice'] = $invoice;
        			// return response()->json(['status'=>TRUE,'data'=>$invoice],200);
	        	}else{
	        		// return response()->json(['Message'=>'No available Invoice','status'=>FALSE], 400);	
                    $return['invoice'] = false;
	        	}

                $expense = Expense::getCreateInvoiceList($data['case_id']);
                if($expense){
                    foreach ($expense as $key => $value) {
                        $profile = Profile::getProfile($value->created_by);

                        $value->created_by = ['first_name'=>$profile->first_name,'middle_name'=>$profile->middle_name,'last_name'=>$profile->last_name];
                        $value->type = 'Cost';
                    }
                    $return['expense'] = $expense;
                }else{
                    $return['expense'] = false;   
                }

                if($return['invoice'] == FALSE && $return['expense'] == FALSE ){
                    return response()->json(['Message'=>'No available Invoice','status'=>FALSE], 400);   
                }else{
                    return response()->json(['status'=>TRUE,'data'=>$return],200);
                }
        	}else{
        		return response()->json(['Message'=>'Client not Allowed','status'=>FALSE], 400);	
        	}
        	}else{
        		return response()->json(['Message'=>'Not a Client','status'=>FALSE], 400);		
        	}
        }
    	
    }

    public function getLedger(Request $request){
        $data = $request->all();
    	$validator = Validator::make($data, [
            'case_id' => 'required',
            'user_id' => 'required',
            'client_id' => 'required',
        ]);

        
        if ($validator->fails()) {
            return response()->json(['Message'=>'Please check Api Parameter','status'=>FALSE], 400);
        }else{
            $case_id = $data['case_id'];
            $user_id = $data['user_id'];
            $client_id = $data['client_id'];

            
            $att = Profile::getProfile($user_id);
            if($att==FALSE){
                return response()->json(['Message'=>'att id is not a client','status'=>FALSE], 400);
            }
            $profile = Client::getClientProfile($client_id);  
            if($profile==FALSE){
                return response()->json(['Message'=>'client id is not a client','status'=>FALSE], 400);
            }

            $q['id'] = $case_id;
            $q['user_id'] = $profile->id;
            
            $client = Cases::getCasebyQuery($q);
            if($client){
            $client = $client?$this->addtype($client->toArray(),'client'):$client=array();
            
            $query['case_id'] = $case_id;
            $query['is_draft'] = 0;
            $cinvoice = Cinvoice::getCinvoiceByQuery($query);
            $cinvoice = $cinvoice?$this->addtype($cinvoice->toArray(),'cinvoice'):$cinvoice=array();

            $transaction = Transactions::getTransByCaseID($case_id);
            $transaction = $transaction?$this->addtype($transaction->toArray(),'transaction'):$transaction=array();

            $transactione = Transactions::getTransEByCaseID($case_id);
            $transactione = $transactione?$this->addtype($transactione->toArray(),'transactione'):$transactione=array();

            $transactionc = Transactions::getTransCByCaseID($case_id);
            $transactionc = $transactionc?$this->addtype($transactionc->toArray(),'transactionc'):$transactionc=array();

            $expense = Expense::getExpenseByCaseID($case_id);
            $expense = $expense?$this->addtype($expense->toArray(),'expense'):$expense=array();

            $invoice = Invoice::getInvoiceByCaseID($case_id);
            $invoice = $invoice?$this->addtype($invoice->toArray(),'invoice'):$invoice=array();
            
            $data_merge = array_merge(                
                $expense,
                $invoice,
                $cinvoice,
                $transactionc,
                $transactione,
                $client
            );

            $balance = 0;
            $credit = 0;
            $debit = 0;
            $return = null;
            foreach ($data_merge as $key => $value) {
               
                $datas = null;
                $datas['is_edit'] = 0;
                $datas['is_delete'] = 0;
                $datas['type'] = $value['type'];
                $datas['credit'] = '';
                $datas['debit'] = '';
                $datas['date'] = '';
                $datas['balance']= '';
                if($value['type'] == 'client'){         
                    $datas['is_edit'] = 1;
                    $datas['date'] = date('m/d/Y H:i:s',strtotime($value['created']));
                    $datas['transaction'] = 'Client Retainer Deposit for Selected Case';
                    $datas['credit'] = number_format(-$value['retainer_deposit'],2);
                    $datas['balance'] = $datas['credit'];
                    $datas['id'] = $value['id']; 
                    $balance = 0+str_replace(',','',$datas['balance']);
                    $return[] = $datas;
                }

                if($value['type'] == 'invoice'){
                    $datas['is_edit'] = 1;
                    $datas['date'] = date('m/d/Y H:i:s',strtotime($value['invoice_date']));
                    $datas['transaction'] = $value['description'];
                    $datas['debit'] = number_format($value['unit']*$value['unit_price'],2);
                    
                    $datas['id'] = $value['id'];
                    if($value['to_invoice']==0){
                        $datas['is_delete'] = 1;
                    }
                    $return[] = $datas;
                }

                if($value['type'] == 'expense'){
                    $datas['is_edit'] = 1;
                     if($value['to_invoice']==0){
                        $datas['is_delete'] = 1;
                    }
                    $datas['date'] = date('m/d/Y H:i:s',strtotime($value['expense_date']));                    
                    $datas['transaction'] = $value['description'];
                    $datas['debit'] = number_format($value['amount'],2);
                    // if has file
                    if(isset($value['filename']) && !empty($value['filename']) && !is_null($value['filename'])){
                        $datas['filename'] = $value['filename'];
                    }
                    
                  
                    $datas['id'] = $value['id'];
                    $return[] = $datas;
                }

                if($value['type'] == 'transaction'){
                    $datas['is_edit'] = 1;

                    $datas['date'] = date('m/d/Y H:i:s',strtotime($value['created']));
                    $datas['transaction'] = 'Client Retainer Deposit<br/>'.' Notes:'.$value['notes'];

                    $datas['credit'] = number_format($value['retainer_deposit'],2);                    
                    $datas['id'] = $value['id'];
                    
                    $return[] = $datas;
                }                

                if($value['type'] == 'transactione'){
                    
                    // but +credit

                    $datas['date'] = date('m/d/Y H:i:s',strtotime($value['created']));
                    $slug = Cinvoice::find($value['cinvoice_id']);
                    if($value['fund_via_rd']){
                        $datas['transaction'] = "Invoice No: ".strtoupper($slug->slug)." [Paid in the Amount of $".number_format($value['funds_received_payment'],2)." via Retainer/Deposit on Account]<br/>Note : ".$value['fnotes'];
                    }else{
                        $datas['paid'] = $value['funds_received_payment'];
                        $datas['transaction'] = "Invoice No: ".strtoupper($slug->slug)." [Paid in the Amount of $".number_format($value['funds_received_payment'],2)."]<br/>Note : ".$value['fnotes'];
                    }
                    $datas['id'] = $value['id'];

                    $return[] = $datas;
                } 

                if($value['type'] == 'transactionc'){
                    
                    
                    $datas['date'] = date('m/d/Y H:i:s',strtotime($value['created']));
                    $datas['transaction'] = 'Client Retainer Deposit<br/>'.' Notes:'.$value['notes'];

                    $datas['credit'] = number_format(-$value['retainer_deposit'],2);

                    $datas['id'] = $value['id'];
                    
                    $return[] = $datas;
                } 

                if($value['type'] == 'cinvoice'){
                    
                    
                    $datas['date'] = date('m/d/Y H:i:s',strtotime($value['sent_date']));
                    if($value['balance'] == 0 && $value['paid_via_retainer']=="1"){
                        $datas['transaction'] = 'Invoice No: '.strtoupper($value['slug'])."  [Paid in the Amount of $".number_format($value['amount'],2)."] Transfer from Retainer/Trust Account to General Business Account";
                    }elseif($value['ipay']>0 && $value['ipay']<$value['amount']){
                        $datas['transaction'] = 'Invoice No: '.strtoupper($value['slug'])."  [Sent in the Amount of $".number_format($value['amount'],2)." / Partially Paid in amount of ".money_format('%(#10n',$value['ipay'])." ]";
                    }elseif($value['ipay']>0 && $value['ipay']==$value['amount']){                        
                        $datas['transaction'] = 'Invoice No: '.strtoupper($value['slug'])."  [Invoice sent to Client in the Amount of $".number_format($value['amount'],2)." / Paid in amount of ".money_format('%(#10n',$value['ipay'])." ]";
                    }else{
                        if(!$value['is_sent_no_clnt']){
                            $datas['transaction'] = 'Invoice No: '.strtoupper($value['slug'])."  [Registered in Ledger in amount of $".number_format($value['amount'],2)."] - Pending Payment";
                        }else{
                            $datas['transaction'] = 'Invoice No: '.strtoupper($value['slug'])."  [Invoice sent to Client in the Amount of $".number_format($value['amount'],2)."] - Pending Payment";
                        }                        
                    }
                    $datas['id'] = $value['id'];      
                    //---seting up debit and credit
                    if($value['paid_via_retainer']=="1"){
                        $datas['credit'] = number_format(-$value['paid'],2);
                        $datas['debit'] = number_format($value['paid'],2);
                    }elseif($value['ipay']>0 && $value['ipay']<$value['amount']){
                        $datas['credit'] = number_format(-$value['ipay'],2);
                        
                    }elseif ($value['ipay']>0 && $value['ipay']==$value['amount']) {
                        $datas['credit'] = number_format(-$value['ipay'],2);
                        
                    }else{
                       
                    }

                    $return[] = $datas;
                }

            }

                foreach ($return as $key => $row) {
                    // replace 0 with the field's index/key
                    $dates[$key]  = $row['date'];   
                }

            array_multisort($dates, SORT_ASC, $return);
                        $tot_debit =0;
                        $tot_credit =0;
                foreach ($return as $key => $datas) {
                        $debit = 0+str_replace(',','',$datas['debit']); // co-ersion + str replace
                        $credit = 0+str_replace(',','',$datas['credit']);

                        // var_dump($debit);
                        // var_dump($credit);
                        // var_dump($balance);
                        
                        if($datas['type']=='client'){
                            $balance = $credit;
                        }elseif($datas['type']=='transactione'){
                            if(isset($datas['paid'])){
                                $balance -= 0+str_replace(',','',$datas['paid']);
                                $datas['balance'] = $balance;
                                unset($datas['paid']);
                            }else{
                                $datas['balance'] = $balance;   
                            }
                        }else{
                            $balance -= $credit;
                            $balance += $debit;
                            $datas['balance'] = $balance;
                        }
                        $tot_debit += $debit;
                        $tot_credit += $credit;

                        $return[$key] =$datas;
                }

                return response()->json(['data'=>$return,'balance'=>$balance,'credit'=>$tot_credit,'debit'=>$tot_debit,'status'=>TRUE], 200);
            }else{
                return response()->json(['Message'=>'No available case with that user'],400);
            }
        }
    } 

    function aasort (&$array, $key) {
        $sorter=array();
        $ret=array();
        reset($array);
        foreach ($array as $ii => $va) {
            // var_dump($ii);
            // var_dump($va->$key);
            $sorter[$ii]=$va[$key];
            // $sorter[$ii]=$va->$key;
        }
        // asort($sorter);
        usort($sorter,function($a, $b){ 
            // return strcmp($b['created'], $a['created']); 
            // return strcmp($a['created'], $b['created']); 
            if (strtotime($a) == strtotime($b)) {
                return 0;
            }
            return (strtotime($a) > strtotime($b)) ? -1 : 1;
        }); 
        // echo '---'; 
        foreach ($sorter as $ii => $va) {
            // var_dump($va->created);
            // var_dump($ii);
            $ret[$ii]=$array[$ii];
        }
        // $array=array_reverse($ret);
        $array=$ret;
        // var_dump($array);
    }

    function addType($table,$type){
        foreach ($table as $key => $value) {
                $value['type'] = $type;
                $table[$key] = $value;
            }
        return $table;
    }

    public function editLedger(Request $request){
        $data = $request->all();

        $validator = Validator::make($data, [
            'type' => 'required',
            'id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['Message'=>'Please check Api Parameter','status'=>FALSE], 400);
        }else{
            if($data['type'] == 'client'){
                $datas['retainer_deposit'] = $data['retainer_deposit'];
                $case = Cases::find($data['id']);
                $case->retainer_deposit = 0+$datas['retainer_deposit'];
                if($case->save())
                    return response()->json(['Message'=>'Successfully updated','status'=>TRUE],200);
                else
                    return response()->json(['Message'=>'Failed to updated','status'=>FALSE],400);
            }
            elseif ($data['type'] == 'invoice') {
                $datas['description'] = $data['description'];
                $datas['unit'] = 0+$data['unit'];
                $datas['unit_price'] = 0+$data['unit_price'];
                $invoice = Invoice::find($data['id']);
                $invoice->fill($datas);
                if($invoice->save())
                    return response()->json(['Message'=>'Successfully updated','status'=>TRUE],200);
                else
                    return response()->json(['Message'=>'Failed to updated','status'=>FALSE],400);   
            }elseif ($data['type'] == 'expense') {
                $datas['description'] = $data['description'];
                $datas['amount'] = 0+$data['amount'];
                $expense = Expense::find($data['id']);
                $expense->fill($datas);
                if($expense->save())
                    return response()->json(['Message'=>'Successfully updated','status'=>TRUE],200);
                else
                    return response()->json(['Message'=>'Failed to updated','status'=>FALSE],400);   
            }

                    
        }
    }

    public function deleteLedger(Request $request){
        $data = $request->all();

        $validator = Validator::make($data, [
            'type' => 'required',
            'id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['Message'=>'Please check Api Parameter','status'=>FALSE], 400);
        }else{
            if($data['type'] == 'expense'){
                $expense = Expense::find($data['id']);
                if($expense){
                    if($expense->delete())
                        return response()->json(['Message'=>'Successfully deleted','status'=>TRUE],200);
                    else
                        return response()->json(['Message'=>'Failed to deleted','status'=>FALSE],400);   
                }else{
                    return response()->json(['Message'=>'No expense available','status'=>FALSE],400);   
                }
            }elseif ($data['type'] == 'invoice') {
                $invoice = Invoice::find($data['id']);
                if($invoice){
                    if($invoice->delete())
                        return response()->json(['Message'=>'Successfully deleted','status'=>TRUE],200);
                    else
                        return response()->json(['Message'=>'Failed to deleted','status'=>FALSE],400);   
                }else{
                    return response()->json(['Message'=>'No invoice available','status'=>FALSE],400);   
                }
            }
        }
    }

    public function setInvoice(Request $request){
        $data = $request->all();

        $validator = Validator::make($data, [
            'client_id' => 'required',
            'user_id' => 'required',
            'case_id' => 'required',
            'description' => 'required',
            'unit' => 'required',
            'unit_price' => 'required',
            'date' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['Message'=>'Please check Api Parameter','status'=>FALSE], 400);
        }else{
            $invoice = new Invoice;            
            $data['created'] = date('Y-m-d h:i:s' , strtotime('now'));
            $data['modified'] = date('Y-m-d h:i:s' , strtotime('now'));
            $data['invoice_date'] = $data['date'];
            $data['created_by'] = $data['user_id'];
            $invoice->fill($data);
            if($invoice->save())
                return response()->json(['Message'=>'Successfully saved','status'=>TRUE],200);
            else
                return response()->json(['Message'=>'Failed to save','status'=>FALSE],400);  
        }
    }

    public function getEditLedger(Request $request){
        $data = $request->all();

        $validator = Validator::make($data, [
            'type' => 'required',
            'id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['Message'=>'Please check Api Parameter','status'=>FALSE], 400);
        }else{
            if($data['type'] == 'client'){
                $return = Cases::where(['id'=>$data['id']])->first(['case_no as case_name','retainer_deposit']);
                if($return) return response()->json(['data'=>$return,'status'=>TRUE],200);
                else return response()->json(['Message'=>'No case found','status'=>FALSE],400);  
            }elseif ($data['type'] == 'invoice'){
                $return = Invoice::where(['id'=>$data['id']])->first(['description','unit','unit_price']);
                if($return) return response()->json(['data'=>$return,'status'=>TRUE],200);
                else return response()->json(['Message'=>'No case found','status'=>FALSE],400);  
            }elseif ($data['type'] == 'expense'){
                $return = Expense::where(['id'=>$data['id']])->first(['description','amount']);
                if($return) return response()->json(['data'=>$return,'status'=>TRUE],200);
                else return response()->json(['Message'=>'No case found','status'=>FALSE],400);  
            }
        }
    }

    public function getCreateLedger(Request $request){
        $data = $request->all();

        $validator = Validator::make($data, [
            'client_id' => 'required',
            'user_id' => 'required',
            'case_id' => 'required',
            'type' => 'required',
            'id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['Message'=>'Please check Api Parameter','status'=>FALSE], 400);
        }else{
            $client_id = $data['client_id'];
            $user_id= $data['user_id'];

            $return['from_address'] = DB::table('users as u')
                        ->Join('profile as p','p.user_id','=','u.id')
                        ->Join('firm as f','f.id','=','u.firm_id')
                        ->Join('address as a','a.user_id','=','u.id')
                        ->select('u.id','p.first_name','p.middle_name','p.last_name','f.name AS firm','a.address1','a.address2','a.city','a.state','a.zip')
                        ->where(['u.id'=>$user_id])
                        ->get();  
            $return['to_address'] = DB::table('client as u')
                        ->Join('address as a','a.user_id','=','u.user_id')
                        ->select('u.id','u.first_name','u.middle_name','u.last_name','u.ftype','u.company_name','a.address1','a.address2','a.city','a.state','a.zip')
                        ->where(['u.user_id'=>$client_id])
                        ->get();  

            if($data['type']=='invoice'){
                $invoice = Invoice::where(['id'=>$data['id']])->first([
                    'id',
                    'created_by',            
                    'description',
                    'unit_price',
                    'unit',
                ]);
                if($invoice){
                        $profile = Profile::getProfile($invoice->created_by);
                        $invoice->created_by = ['id'=>$profile->id,'first_name'=>$profile->first_name,'middle_name'=>$profile->middle_name,'last_name'=>$profile->last_name];
                        $invoice->amount = $invoice->unit * $invoice->unit_price;
                    $return['invoice'] = $invoice;
                }else{
                    $return['invoice'] = false;
                }
            }elseif($data['type']=='expense'){
                $expense = Expense::where(['id'=>$data['id']])->first([
                    'id',
                'created_by',
                'amount',
                'description',
                ]);
                if($expense){
                        $profile = Profile::getProfile($expense->created_by);
                        $expense->created_by = ['id'=>$profile->id,'first_name'=>$profile->first_name,'middle_name'=>$profile->middle_name,'last_name'=>$profile->last_name];
                        $expense->unit = 'N/A';
                        $expense->unit_price = 'N/A';
                    $return['expense'] = $expense;
                }else{
                    $return['expense'] = false;   
                }

            }
            $return['slug'] = uniqid();   
            $return['retainer_deposit'] =  $this->getRetainerDeposit($data['case_id']);

            return response()->json(['data'=>$return,'status'=>TRUE],200);
        }
    }

    function getRetainerDeposit($case_id){
            $retdep = 0;
            $case = Cases::where(['id'=>$case_id])->first(['retainer_deposit']); 
            $retdep += $case->retainer_deposit;
            if($retdep){
                //find retainer deposit that came from cts
                $transaction = Transactions::where(['case_id'=>$case_id])->get(['retainer_deposit']); 
                if(!$transaction->isEmpty()){
                foreach ($transaction as $key => $value) {
                    $retdep += $value->retainer_deposit;
                }
                }

                //find and deduct invoice amount paid using retainer deposit to get final retainer deposit available
                $cinvoice = Cinvoice::where(['case_id'=>$case_id,'paid_via_retainer'=>1])->get(['paid']);
                if(!$cinvoice->isEmpty()){
                foreach ($cinvoice as $key => $value) {
                    $retdep -= $value->retainer_deposit;
                }
                }

                //find and deduct money from invoice vlaue
                $transaction1 = Transactions::where(['case_id'=>$case_id,'fund_via_rd'=>1])->get(['funds_received_payment']); 
                if(!$transaction1->isEmpty()){
                    foreach ($transaction1 as $key => $value) {
                        if($value->funds_received_payment >= 0)
                            $retdep -= $value->funds_received_payment;
                    }
                }
            }
           
            return $retdep;
    }

    public function createDraftInv(Request $request){
        $data = $request->all();

        $validator = Validator::make($data, [
            'client_id' => 'required',
            'created_by' => 'required',
            'case_id' => 'required',
            'slug' =>'required',
            'to_address'=>'required',
            'from_address'=>'required',            
            'type' => 'required', //invoice or expense
            'id' => 'required', //inv_id or expense_id
            'total' =>'required',
            'balance'=>'required',
            'retainer_deposit' =>'required',            
        ]);

        if ($validator->fails()) {
            return response()->json(['Message'=>'Please check Api Parameter','status'=>FALSE], 400);
        }else{
            $cinvoice = new Cinvoice;
            $data['paid_via_retainer'] = 0;    
            if(!isset($data['invoice_date'])){
             $data['invoice_date'] = date('Y-m-d h:i:s',strtotime('now'));
            }
            if(!isset($data['due_date'])){
             $data['due_date'] = date('Y-m-d h:i:s',strtotime('now'));
            }
            $data['amount_paid'] = 0;
            $data['amount'] = $data['total'];
            $data['paid'] = $data['amount_paid'];
            $data['ipay'] = $data['amount_paid'];
            $data['balance'] = $data['amount'] - $data['paid'];
            $data['created'] = date('Y-m-d h:i:s',strtotime('now'));
            if(!isset($data['notes'])){
             $data['notes'] = '';
            }

            if(is_array($data['to_address'])){
                $str = "";
                foreach ($data['to_address'] as $key => $value) {
                    if($key == 0){
                        $str .= $value;
                    }
                    else{
                        $str .= ",".$value;
                    }
                }
                $data['to_address'] = $str;
            }

            if(is_array($data['from_address'])){
                $str = "";
                foreach ($data['from_address'] as $key => $value) {
                    if($key == 0){
                        $str .= $value;
                    }
                    else{
                        $str .= ",".$value;
                    }
                }
                $data['from_address'] = $str;
            }

            $data['lbal'] = $data['retainer_deposit'];

                    if($data['balance']>0 && $data['balance']<$data['total']){
                        $data['status'] = "Partial";
                    }
                    if($data['balance']==0){
                        $data['status'] = "Paid";
                    }
                    if($data['amount_paid']==0){
                        $data['status'] = "Unpaid";
                    }      
            $cinvoice->fill($data);
            if($cinvoice->save()){
                        if($data['type']=='invoice'){
                            $inv = Invoice::find($data['id']);
                            $inv->to_invoice = 1;
                            $inv->cinvoice_id = $cinvoice->id;
                            $inv->save();
                            return response()->json(['Message'=>'Successfully saved','status'=>TRUE],200);
                        }
                        if($data['type']=='expense'){
                            $inv = Expense::find($data['id']);
                            $inv->to_invoice = 1;
                            $inv->cinvoice_id = $cinvoice->id;
                            $inv->save();
                            return response()->json(['Message'=>'Successfully saved','status'=>TRUE],200);
                        } 
                
            }
            else{
                return response()->json(['Message'=>'Failed to save','status'=>FALSE],400);  
            }
            
        }
    }

    public function getDraftInv(Request $request){
        $data = $request->all();
        $validator = Validator::make($data, [
            'client_id' => 'required',
            'user_id' => 'required',
            'case_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['Message'=>'Please check Api Parameter','status'=>FALSE], 400);
        }else{
            $query['case_id'] = $data['case_id'];
            $query['created_by'] = $data['user_id'];
            $query['client_id'] = $data['client_id'];
            $query['is_draft']= 1;
            $cinvoice = Cinvoice::getCinvoiceByQuery($query);
            $ret = FALSE;

            if(!$cinvoice)
                return response()->json(['Message'=>'No invoice available','status'=>FALSE], 400);

            foreach ($cinvoice as $key => $cinvoice) {
                $profile = Client::getClientProfile($cinvoice->client_id);
                $email = User::getUserbyID($cinvoice->client_id);
                
                $return['client'] = ['id'=>$profile->id,'first_name'=>$profile->first_name,'middle_name'=>$profile->middle_name,'last_name'=>$profile->last_name];
                $return['email'] = $email->email;
                $return['tag'] = ($cinvoice->is_draft) ? 'Draft' : 'Sent';
                $return['invoice_date'] = date("m/d/Y",strtotime($cinvoice->invoice_date));
                $return['paid_amount'] = $cinvoice->paid;
                $return['balance'] = $cinvoice->balance;
                if($cinvoice->balance=='0'){
                    if($cinvoice->is_draft==1){
                        $cinvoice->status = 'Draft';
                    }else{
                        $cinvoice->status = 'Paid';
                    }
                    
                }else{
                    if($cinvoice->balance>0){
                        $cinvoice->status = 'Unpaid / Pending ($'.number_format($cinvoice->balance,2).')'; 
                    }
                    
                }
                $return['status'] = $cinvoice->status;
                $return['id'] = $cinvoice->id;
                $return['slug'] = $cinvoice->slug;
                $ret[] = $return;
            }

            return response()->json(['data'=>$ret,'status'=>TRUE], 200);
        }
    }

    public function editDraftInv(Request $request){
        $data = $request->all();

        $validator = Validator::make($data, [
            'client_id' => 'required',
            'user_id' => 'required',
            'case_id' => 'required',
            'id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['Message'=>'Please check Api Parameter','status'=>FALSE], 400);
        }else{
            $client_id = $data['client_id'];
            $user_id= $data['user_id'];
            
            $profile = Profile::getProfile($user_id);
            if(!$profile)
                return response()->json(['Message'=>'Lawyer ID is not a Client','status'=>FALSE], 400);
            $return['from'] = ['id'=>$profile->user_id,'first_name'=>$profile->first_name,'middle_name'=>$profile->middle_name,'last_name'=>$profile->last_name];
            
            $cprofile = Client::getClientProfile($client_id);
            if(!$cprofile)
                return response()->json(['Message'=>'Client ID is not a Client','status'=>FALSE], 400);
            $return['to'] = ['id'=>$cprofile->user_id,'first_name'=>$cprofile->first_name,'middle_name'=>$cprofile->middle_name,'last_name'=>$cprofile->last_name];
            
            $invoice = Invoice::where(['cinvoice_id'=>$data['id']])->first([
                    'description',
                    'unit_price',
                    'unit',
            ]);
            if($invoice){
                $return['inv_details'] = $invoice;
                $return['inv_details']['type'] = 'Billing/Time Entry';
            }

            $expense = Expense::where(['cinvoice_id'=>$data['id']])->first([
                'amount',
                'description',
                ]);
            if($expense){
                $return['inv_details'] = $expense;
                $return['inv_details']['unit'] = 'N/A';
                $return['inv_details']['unit_price'] = 'N/A';
                $return['inv_details']['type'] = 'Expense';
            }
            $cinvoice = Cinvoice::where(['id'=>$data['id']])->first([  
                'id',
                'to_address',
                'from_address',
                'amount as total',
                'balance',
                'paid',
                'notes',
                'invoice_date',
                'due_date',
                'slug'
            ]);
            $return['cinvoice'] = $cinvoice;
            return response()->json(['data'=>$return,'status'=>TRUE],200);
        }
    }

    public function updateDraftInv(Request $request){
        $data = $request->all();

        $validator = Validator::make($data, [
            'client_id' => 'required',
            'created_by' => 'required',
            'case_id' => 'required',
            'slug' =>'required',
            // 'to_address'=>'required',
            // 'from_address'=>'required',
            'id' => 'required', //inv_id or expense_id
            'total' =>'required',
            'balance'=>'required',    
        ]);

        if ($validator->fails()) {
            return response()->json(['Message'=>'Please check Api Parameter','Error'=>$validator->errors(),'status'=>FALSE], 400);
        }else{
            $cinvoice = Cinvoice::find($data['id']);
            $data['paid_via_retainer'] = 0;    
            if(!isset($data['invoice_date'])){
             $data['invoice_date'] = date('Y-m-d h:i:s',strtotime('now'));
            }
            if(!isset($data['due_date'])){
             $data['due_date'] = date('Y-m-d h:i:s',strtotime('now'));
            }
            if(!isset($data['amount_paid']))
                $data['amount_paid'] = 0;
            $data['amount'] = $data['total'];
            $data['paid'] = $data['amount_paid'];
            $data['balance'] = $data['amount'] - $data['paid'];
            $data['created'] = date('Y-m-d h:i:s',strtotime('now'));
            if(!isset($data['notes'])){
             $data['notes'] = '';
            }

            if(isset($data['to_address'])){
                if(is_array($data['to_address'])){
                    $str = "";
                    foreach ($data['to_address'] as $key => $value) {
                        if($key == 0){
                            $str .= $value;
                        }
                        else{
                            $str .= ",".$value;
                        }
                    }
                    $data['to_address'] = $str;
                }
                // if($data['to_address']==''){
                // $client_id = $data['client_id'];
                // $data['to_address'] = DB::table('client as u')
                //         ->Join('address as a','a.user_id','=','u.user_id')
                //         ->select('u.id','u.first_name','u.middle_name','u.last_name','u.ftype','u.company_name','a.address1','a.address2','a.city','a.state','a.zip')
                //         ->where(['u.user_id'=>$client_id])
                //         ->get();  
                // }
            }
            
            if(isset($data['from_address'])){
                if(is_array($data['from_address'])){
                    $str = "";
                    foreach ($data['from_address'] as $key => $value) {
                        if($key == 0){
                            $str .= $value;
                        }
                        else{
                            $str .= ",".$value;
                        }
                    }
                    $data['from_address'] = $str;
                }
                // if($data['from_address']==''){
                // $user_id = $data['created_by'];
                // $data['from_address'] = DB::table('users as u')
                //         ->Join('profile as p','p.user_id','=','u.id')
                //         ->Join('firm as f','f.id','=','u.firm_id')
                //         ->Join('address as a','a.user_id','=','u.id')
                //         ->select('u.id','p.first_name','p.middle_name','p.last_name','f.name AS firm','a.address1','a.address2','a.city','a.state','a.zip')
                //         ->where(['u.id'=>$user_id])
                //         ->get();
                // }
            }
            


                    if($data['balance']>0 && $data['balance']<$data['total']){
                        $data['status'] = "Partial";
                    }
                    if($data['balance']==0){
                        $data['status'] = "Paid";
                    }
                    if($data['amount_paid']==0){
                        $data['status'] = "Unpaid";
                    }      
            $cinvoice->fill($data);
            if($cinvoice->save()){
                    return response()->json(['Message'=>'Successfully saved','status'=>TRUE],200);
            }
            else{
                return response()->json(['Message'=>'Failed to save','status'=>FALSE],400);  
            }
            
        }
    }

    public function saveDraftInv(Request $request){
        $data = $request->all();

        $validator = Validator::make($data, [
            'id' =>'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['Message'=>'Please check Api Parameter','status'=>FALSE], 400);
        }else{
            $cinvoice = Cinvoice::find($data['id']);
            $cinvoice->is_draft = 0;
            if(isset($data['is_sent_no_clnt'])) $cinvoice->is_sent_no_clnt = 1;
            else $cinvoice->is_sent_no_clnt = 0;

            if($cinvoice->save()){
                    return response()->json(['Message'=>'Successfully saved','status'=>TRUE],200);
            }
            else{
                return response()->json(['Message'=>'Failed to save','status'=>FALSE],400);  
            }
        }
    }

    public function delDraftInv(Request $request){
        $data = $request->all();

        $validator = Validator::make($data, [
            'id' =>'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['Message'=>'Please check Api Parameter','status'=>FALSE], 400);
        }else{
            $cinvoice = Cinvoice::find($data['id']);
            
            if($cinvoice->delete()){
                    return response()->json(['Message'=>'Successfully deleted','status'=>TRUE],200);
            }
            else{
                return response()->json(['Message'=>'Failed to save','status'=>FALSE],400);  
            }
        }
    }

    public function getctsDepositAndPayment(Request $request){
        $data = $request->all();

        $validator = Validator::make($data, [
            'case_id' =>'required',
            'client_id' =>'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['Message'=>'Please check Api Parameter','status'=>FALSE], 400);
        }else{
            $return['retainer_deposit'] = $this->getRetainerDeposit($data['case_id']);
            $return['invoices'] = DB::table('cinvoice')
                ->where('balance', '>', 0)
                ->orwhere('paid', 0)
                ->where('is_draft', 0)
                ->where('case_id',$data['case_id'])
                ->where('client_id',$data['client_id'])
                ->get([
                    'id',
                    'slug',
                    'balance',
                ]);
           
            return response()->json(['data'=>$return,'status'=>TRUE], 200);
        }
    }

    public function setctsDepositAndPayment(Request $request){
        $data = $request->all();

        $validator = Validator::make($data, [
            'case_id' =>'required',
            'client_id' =>'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['Message'=>'Please check Api Parameter','status'=>FALSE], 400);
        }else{
            $transaction = new Transactions;
            $data['created'] = date('Y-m-d h:i:s',strtotime('now'));
            $data['modified'] = date('Y-m-d h:i:s',strtotime('now'));
            
            
            if(isset($data['funds_amount'])) $data['funds_received_payment'] = $data['funds_amount'];
            else $data['funds_received_payment'] = 0;

            if(isset($data['retainer_amount'])) $data['retainer_deposit'] = $data['retainer_amount'];
            else $data['retainer_deposit'] = 0;

            if(isset($data['cinvoice_id'])){
                $cinvoice = Cinvoice::find($data['cinvoice_id']);
                
                $amount = $cinvoice->amount;
                $paid = $cinvoice->paid;

                $cinvoice->paid = $paid+$data['funds_received_payment'];
                $cinvoice->balance = $amount-($paid+$data['funds_received_payment']);

                if(!$cinvoice->save()){                
                    return response()->json(['Message'=>'Failed to invoice on transaction','status'=>FALSE],400);  
                }
            }
            $transaction->fill($data);
            if($transaction->save()){                
                return response()->json(['Message'=>'Successfully save','status'=>TRUE],200);
            // return response()->json(['Message'=>$transaction,'status'=>TRUE],200);
            }
            else{
                return response()->json(['Message'=>'Failed to save','status'=>FALSE],400);  
            }
        }
    }

    public function getPendingInv(Request $request){
        $data = $request->all();

        $validator = Validator::make($data, [
            'case_id' =>'required',
            'client_id' =>'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['Message'=>'Please check Api Parameter','status'=>FALSE], 400);
        }else{
            $cinvoice = DB::table('cinvoice')
                ->where('balance', '>', 0)
                ->orwhere('paid', 0)
                ->where('is_draft', 0)
                ->where('case_id',$data['case_id'])
                ->where('client_id',$data['client_id'])
                ->get([
                    'created',
                    'slug',
                    'paid',
                    'balance',
                    'status',
                ]);
            if(!$cinvoice)
                return response()->json(['Message'=>'No available Pending Invoice','status'=>FALSE], 400);
            foreach ($cinvoice as $key => $cinvoice) {
                $cinvoice->status = 'Unpaid / Pending ($'.number_format($cinvoice->balance,2).')'; 
                $cinvoice->amount = $cinvoice->paid+$cinvoice->balance;
                unset($cinvoice->paid);
                unset($cinvoice->balance);
                $ret[] = $cinvoice;
            }
            return response()->json(['data'=>$ret,'status'=>true], 200);
        }
    }

    public function getPaidInv(Request $request){
        $data = $request->all();

        $validator = Validator::make($data, [
            'case_id' =>'required',
            'client_id' =>'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['Message'=>'Please check Api Parameter','status'=>FALSE], 400);
        }else{
            $cinvoice = DB::table('cinvoice')
                ->where('amount', '=',  DB::raw('paid'))
                ->where('is_draft', 0)
                ->where('case_id',$data['case_id'])
                ->where('client_id',$data['client_id'])
                ->get([
                    'created',
                    'slug',
                    'paid',
                    'balance',
                ]);

            if(!$cinvoice)
                return response()->json(['Message'=>'No available paid invoice','status'=>FALSE], 400);
            foreach ($cinvoice as $key => $cinvoice) {
                $cinvoice->amount = $cinvoice->paid+$cinvoice->balance;
                unset($cinvoice->balance);
                $ret[] = $cinvoice;
            }
            return response()->json(['data'=>$ret,'status'=>true], 200);
        }
    }


}
