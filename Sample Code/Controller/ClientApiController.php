<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Client;
use Illuminate\Support\Facades\Auth;
use Validator;
use App\Authassignment;
use App\Cases;
use App\CaseUser;
use App\User;
class ClientApiController extends Controller
{
    public function getClient(Request $request)
    {
        $data = $request->all();
        if(isset($data['user_id'])){
            $user_id = $data['user_id'];
    		$authassignment = Authassignment::getAuthassignment($user_id);
            foreach ($authassignment as $key => $authass) {
                if($authass->itemname != 'Client'){
                    $case_users = CaseUser::getCaseuserByID($user_id);
                        foreach ($case_users as $key => $case_user) {
                            $case = Cases::getCasesByCaseID($case_user->case_id);
                            foreach ($case as $key => $value) {
                                $clients = Client::getClientByID($value->user_id);
                                
                                // foreach ($clients as $key => $value1) {
                                    if($clients){ 
                                            $ret[$value->user_id] = $clients; 
                                    }
                                // }
                            }
                        }
                        $return = array();
                        $return = array_merge($return,$ret);
                    return response()->json(['data'=>$return,'status'=>TRUE], 200);
                }else{
                    return response()->json(['Message'=>'Client not allowed','status'=>FALSE], 400);
                }
            }
        }else{
            return response()->json(['Message'=>'Please check Parameter','status'=>FALSE], 400);
        }
		
    }

    public function setClient(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'unique:client|max:255',
            'first_name' => 'required',
            'last_name' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['Message'=>'Please check Parameter','status'=>FALSE], 400);
        }
        $data = $request->all();
        if(!isset($data['ftype']))
            $data['ftype'] = 'INDIVIDUAL';
        if(!isset($data['opening_balance']))
            $data['opening_balance'] = 0;
        if(!isset($data['created_by']))
            $data['created_by'] = Auth::id();

        $data['user_id'] = Auth::id();
        $data['created'] = date_create();
        $data['modified'] = '0000-00-00';
     
    	$return = Client::setClient($data);  
        if($return){
            return response()->json(['Message'=>'Account Successfully Added','status'=>TRUE],200);
        }else{
            return response()->json(['Message'=>'Account Failed to Add','status'=>FALSE],400);
        }

    }

    public function updateClient(Request $request)
    {
        $data = $request->all();

        if(isset($data['_method'])) unset($data['_method']);
        if(!$data)
            return response()->json(['Message'=>'Please check parameter','status'=>FALSE],400);
        $data['user_id'] = Auth::id();
        $data['modified'] = date_create();
        $return = Client::setClient($data);
        if($return){
            return response()->json(['Message'=>'Account Successfully Updated','status'=>TRUE],200);
        }else{
            return response()->json(['Message'=>'Account Failed to Update','status'=>FALSE],400);
        }
    }

    public function deleteClient()
    {
        $return = Client::deleteClient(Auth::id());
        if($return){
            return response()->json(['Message'=>'Account Successfully Updated','status'=>TRUE],200);
        }else{
            return response()->json(['Message'=>'Account Failed to Update','status'=>FALSE],400);
        }
    }
}
