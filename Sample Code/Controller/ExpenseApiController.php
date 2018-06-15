<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Expense;
use Validator;
class ExpenseApiController extends Controller
{
    public function setExpense(Request $request){
    	$data = $request->all();

        $validator = Validator::make($data, [
            'client_id' => 'required',
            'user_id' => 'required',
            'case_id' => 'required',
            'description' => 'required',
            'amount' => 'required',
            'date' => 'required',
			// 'file'=> 'mimes:jpg,gif,png,doc,docx,pdf,ppt,pptx,odt,odp,rtf,wpd',
        ]);

        if ($validator->fails()) {
            return response()->json(['Message'=>'Please check Api Parameter','Error'=>$validator->erro,'status'=>FALSE], 400);
        }else{

        	$expense = new Expense;
        	$data['created'] = date('Y-m-d h:i:s' , strtotime('now'));
            $data['modified'] = date('Y-m-d h:i:s' , strtotime('now'));
            $data['expense_date'] = $data['date'];
            $data['created_by'] = $data['user_id'];
            if(isset( $data['file'] )){
            	$file = $data['file'];
            	$fileName= $file->getClientOriginalName();
            	$fileName = preg_replace('/[^A-Za-z0-9.\-]/', '', $fileName);
				$fileName = strtolower($fileName);                                
            	$data['filename'] = mt_rand(1000000, 9999999).'-'.$fileName;
            	$data['doc_name'] = $file->getClientOriginalName();
            }
            $expense->fill($data);
            if($expense->save()){
            	if(isset( $data['file'] )){
            		$request->file('file')->move(storage_path('documents'),$data['filename']);
            		return response()->json(['Message'=>'Successfully saved and uploaded','status'=>TRUE],200);
            	}
                return response()->json(['Message'=>'Successfully saved','status'=>TRUE],200);
            }else
                return response()->json(['Message'=>'Failed to save','status'=>FALSE],400);  
            
        }
    }
}
