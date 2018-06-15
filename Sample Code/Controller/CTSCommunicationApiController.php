<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Validator;
use DB;
use App\Authassignment;
use App\Client;
use App\Profile;
class CTSCommunicationApiController extends Controller
{

	function getMail($com){
                        foreach ($com as $key => $coms) {
                        
                        // get sender
                        $auth = Authassignment::getAuthassignment($coms->created_by);
                        foreach ($auth as $key => $auth) {
                            if($auth->itemname == 'Client'){
                                $profile1 = Client::getClientProfile($coms->created_by);
                            }else{
                                $profile1 = Profile::getProfile($coms->created_by);                                
                            }                            
                        }
                        $coms->sender = [
                            'first_name'=>$profile1->first_name,
                            'middle_name'=>$profile1->middle_name,
                            'last_name'=>$profile1->last_name
                            ];

                         $reciever = DB::table('communication_user as a')
                        ->Join('communication as b','a.communication_id','=','b.id')
                        ->select('b.id','a.user_id','b.case_id','b.subject','b.message','b.created_by','b.entry_date')
                        ->where(['b.id'=>$coms->id])
                        ->orderBy('b.id', 'desc')
                        // ->where('case_id','<>','null')
                        ->get();  

                        // foreach ($reciever as $key => $value) {

                        // }
                      
                        $rec = null;
                        $prof = null;
                        $profs = null;
                        foreach ($reciever as $key => $value) {
                           $auth = Authassignment::getAuthassignment($value->user_id);
                            foreach ($auth as $key => $auth) {
                                if($auth->itemname == 'Client'){
                                    $profile2 = Client::getClientProfile($value->user_id);
                                }else{
                                    $profile2 = Profile::getProfile($value->user_id);
                                }
                                    $prof['first_name'] = $profile2->first_name;
                                    $prof['middle_name'] = $profile2->middle_name;
                                    $prof['last_name'] = $profile2->last_name;
                                    $prof['id'] = $value->user_id;
                                    if($profile1->user_id != $value->user_id){
                                        $prof['sender'] = 'no';
                                    }else{
                                        $prof['sender'] = 'yes';
                                    }
                                    
                                                               
                            }
                            $profs[] = $prof;
                        }
                        
                        $coms->reciever = $profs;
                    }

                    return $com;
    }

    public function getInboxMail(Request $request)
    {
    	$data = $request->all();
    	$validator = Validator::make($data, [
            'case_id' => 'required|max:255',
            'user_id' => 'required|max:255',
            'client_id' => 'required|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['Message'=>'Please check Parameter','status'=>FALSE], 400);
        }else{
        	$query['case_id'] = $data['case_id'];
        	$query['user_id'] = $data['user_id'];
            $query['created_by'] = $data['client_id'];
        	$auth = Authassignment::getAuthassignment($data['user_id']);
        	$att = 0;

        	if($auth){
        	foreach ($auth as $key => $auth) {
        		if($auth->itemname == 'Admin'){
        			$att = 1;
        		}
        	}


        	if($att == 1){
        	 		$com = DB::table('communication_user as a')
                        ->Join('communication as b','a.communication_id','=','b.id')
                        ->select('b.id','a.user_id','b.case_id','b.subject','b.message','b.created_by','b.entry_date')
                        ->where($query)
                        ->whereNull('in_reply')
                        ->orderBy('b.id', 'desc')
                        // ->where('case_id','<>','null')
                        ->get();   

                    if(!$com) return response()->json(['Message'=>'No inbox Available','status'=>FALSE], 400);   
                    $return = $this->getMail($com);
                    return response()->json(['data'=>$com,'status'=>TRUE], 200);
            }else{
            	return response()->json(['status'=>FALSE,'Message'=>'Client Not Allowed'], 400);
            }

	        }else{
	        	return response()->json(['status'=>FALSE,'Message'=>"User doesn't exist"], 400);
	        }
        }
    }

    public function getSentboxMail(Request $request)
    {
    	$data = $request->all();
    	$validator = Validator::make($data, [
            'case_id' => 'required|max:255',
            'user_id' => 'required|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['Message'=>'Please check Parameter','status'=>FALSE], 400);
        }else{
        	$query['case_id'] = $data['case_id'];
        	$query['created_by'] = $data['user_id'];
        	$auth = Authassignment::getAuthassignment($data['user_id']);
        	$att = 0;
        	if(!$auth->isEmpty()){
        	foreach ($auth as $key => $auth) {
        		if($auth->itemname == 'Admin'){
        			$att = 1;
        		}
        	}
        	if($att == 1){
        		$com = DB::table('communication_user as a')
                        ->Join('communication as b','a.communication_id','=','b.id')
                        ->select('b.id','a.user_id','b.case_id','b.subject','b.message','b.created_by','b.entry_date')
                        ->where($query)
                        ->orderBy('b.id', 'desc')
                        // ->where('case_id','<>','null')
                        ->get();   

                    if(!$com) return response()->json(['Message'=>'No communication Available','status'=>FALSE], 400);   

                    foreach ($com as $key => $com) {
                        $coms[$com->id] = $com;
                    }
                    $arr = array();
                    $arr = array_merge($arr,$coms);
                    $return = $this->getMail($arr);
                    return response()->json(['data'=>$return,'status'=>TRUE], 200);
           	}else{
            	return response()->json(['status'=>FALSE,'Message'=>'Client Not Allowed'], 400);
            }

	        }else{
	        	return response()->json(['status'=>FALSE,'Message'=>"User doesn't exist"], 400);
	        }
        }
    }
}
