<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Http\Requests;
use Illuminate\Support\Facades\Auth;
use App\User;
class UserApiController extends Controller
{
    // public $token;
    // public function __construct(Request $request)
    // {
    //     $this->token['_token'] = $request->session()->get('_token');
    // }
    
    // public function getKey(Request $request)
    // {// get token for any post request
    //   	return response()->json($this->token, 200);
    // }

    public function getUser()
    {
	      $return = Auth::user();
	      return response()->json($return, 200); 
    }

    public function setUser(Request $request)
    {
    	$data = $request->all();
        $validator = Validator::make($request->all(), [
            'email' => 'required|unique:users|max:255',
            'password' => 'required',
            'firm_id' => 'required',
        ]);
        
        if (!$validator->fails()) {
            $user = new User;
          	
            $user->fill($data);
          
          	// $return['user'] = $user;
          	$user['is_active'] = 1;
          	$user['status'] = 1;
          	$user['created'] = date_create();

          	if($user->save()){
          		Auth::login($user);
            	$return['Message'] = 'Successfully Saved';
            	$return['return'] = TRUE;
            	return response()->json($return, 200);
          	}else{
            	return response()->json(['Message'=>'Failed to save user','status'=>FALSE], 400);
          	}
        }else{
            return response()->json(['Message'=>'Please check Parameter','status'=>FALSE], 400);
        }
    }

    public function updateUser(Request $request)
    {
        if(!$data) return response()->json(['Message'=>'Please check Parameter.','status'=>TRUE], 400);   
    	
        $data = $request->all();
        if(isset($data['email']))
            unset($data['email']);
    	 $data['modified'] = date_create();
        $return = User::updateUser(Auth::id(),$data);
    	if($return){
    		return response()->json(['Message'=>'User Successfully Updated.','status'=>TRUE], 200);	
    	}else{
    		return response()->json(['Message'=>'User Update Failed.','status'=>FALSE], 400);	
    	}
    	
    }

    public function deleteUser()
    {    	
       
    	$return = User::deleteUser(Auth::id());
    	if($return){
    		return response()->json(['Message'=>'User Successfully Deleted.','status'=>TRUE], 200);	
    	}else{
    		return response()->json(['Message'=>'User Delete Failed.','status'=>FALSE], 400);	
    	}
    }

    public function forgotpassword(Request $request)
    {
        $data = $request->all();
        if(isset($data['email'])){
            return response()->json(['email'=>$data['email']],200);
        }else{
            return response()->json(['Message'=>'Please input email','status'=>FALSE],400);
        }
        
    }

    
}
