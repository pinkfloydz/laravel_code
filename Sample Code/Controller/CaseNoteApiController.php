<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\CaseNote;
use App\Cases;
use App\Client;
use App\Authassignment;
use App\Profile;
use Validator;
class CaseNoteApiController extends Controller
{

    public function getCaseNotes(Request $request){
    	$data = $request->all();
    	$validator = Validator::make($data, [
            'case_id' => 'required',
            'user_id' => 'required',
            'client_id' => 'required',
        ]);

        

        if (!$validator->fails()){
    		if(!isset($data['entry_time'])){ 
    			// $data['entry_time'] = date('Y-m-d H:i:s', strtotime( date('Y-m-d')." - 1 day" ));
    			$data['entry_time'] = date('Y-m-d 00:00:00',strtotime('now'));
    		}
    		$allowed = $this->isAllowed($data['user_id']);
	        if($allowed){
                    $c_profile = Client::getClientProfile($data['client_id']);
			    	$casenotes= CaseNote::getCaseNote($data['case_id'],$data['entry_time'],$data['user_id'],$c_profile->id);
			    	if($casenotes){
			    		$datas = FALSE;
				    	foreach ($casenotes as $key => $casenote) {
				    		// var_dump($casenote->client_id);
				    		$client_profile = Client::getClientByID($casenote->client_id);
				    		$profile = Profile::getProfile($casenote->created_by);
				    		$client_case = Cases::getCasesByCaseID($data['case_id']);
							foreach ($client_case as $key => $value) {
								$return['case_name'] = ucwords($value->case_no);
							}
							$return['subject'] = $casenote->subject;
							$return['description'] = $casenote->description;
							$return['entry_date'] = $casenote->entry_time;
							$return['created_on'] = $casenote->created;
							$return['client_name'] = $client_profile->last_name.', '.$client_profile->first_name;
							$return['created_by'] = $profile->last_name.', '.$profile->first_name;
						    $datas[] = $return;
			    		}
			    		if($datas)
			    			return response()->json(['data'=>$datas,'status'=>TRUE],200);	
			    		else
			    			return response()->json(['Message'=>'No Notes Available','status'=>FALSE],400);			
		    	}else{
		    		return response()->json(['Message'=>'No Notes Available','status'=>FALSE],400);	
		    	}
	    	}else{	
	    		return response()->json(['Message'=>'Client is not allowed','status'=>FALSE],400);		
	    	}
    	}else{
    		return response()->json(['Message'=>'Please check Parameter','status'=>FALSE],400);	
    	}
    }

    public function setCaseNotes(Request $request){
    	$data = $request->all();

    	$validator = Validator::make($data, [
            'case_id' => 'required',
            'user_id' => 'required',
            'client_id' => 'required',
            'subject' => 'required',
            'entry_date' => 'required',
        ]);

        

        if (!$validator->fails()){
        	if(isset($data['notes'])){
        		$data['description'] = $data['notes'];
        		unset($data['notes']);
        	}
        	$data['created_by'] = $data['user_id'];
        	unset($data['user_id']);
        	$data['created'] = date_create();
        	$data['modified'] = date_create();
        	$client = Client::getClientProfile($data['client_id']);
        	$data['client_id'] = $client->id;
        	if(!isset($data['entry_date'])){
                $data['entry_time'] = date_create();
            }else{
                $data['entry_time'] = $data['entry_date'];
            }
        	$casenote = new CaseNote;
        	$casenote->fill($data);
        	if($casenote->save()){
        		return response()->json(['status'=>TRUE],200);	
        	}else{
        		return response()->json(['Message'=>'Failed to Save','status'=>FALSE],400);		
        	}
    	}else{
    		return response()->json(['Message'=>'Please check Parameter','status'=>FALSE],400);	
    	}
    }

    public function isAllowed($user_id){
    	$authassignment = Authassignment::getAuthassignment($user_id);
            if(!$authassignment->isEmpty()){ // check if sender exist as user
                foreach ($authassignment as $key => $auth) {
                	if($auth->itemname != 'Client'){
                        return TRUE;
                    }else{
                        return FALSE;
                    }
                }
                return FALSE;
            }else{
            	return FALSE;
            }
    }

    
}
