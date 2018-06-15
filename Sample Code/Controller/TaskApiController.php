<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Validator;
use App\Task;
use App\Cases;
use App\Client;
use App\Profile;
class TaskApiController extends Controller
{
	

    public function getTask(Request $request)
    {
    	$data = $request->all();
    	$validator = Validator::make($data, [
            'case_id' => 'required',
            'user_id' => 'required',
            'client_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['Message'=>'Please check Api Parameter','status'=>FALSE], 400);
        }else{
            if(!isset($data['entry_date'])) $data['entry_date'] = date('Y-m-d h:i:s',strtotime('now'));

            $entry_date = date('Y-m-d h:i:s',strtotime(''.$data['entry_date']));
        	$task = Task::getTask($data['user_id'],$data['case_id'],$entry_date);
            if($task){
            	foreach ($task as $key => $task) {                   

            		$cases = Cases::getCasesByCaseID($task->case_id);
            		foreach ($cases as $key => $value) {
            			$return['case_name'] = $value->case_no;
            			$return['case_type'] = $value->case_type;

            		}

            		$return['task'] = $task->subject;
            		$return['description'] = $task->description;
            		$return['id'] = $task->id;
            		$return['entry_date'] = $task->entry_date;	
            		$return['created'] = $task->created;
                    $client = Client::getClientProfile($data['client_id']);
                    $name['first_name'] = $client->first_name;
                    $name['middle_name'] = $client->middle_name;
                    $name['last_name'] = $client->last_name;
                    $return['client_name'] = $name;
                    $att = Profile::getProfile($data['user_id']);
                    $name['first_name'] = $att->first_name;
                    $name['middle_name'] = $att->middle_name;
                    $name['last_name'] = $att->last_name;
                    $return['att_name'] = $name;
                    $ret[] = $return;
            	}
        	        	return response()->json(['status'=>TRUE,'data'=>$ret],200);
            }else{
                return response()->json(['Message'=>'No task available','status'=>FALSE], 400);
            }
        }
    }

    public function setTask(Request $request)
    {
    	$data = $request->all();
    	$validator = Validator::make($data, [
            'case_id' => 'required',
            'user_id' => 'required',
            'subject' => 'required',
            'entry_date' => 'required',
        ]);

        if ($validator->fails()) {
        	return response()->json(['Message'=>'Please check Api Parameter','status'=>FALSE], 400);
        }else{
        	if(isset($data['task'])){
        		$data['description'] = $data['task'];
        		unset($data['task']);
        	}
        	$data['created'] = date_create();
        	$data['modified'] = date_create();
        	$data['created_by'] = $data['user_id'];
        	if(!isset($data['entry_date'])) $data['entry_date'] = date_create();
        	// for Client type
        	$data['entry_type'] = 'Client'; // Client , Internal ,Personal
        	unset($data['user_id']);
        	$task = new Task;
        	$task->fill($data);
        	if($task->save()){
        		return response()->json(['status'=>TRUE],200);	
        	}else{
        		return response()->json(['Message'=>'Failed to Save','status'=>FALSE],400);		
        	}
        }
    }

    public function getOTSTask(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'user_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['Message'=>'Please check Api Parameter','status'=>FALSE], 400);
        }else{
            if(!isset($data['entry_date'])) $data['entry_date'] = date('Y-m-d h:i:s',strtotime('now'));

            $entry_date = date('Y-m-d h:i:s',strtotime(''.$data['entry_date']));
            $task = Task::getOTSTask($data['user_id'],$entry_date);
            if($task){
                foreach ($task as $key => $task) {                   
                    $return['id'] = $task->id;
                    $return['subject'] = $task->subject;
                    $return['description'] = $task->description;
                    $return['entry_date'] = $task->entry_date;  
                    $return['created'] = $task->created;
                    
                    $att = Profile::getProfile($data['user_id']);
                    $name['first_name'] = $att->first_name;
                    $name['middle_name'] = $att->middle_name;
                    $name['last_name'] = $att->last_name;
                    $return['att_name'] = $name;
                    
                    $cases = Cases::getCasesByCaseID($task->case_id);
                    foreach ($cases as $key => $value) {
                        $return['case_name'] = $value->case_no;
                        $return['case_type'] = $value->case_type;
                        $client = Client::getClientByID($value->user_id);
                        $name['first_name'] = $client->first_name;
                        $name['middle_name'] = $client->middle_name;
                        $name['last_name'] = $client->last_name;
                        $return['client_name'] = $name;
                    }
                    
                    
                    
                    $ret[] = $return;
                }
                        return response()->json(['status'=>TRUE,'data'=>$ret],200);
            }else{
                return response()->json(['Message'=>'No task available','status'=>FALSE], 400);
            }
        }
    }
}
