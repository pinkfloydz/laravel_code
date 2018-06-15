<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Helper;
use App\Communication;
use App\Communication_user;
use App\User;
use App\Authassignment;
use App\Profile;
use App\Client;
use App\Cases;
use DB;
use Validator;
use Config;
use Session;
use Illuminate\Pagination\Paginator;
use DateTime;
class CommunicationApiController extends Controller
{
    function getMail($id,$com,$flag){
        // $auth = Authassignment::getAuthassignment($id);
        $client = 0;
        // foreach ($auth as $key => $auth) {
        //     if($auth->itemname == 'Client'){
        //         $client = 1;
        //     }
        // }
            // if($client == 1){                
                  
                    if(!$com) return FALSE;
                    $coms = array();
                    foreach ($com as $key => $value) {
                        $coms[$value->id] = $value;
                    }

                    foreach ($coms as $key => $coms) {
                        
                        // get sender
                        $auth = Authassignment::getAuthassignment($coms->created_by);
                        $client = 0;
                        foreach ($auth as $key => $auth) {
                            if($auth->itemname == 'Client'){
                                $client = 1;
                            }
                        }
                        // foreach ($auth as $key => $auth) {
                            if($client == 1){
                                $profile1 = Client::getClientProfile($coms->created_by);
                            }else{
                                $profile1 = Profile::getProfile($coms->created_by);                                
                            }                            
                        // }
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
                        $return[] = $coms;
                    }
                    // var_dump($rec_data);
                    return $return;
                    // return response()->json(['data'=>$return,'status'=>TRUE], 200);
            // }else{
            //     return response()->json(['Message'=>'Not a Client','status'=>FALSE], 400);        
            // }
        // }
        // return response()->json($data);
        // return response()->json(['data'=>$return,'status'=>TRUE], 200);   
    }
    public function setReadMail(Request $request){
        $data = $request->all();
        $validator = Validator::make($data, [
            'com_id' => 'required|max:255',
            'user_id' => 'required|max:255',
        ]);

        if ($validator->fails()) {          
            return response()->json(['Message'=>'Please check Api Parameter','status'=>FALSE], 400);
        }else{
            $query['communication_id'] = $data['com_id'];
            $query['user_id']= $data['user_id'];
            $com = Communication_user::where($query)->first();
            $com->is_read = 1;
            if($com->save())
                return response()->json(['status'=>TRUE],200);
            else
                return response()->json(['status'=>FALSE],400);
        }
    }
    public function getInboxMail(Request $request){
        $data = $request->all();
        $validator = Validator::make($data, [
            'user_id' => 'required|max:255',
            'page_no' => 'required|max:255',
        ]);

        if ($validator->fails()) {          
            return response()->json(['Message'=>'Please check Api Parameter','status'=>FALSE], 400);
        }else{
            $id = $data['user_id'];
        }
        $role = Authassignment::getRole($data['user_id']);
        if($role['client']==TRUE){
            $query['user_id'] = $id;
            $query['is_archive'] = 0;            
            $limit = $data['page_no'] * 2;
          $com = DB::table('communication_user as a')
                        ->Join('communication as b','a.communication_id','=','b.id')
                        ->select('b.id','a.user_id','a.is_read','b.case_id','b.subject','b.message','b.created_by','b.entry_date')
                        ->where($query)
                        ->orderBy('b.id', 'desc')
                        // ->where('case_id','<>','null')
                        ->get();     
            
            $coms = $this->getMail($id,$com,'Inbox');
            $returns = Helper::paginatorHelper($data['page_no'],$coms);   
            
            if(count($returns)){
                return response()->json(['data'=>$returns,'count'=>count($coms),'status'=>TRUE], 200);
            }else{
                return response()->json(['status'=>FALSE], 400);
            }
            
        }else{
            return response()->json(['Message'=>'User not allowed','status'=>FALSE], 400);
        }
    }

    public function getSentboxMail(Request $request){
        $data = $request->all();
        $validator = Validator::make($data, [
            'user_id' => 'required|max:255',
            'page_no' => 'required|max:255',
        ]);

        if ($validator->fails()) {            
            return response()->json(['Message'=>'Please check Api Parameter','status'=>FALSE], 400);
        }else{
            $id = $data['user_id'];
        }
        $role = Authassignment::getRole($data['user_id']);
        if($role['client']==TRUE){
        $query['created_by'] = $id;
        $query['is_archive'] = 0;
        $query[] = ['case_id','<>','null']; 
          $com = DB::table('communication_user as a')
                        ->Join('communication as b','a.communication_id','=','b.id')
                        ->select('b.id','a.user_id','a.is_read','b.case_id','b.subject','b.message','b.created_by','b.entry_date')
                        ->where($query)
                        ->orderBy('b.id', 'desc')
                        // ->where('case_id','<>','null')
                        ->get();     
            $coms = $this->getMail($id,$com,'Sentbox');
            $returns = Helper::paginatorHelper($data['page_no'],$coms);   
            if(count($returns)){
                return response()->json(['data'=>$returns,'count'=>count($coms),'status'=>TRUE], 200);
            }else{
                return response()->json(['status'=>FALSE], 400);
            }
        }else{
            return response()->json(['Message'=>'User not allowed','status'=>FALSE], 400);
        }
    }

    public function getArchiveMail(Request $request){
        $data = $request->all();
        $validator = Validator::make($data, [
            'user_id' => 'required|max:255',
            'page_no' => 'required|max:255',
        ]);

        if ($validator->fails()) {              
            return response()->json(['Message'=>'Please check Api Parameter','status'=>FALSE], 400);
        }else{
            $id = $data['user_id'];
        }
        $role = Authassignment::getRole($data['user_id']);
        if($role['client']==TRUE){
        $query['is_archive'] = 1;
        $query['user_id'] = $id;
          $com = DB::table('communication_user as a')
                        ->Join('communication as b','a.communication_id','=','b.id')
                        ->select('b.id','a.user_id','a.is_read','b.case_id','b.subject','b.message','b.created_by','b.entry_date')
                        ->where($query)
                        ->orderBy('b.id', 'desc')
                        // ->where('case_id','<>','null')
                        ->get();     
            $coms = $this->getMail($id,$com,'Archived');
            $returns = Helper::paginatorHelper($data['page_no'],$coms);   
            if(count($returns)){
                return response()->json(['data'=>$returns,'count'=>count($coms),'status'=>TRUE], 200);
            }else{
                return response()->json(['status'=>FALSE], 400);
            }
        }else{
            return response()->json(['Message'=>'User not allowed','status'=>FALSE], 400);
        }
    }

    public function getStarMail(Request $request){
        $data = $request->all();
        $validator = Validator::make($data, [
            'user_id' => 'required|max:255',
            'page_no' => 'required|max:255',
        ]);

        if ($validator->fails()) {             
            return response()->json(['Message'=>'Please check Api Parameter','status'=>FALSE], 400);
        }else{
            $id = $data['user_id'];
        }
        $role = Authassignment::getRole($data['user_id']);
        if($role['client']==TRUE){
        $query['is_star'] = 1;
        $query['user_id'] = $id;
          $com = DB::table('communication_user as a')
                        ->Join('communication as b','a.communication_id','=','b.id')
                        ->select('b.id','a.user_id','a.is_read','b.case_id','b.subject','b.message','b.created_by','b.entry_date')
                        ->where($query)
                        ->orderBy('b.id', 'desc')
                        // ->where('case_id','<>','null')
                        ->get();     
            $coms = $this->getMail($id,$com,'Starred');
            $returns = Helper::paginatorHelper($data['page_no'],$coms);   
            if(count($returns)){
                return response()->json(['data'=>$returns,'count'=>count($coms),'status'=>TRUE], 200);
            }else{
                return response()->json(['status'=>FALSE], 400);
            }
        }else{
            return response()->json(['Message'=>'User not allowed','status'=>FALSE], 400);
        }
    }

    public function getImportantMail(Request $request){
        $data = $request->all();
        $validator = Validator::make($data, [
            'user_id' => 'required|max:255',
            'page_no' => 'required|max:255',
        ]);

        if ($validator->fails()) {              
            return response()->json(['Message'=>'Please check Api Parameter','status'=>FALSE], 400);
        }else{
            $id = $data['user_id'];
        }
        $role = Authassignment::getRole($data['user_id']);
        if($role['client']==TRUE){
        $query['is_flag'] = 1;
        $query['user_id'] = $id;
          $com = DB::table('communication_user as a')
                        ->Join('communication as b','a.communication_id','=','b.id')
                        ->select('b.id','a.user_id','a.is_read','b.case_id','b.subject','b.message','b.created_by','b.entry_date')
                        ->where($query)
                        ->orderBy('b.id', 'desc')
                        // ->where('case_id','<>','null')
                        ->get();     
            $coms = $this->getMail($id,$com,'Important');
            $returns = Helper::paginatorHelper($data['page_no'],$coms);   
            if(count($returns)){
                return response()->json(['data'=>$returns,'count'=>count($coms),'status'=>TRUE], 200);
            }else{
                return response()->json(['status'=>FALSE], 400);
            }
        }else{
            return response()->json(['Message'=>'User not allowed','status'=>FALSE], 400);
        }
    }
   
    

    public function replyMail(Request $request){
        $data = $request->all();
        $validator = Validator::make($data, [
            'com_id' => 'required|max:255',
            'sender_id' => 'required|max:255',
            'receiver_id' => 'required|max:255',
            'message' => 'required',  
            'message_type' => 'required',  
        ]);

        if ($validator->fails()) { 
            return response()->json(['Message'=>'Please check Api Parameter','error'=>$validator->errors(),'status'=>FALSE], 400);
        }else{
            $com_id = $request->get('com_id');
            $message = $request->get('message');
            $com = Communication::getCommunication($com_id); 
            if($com){
                $data['in_reply'] = $com->id;       
                $data['case_id'] = $com->case_id;
                $data['entry_date'] = date_create();
                $data['subject'] = 'Re: '.$com->subject;

                $auth1 = Authassignment::getAuthassignment($com->created_by);
                foreach ($auth1 as $key => $auth) {
                    if($auth->itemname == 'Client'){
                        $profile1 = Client::getClientProfile($com->created_by);
                    }else{
                        $profile1 = Profile::getProfile($com->created_by);                                
                    }
                }

                $date = date('D d, M Y h:s A',strtotime($com->entry_date));

                $message .= "<br>\n\t\t\t\t\t\t<br>\n\t\t\t\t\t\t<br>  \n\t\t\t\t\t\t"
                ."On ".$date." "
                .$profile1->last_name.', '.$profile1->first_name.' wrote:'
                ."\n\t\t\t\t\t\t<p class=\"ng-binding\" ng-bind-html=\"coms.message | to_trusted\" style=\"padding-left: 10px; border-left: 2px solid rgb(136, 136, 136); padding-top: 5px; padding-bottom: 5px;\">"
                .$com->message
                ."</p>\n\t\t\t\t\t";
                $data['message'] = $message;
                
                $request->merge($data);
                return $this->sendMail($request);
                // return response()->json(['Message'=>$request->all(),'status'=>FALSE], 400);
            }else{
                return response()->json(['status'=>FALSE],400);  
            }
        }
    }

    public function forwardMail(Request $request){
        $data = $request->all();
        $validator = Validator::make($data, [
            'com_id' => 'required|max:255',
            'sender_id' => 'required|max:255',
            'receiver_id' => 'required|max:255',
            'message' => 'required',
            'message_type' => 'required',
        ]);

        if ($validator->fails()) { 
            return response()->json(['Message'=>'Please check Api Parameter','error'=>$validator->errors(),'status'=>FALSE], 400);
        }else{
            $com_id = $request->get('com_id');
            $message = $request->get('message');
            $com = Communication::getCommunication($com_id); 
            $data['case_id'] = $com->case_id;
            $data['entry_date'] = date_create();
            $data['subject'] = 'FWD: '.$com->subject;

            $auth1 = Authassignment::getAuthassignment($com->created_by);
            foreach ($auth1 as $key => $auth) {
                if($auth->itemname == 'Client'){
                    $profile1 = Client::getClientProfile($com->created_by);
                }else{
                    $profile1 = Profile::getProfile($com->created_by);                                
                }
            }

            $date = date('D d, M Y h:s A',strtotime($com->entry_date));

            $message .= "<br>\n\t\t\t\t\t\t<br>\n\t\t\t\t\t\t<br>  \n\t\t\t\t\t\t"
            ."On ".$date." "
            .$profile1->last_name.', '.$profile1->first_name.' wrote:'
            ."\n\t\t\t\t\t\t<p class=\"ng-binding\" ng-bind-html=\"coms.message | to_trusted\" style=\"padding-left: 10px; border-left: 2px solid rgb(136, 136, 136); padding-top: 5px; padding-bottom: 5px;\">"
            .$com->message
            ."</p>\n\t\t\t\t\t";
            $data['message'] = $message;
            
            $request->merge($data);
            // return response()->json(['Message'=>$request->all(),'status'=>FALSE], 400);
            return $this->sendMail($request);
        }
    }
    
    public function ctsInboxMail(Request $request){
        $data = $request->all();
        $validator = Validator::make($data, [
            'case_id' => 'required|max:255',
            'user_id' => 'required|max:255',
            'client_id' => 'required|max:255',
            'page_no' => 'required|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['Message'=>'Please check Parameter','status'=>FALSE], 400);
        }else{
            if(!isset($data['entry_time'])) $data['entry_time'] = date('Y-m-d h:i:s',strtotime('now'));

            $month = date('m',strtotime(''.$data['entry_time']));
            $year = date('Y',strtotime(''.$data['entry_time']));
            $query['user_id'] = $data['user_id'];
            $query['case_id'] = $data['case_id'];
            // $query['created_by'] = $data['client_id'];
            $auth = Authassignment::getAuthassignment($data['user_id']);
            $att = 0;

            $role = Authassignment::getRole($data['user_id']);
            if($role['att']==TRUE || $role['admin']==TRUE){

            $com = DB::table('communication_user as a')
                        ->Join('communication as b','b.id','=','a.communication_id')
                        ->select('b.id','a.user_id','a.is_read','b.case_id','b.subject','b.message','b.created_by','b.entry_date')
                        ->where($query)
                        ->where('is_archive','<>',1)
                        ->whereMonth('entry_date', '=', $month)
                        ->whereYear('entry_date', '=', $year)
                        ->orderBy('b.id', 'desc')
                        // ->where('case_id','<>','null')
                        ->get();     
                $coms= $this->getMail($data['user_id'],$com,'Inbox');
                $returns = Helper::paginatorHelper($data['page_no'],$coms);   
                if(count($returns)){
                    return response()->json(['data'=>$returns,'count'=>count($coms),'status'=>TRUE], 200);
                }else{
                    return response()->json(['status'=>FALSE], 400);
                }
            }else{
            return response()->json(['Message'=>'User not allowed','status'=>FALSE], 400);
            }
        }
    }
   
    public function ctsSentboxMail(Request $request){
        $data = $request->all();
        $validator = Validator::make($data, [
            'case_id' => 'required|max:255',
            'user_id' => 'required|max:255',
            'client_id' => 'required|max:255',
            'page_no' => 'required|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['Message'=>'Please check Parameter','status'=>FALSE], 400);
        }else{
            //if(!isset($data['entry_time'])) $data['entry_time'] = date('Y-m-d h:i:s',strtotime('now'));

            //$entry_time = date('m',strtotime(''.$data['entry_time']));
            
            
            $query['created_by'] = $data['user_id'];
            $query['user_id'] = $data['client_id'];
            $query['case_id'] = $data['case_id'];
            $role = Authassignment::getRole($data['user_id']);
            if($role['att']==TRUE || $role['admin']==TRUE){
            $com = DB::table('communication_user as a')
                        ->Join('communication as b','a.communication_id','=','b.id')
                        ->select('b.id','a.user_id','a.is_read','b.case_id','b.subject','b.message','b.created_by','b.entry_date')
                        ->where($query)
                        //->whereMonth('entry_date', '=', $entry_time)
                        ->orderBy('b.id', 'desc')
                        // ->where('case_id','<>','null')
                        ->get();     
                $coms = $this->getMail($data['user_id'],$com,'Sentbox');
                $returns = Helper::paginatorHelper($data['page_no'],$coms);   
                if(count($returns)){
                    return response()->json(['data'=>$returns,'count'=>count($coms),'status'=>TRUE], 200);
                }else{
                    return response()->json(['status'=>FALSE], 400);
                }
            }else{
            return response()->json(['Message'=>'User not allowed','status'=>FALSE], 400);
            }
        }
    }

    public function ctsArchiveMail(Request $request){
        $data = $request->all();
        $validator = Validator::make($data, [
            'case_id' => 'required|max:255',
            'user_id' => 'required|max:255',
            'client_id' => 'required|max:255',
            'page_no' => 'required|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['Message'=>'Please check Parameter','status'=>FALSE], 400);
        }else{
            // if(!isset($data['entry_time'])) $data['entry_time'] = date('Y-m-d h:i:s',strtotime('now'));

            // $entry_time = date('m',strtotime(''.$data['entry_time']));
            $query['case_id'] = $data['case_id'];
            $query['user_id'] = $data['user_id'];
            $query['created_by'] = $data['client_id'];
            $query['is_archive'] = 1;
            $role = Authassignment::getRole($data['user_id']);
            if($role['att']==TRUE || $role['admin']==TRUE){
            $com = DB::table('communication_user as a')
                        ->Join('communication as b','a.communication_id','=','b.id')
                        ->select('b.id','a.user_id','a.is_read','b.case_id','b.subject','b.message','b.created_by','b.entry_date')
                        ->where($query)
                        // ->whereMonth('entry_date', '=', $entry_time)
                        ->orderBy('b.id', 'desc')
                        // ->where('case_id','<>','null')
                        ->get();     
                $coms= $this->getMail($data['user_id'],$com,'Archive');
                $returns = Helper::paginatorHelper($data['page_no'],$coms);   
                if(count($returns)){
                    return response()->json(['data'=>$returns,'count'=>count($coms),'status'=>TRUE], 200);
                }else{
                    return response()->json(['status'=>FALSE], 400);
                }
            }else{
            return response()->json(['Message'=>'User not allowed','status'=>FALSE], 400);
            }
        }
    }

    public function ctsStarMail(Request $request){
        $data = $request->all();
        $validator = Validator::make($data, [
            'case_id' => 'required|max:255',
            'user_id' => 'required|max:255',
            'client_id' => 'required|max:255',
            'page_no' => 'required|max:255',
        ]);

        if ($validator->fails()) {            
            return response()->json(['Message'=>'Please check Api Parameter','status'=>FALSE], 400);
        }
            $id = $data['user_id'];
       
        // if(!isset($data['entry_time'])) $data['entry_time'] = date('Y-m-d h:i:s',strtotime('now'));

        // $entry_time = date('m',strtotime(''.$data['entry_time']));
        $query['is_star'] = 1;
        $query['a.user_id'] = $id;
        $query['case_id'] = $data['case_id'];
        $query['created_by'] = $data['client_id'];
        $role = Authassignment::getRole($data['user_id']);
        if($role['att']==TRUE || $role['admin']==TRUE){
          $com = DB::table('communication_user as a')
                        ->Join('communication as b','a.communication_id','=','b.id')
                        ->select('b.id','a.user_id','a.is_read','b.case_id','b.subject','b.message','b.created_by','b.entry_date')
                        ->where($query)
                        // ->whereMonth('entry_date', '=', $entry_time)
                        ->orderBy('b.id', 'desc')
                        // ->where('case_id','<>','null')
                        ->get();     
            $coms= $this->getMail($id,$com,'Starred');
            $returns = Helper::paginatorHelper($data['page_no'],$coms);   
            if(count($returns)){
                return response()->json(['data'=>$returns,'count'=>count($coms),'status'=>TRUE], 200);
            }else{
                return response()->json(['status'=>FALSE], 400);
            }
        }else{
            return response()->json(['Message'=>'User not allowed','status'=>FALSE], 400);
        }
    }

    public function ctsImportantMail(Request $request){
        $data = $request->all();
        $validator = Validator::make($data, [
            'case_id' => 'required|max:255',
            'user_id' => 'required|max:255',
            'client_id' => 'required|max:255',
            'page_no' => 'required|max:255',
        ]);

        if ($validator->fails()) {          
            return response()->json(['Message'=>'Please check Api Parameter','status'=>FALSE], 400);
        }
            $id = $data['user_id'];
       
        // if(!isset($data['entry_time'])) $data['entry_time'] = date('Y-m-d h:i:s',strtotime('now'));

        // $entry_time = date('m',strtotime(''.$data['entry_time']));
        $query['is_flag'] = 1;
        $query['user_id'] = $id;
        $query['case_id'] = $data['case_id'];
        $query['created_by'] = $data['client_id'];
        $role = Authassignment::getRole($data['user_id']);
        if($role['att']==TRUE || $role['admin']==TRUE){
          $com = DB::table('communication_user as a')
                        ->Join('communication as b','a.communication_id','=','b.id')
                        ->select('b.id','a.user_id','a.is_read','b.case_id','b.subject','b.message','b.created_by','b.entry_date')
                        ->where($query)
                        // ->whereMonth('entry_date', '=', $entry_time)
                        ->orderBy('b.id', 'desc')
                        // ->where('case_id','<>','null')
                        ->get();     
            $coms= $this->getMail($id,$com,'Important');
            $returns = Helper::paginatorHelper($data['page_no'],$coms);   
            if(count($returns)){
                return response()->json(['data'=>$returns,'count'=>count($coms),'status'=>TRUE], 200);
            }else{
                return response()->json(['status'=>FALSE], 400);
            }
        }else{
            return response()->json(['Message'=>'User not allowed','status'=>FALSE], 400);
        }
    }
    
    public function otssendMail(Request $request){
        $data = $request->all();
        $validator = Validator::make($data, [
            'sender_id' => 'required|max:255',
            'receiver_id' => 'required|max:255',
            'message' => 'required',
        ]);

        if ($validator->fails()) {          
            return response()->json(['Message'=>'Please check Api Parameter','status'=>FALSE], 400);
        }else{

            $sender_id = $data['sender_id'];
            $authassignment = Authassignment::getAuthassignment($sender_id);
            $staff = 0;
            $admin = 0;
            if(!$authassignment->isEmpty()){ // check if sender exist as user
                foreach ($authassignment as $key => $auth) {
                   if ($auth->itemname == 'Staff') {
                        $staff = 1;
                    }
                    if ($auth->itemname == 'Admin') {
                        $admin = 1;
                    }
                }
                if($staff == 1 || $admin == 1){
                    // if($att == 1){
                        $message = $data['message']; 
                        if(isset($data['subject']))
                            $subject = $data['subject'];
                        else
                            $subject = '';

                        $receiver_id = explode(',', $data['receiver_id']);
                            $datas['created_by'] = $sender_id;
                            $datas['message'] = $message;
                            $datas['subject'] = $subject;
                            $datas['entry_date'] = date_create();
                            $datas['created'] = date_create();
                            $datas['modified'] = '0000-00-00';
                        if(isset($data['in_reply'])){
                            $datas['in_reply'] = '';
                        }
                        if(!isset($data['tz'])){
                            $datas['tz'] = 'US/Pacific';
                        }
                        // if(isset($data['message_type'])){
                            // $datas['message_type'] = $data['message_type'];
                        // }else{
                        //     $datas['message_type'] = 'Client';
                            $datas['message_type'] = 'Internal';
                        // }
                             //insert
                            $com = new Communication;
                            $com->fill($datas);
                            if($com->save()){
                                if(is_array($receiver_id)){
                                    foreach ($receiver_id as $key => $reciever) {                        
                                        $auths = Authassignment::getAuthassignment($reciever);
                                        if(!$auths->isEmpty()){ 
                                        // check if reciept exist as user
                                            $comuser = new Communication_user;
                                            $comuser->fill(['user_id'=>$reciever,'communication_id'=>$com->id]);
                                            if($comuser->save()){
                                                // $return[] = ['com_id'=>$com->id,'comuser_id'=>$comuser->id,'status'=>TRUE];
                                                $return[] = TRUE;
                                            }else{
                                                // $return[] = ['com_id'=>$com->id,'comuser_id'=>FALSE]; // failed to save to communicaion_user
                                                $return[] = FALSE;
                                            }
                                        }else{
                                            // $return[] = ['status'=>FALSE,'Message'=>'Recipient not register']; // Recipient not register
                                            $return[] = FALSE;
                                        }
                                    }
                                }else{
                                    $auths = Authassignment::getAuthassignment($receiver_id);
                                        if(!$auths->isEmpty()){ 
                                        // check if reciept exist as user
                                            $comuser = new Communication_user;
                                            $comuser->fill(['user_id'=>$receiver_id,'communication_id'=>$com->id]);
                                            if($comuser->save()){
                                                // $return[] = ['com_id'=>$com->id,'comuser_id'=>$comuser->id,'status'=>TRUE];
                                                $return = TRUE;
                                            }else{
                                                // $return[] = ['com_id'=>$com->id,'comuser_id'=>FALSE]; // failed to save to communicaion_user
                                                $return = FALSE;
                                            }
                                        }else{
                                            // $return[] = ['status'=>FALSE,'Message'=>'Recipient not register']; // Recipient not register
                                            $return = FALSE;
                                        }
                                }
                               
                            }else{
                                // $return[] = ['com_id'=>FALSE,'comuser_id'=>FALSE]; // failed to save to communication
                                $return = FALSE;
                            }
                            //end insert
                    // }      
                     if($return)
                        return response()->json(['status'=>TRUE],200);    
                    else
                        return response()->json(['status'=>FALSE],400);                  
                }else{
                    // if($admin==1){
                    //     return response()->json(['message'=>'Admin not Allowed','status'=>FALSE], 400);
                    // }else{
                        return response()->json(['message'=>'Client not Allowed','status'=>FALSE], 400);
                    // }
                }
            }else{
                return response()->json(['message'=>'NOT A USER','status'=>FALSE], 400);
            }
            
        }
    }

    public function sendMail(Request $request){
        $data = $request->all();
        $validator = Validator::make($data, [
            // 'case_id' => 'required',
            'sender_id' => 'required',
            'receiver_id' => 'required',
            'message' => 'required',
            'message_type' => 'required',
        ]);
        

        if ($validator->fails()) {
            return response()->json(['Message'=>'Please check Api Parameter','error'=>$validator->errors(),'status'=>FALSE], 400);
        }else{

            $message = $data['message'];
            $receiver_id = $data['receiver_id'];
            $sender_id = $data['sender_id'];
            if(isset($data['case_id']))
                $case_id = $data['case_id'];
            else
                $case_id = null;

            if(isset($data['subject']))
                $subject = $data['subject'];
            else
                $subject = '';

            $receiver_id = explode(',', $receiver_id);
            
            $authassignment = Authassignment::getAuthassignment($sender_id);
            if(!$authassignment->isEmpty()){ // check if sender exist as user
                // foreach ($authassignment as $key => $auth) {
                   
                            $datas['created_by'] = $sender_id;
                            $datas['case_id'] = $case_id;
                            $datas['message'] = $message;
                            $datas['subject'] = $subject;
                            $datas['entry_date'] = date_create();
                            $datas['created'] = date_create();
                            $datas['modified'] = '0000-00-00';
                                // if(isset($data['message_type'])){
                                //     if($data['message_type'] == 'Internal'){
                                //         $datas['message_type'] = 'Internal';
                                //     }else{
                                        // $datas['message_type'] = 'Client'; // for cts and client messaging
                                    // }
                                // }else{
                                    // return response()->json(['Not a Client'=>FALSE],200);
                                    // $datas['message_type'] = 'Client';
                                // }
                            if(isset($data['in_reply'])){
                                $datas['in_reply'] = $data['in_reply'];
                            }
                            if(!isset($data['tz'])){
                                $datas['tz'] = 'US/Pacific';
                            }
                            //insert
                            $com = new Communication;
                            $com->fill($datas);
                            if($com->save()){
                                if(is_array($receiver_id)){
                                    foreach ($receiver_id as $key => $reciever) {                        
                                        $auths = Authassignment::getAuthassignment($reciever);
                                        if(!$auths->isEmpty()){ 
                                        // check if reciept exist as user
                                            $comuser = new Communication_user;
                                            $comuser->fill(['user_id'=>$reciever,'communication_id'=>$com->id]);
                                            if($comuser->save()){
                                                // $return[] = ['com_id'=>$com->id,'comuser_id'=>$comuser->id,'status'=>TRUE];
                                                $return[] = TRUE;
                                            }else{
                                                // $return[] = ['com_id'=>$com->id,'comuser_id'=>FALSE]; // failed to save to communicaion_user
                                                $return[] = FALSE;
                                            }
                                        }else{
                                            // $return[] = ['status'=>FALSE,'Message'=>'Recipient not register']; // Recipient not register
                                            $return[] = FALSE;
                                        }
                                    }
                                }else{
                                    $auths = Authassignment::getAuthassignment($receiver_id);
                                        if(!$auths->isEmpty()){ 
                                        // check if reciept exist as user
                                            $comuser = new Communication_user;
                                            $comuser->fill(['user_id'=>$receiver_id,'communication_id'=>$com->id]);
                                            if($comuser->save()){
                                                // $return[] = ['com_id'=>$com->id,'comuser_id'=>$comuser->id,'status'=>TRUE];
                                                $return = TRUE;
                                            }else{
                                                // $return[] = ['com_id'=>$com->id,'comuser_id'=>FALSE]; // failed to save to communicaion_user
                                                $return = FALSE;
                                            }
                                        }else{
                                            // $return[] = ['status'=>FALSE,'Message'=>'Recipient not register']; // Recipient not register
                                            $return = FALSE;
                                        }
                                }
                               
                            }else{
                                // $return[] = ['com_id'=>FALSE,'comuser_id'=>FALSE]; // failed to save to communication
                                $return = FALSE;
                            }
                            //end insert
                // }
            }else{
                // $return[] = ['status'=>FALSE,'Message'=>'Sender not register']; // Sender not register
                $return = FALSE;                
            }
                if($return)
                    return response()->json(['status'=>TRUE],200);    
                else
                    return response()->json(['status'=>FALSE],400);    
        }
    }

    public function getOTSstaffList(Request $request){
        $data = $request->all();

        $data = $request->all();
        $validator = Validator::make($data, [
            'user_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['Message'=>'Please check Api Parameter','status'=>FALSE], 400);
        }else{
             $authassignment = Authassignment::getAuthassignment($data['user_id']);
            if(!$authassignment->isEmpty()){ 
            $authassignment = Authassignment::getRole($data['user_id']);
            if($authassignment['admin'] || $authassignment['staff']){
                $staffs = Authassignment::getAllOTSstaff($data['user_id']);
                foreach ($staffs as $key => $value) {
                    $staff[] = Profile::getProfile($value->userid);
                }
                return response()->json(['data'=>$staff,'status'=>TRUE], 200);
            }else{
                return response()->json(['Message'=>'User is not allowed','status'=>FALSE], 400);
            }
            }else{
                return response()->json(['Message'=>'User not exist','status'=>FALSE], 400);   
            }
        }
    }

    public function otsInboxMail(Request $request){
        $data = $request->all();
        $validator = Validator::make($data, [
            'user_id' => 'required|max:255',
            'message_type' => 'required|max:255', 
            'page_no' => 'required|max:255',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['Message'=>'Please check Parameter','status'=>FALSE], 400);
        }else{
            if(!isset($data['entry_time'])) $data['entry_time'] = date('Y-m-d h:i:s',strtotime('now'));

            $entry_time = date('m',strtotime(''.$data['entry_time']));

            $query['user_id'] = $data['user_id'];
            $query['message_type'] = $data['message_type'];
            // $query['created_by'] = $data['client_id'];
            $auth = Authassignment::getRole($data['user_id']);
            
            if($auth['admin'] == TRUE || $auth['staff'] == TRUE){
            $com = DB::table('communication_user as a')
                        ->Join('communication as b','b.id','=','a.communication_id')
                        ->select('b.id','a.user_id','a.is_read','b.case_id','b.subject','b.message','b.created_by','b.entry_date')
                        ->where($query)
                        ->where('is_archive','<>',1)
                        ->whereMonth('entry_date', '>', $entry_time)
                        ->orderBy('b.id', 'desc')
                        // ->where('case_id','<>','null')
                        ->get();   
                $coms= $this->getMail($data['user_id'],$com,'Inbox');
                $returns = Helper::paginatorHelper($data['page_no'],$coms);   
                if(count($returns)){
                    return response()->json(['data'=>$returns,'count'=>count($coms),'status'=>TRUE], 200);
                }else{
                    return response()->json(['status'=>FALSE], 400);
                }
            }else{
                return response()->json(['Message'=>'User not allowed.','status'=>FALSE], 400);
            }
        }
    }

    public function otsSentboxMail(Request $request){
        $data = $request->all();
        $validator = Validator::make($data, [
            'user_id' => 'required|max:255',
            'message_type' => 'required|max:255',
            'page_no' => 'required|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['Message'=>'Please check Parameter','status'=>FALSE], 400);
        }else{
            //if(!isset($data['entry_time'])) $data['entry_time'] = date('Y-m-d h:i:s',strtotime('now'));

            //$entry_time = date('m',strtotime(''.$data['entry_time']));
            $query['created_by'] = $data['user_id'];
            $query['message_type'] = $data['message_type'];
            $auth = Authassignment::getRole($data['user_id']);
            
            if($auth['admin'] == TRUE || $auth['staff'] == TRUE){
                    $com = DB::table('communication_user as a')
                        ->Join('communication as b','a.communication_id','=','b.id')
                        ->select('b.id','a.user_id','a.is_read','b.case_id','b.subject','b.message','b.created_by','b.entry_date')
                        ->where($query)
                        //->whereMonth('entry_date', '=', $entry_time)
                        ->orderBy('b.id', 'desc')
                        // ->where('case_id','<>','null')
                        ->get();     
                $coms= $this->getMail($data['user_id'],$com,'Sentbox');
                $returns = Helper::paginatorHelper($data['page_no'],$coms);   
                if(count($returns)){
                    return response()->json(['data'=>$returns,'count'=>count($coms),'status'=>TRUE], 200);
                }else{
                    return response()->json(['status'=>FALSE], 400);
                }
            }else{
                return response()->json(['Message'=>'User not allowed.','status'=>FALSE], 400);
            }
        }
    }

    public function otsArchiveMail(Request $request){
        $data = $request->all();
        $validator = Validator::make($data, [
            'user_id' => 'required|max:255',
            'message_type' => 'required|max:255',
            'page_no' => 'required|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['Message'=>'Please check Parameter','status'=>FALSE], 400);
        }else{
            // if(!isset($data['entry_time'])) $data['entry_time'] = date('Y-m-d h:i:s',strtotime('now'));

            // $entry_time = date('m',strtotime(''.$data['entry_time']));
            $query['user_id'] = $data['user_id'];
            $query['message_type'] = $data['message_type'];
            $query['is_archive'] = 1;
            $auth = Authassignment::getRole($data['user_id']);
            
            if($auth['admin'] == TRUE || $auth['staff'] == TRUE){
                $com = DB::table('communication_user as a')
                            ->Join('communication as b','a.communication_id','=','b.id')
                            ->select('b.id','a.user_id','a.is_read','b.case_id','b.subject','b.message','b.created_by','b.entry_date')
                            ->where($query)
                            // ->whereMonth('entry_date', '=', $entry_time)
                            ->orderBy('b.id', 'desc')
                            // ->where('case_id','<>','null')
                            ->get();     
                $coms= $this->getMail($data['user_id'],$com,'Archive');
                $returns = Helper::paginatorHelper($data['page_no'],$coms);   
            if(count($returns)){
                return response()->json(['data'=>$returns,'count'=>count($coms),'status'=>TRUE], 200);
            }else{
                return response()->json(['status'=>FALSE], 400);
            }
            }else{
                return response()->json(['Message'=>'User not allowed.','status'=>FALSE], 400);
            }
        }
    }

    public function otsStarMail(Request $request){
        $data = $request->all();
        $validator = Validator::make($data, [
            'user_id' => 'required|max:255',
            'message_type' => 'required|max:255',
            'page_no' => 'required|max:255',
        ]);

        if ($validator->fails()) {            
            return response()->json(['Message'=>'Please check Api Parameter','status'=>FALSE], 400);
        }
            $id = $data['user_id'];
       
        // if(!isset($data['entry_time'])) $data['entry_time'] = date('Y-m-d h:i:s',strtotime('now'));

        // $entry_time = date('m',strtotime(''.$data['entry_time']));
        $query['is_star'] = 1;
        $query['a.user_id'] = $id;
        $query['message_type'] = $data['message_type'];
        $auth = Authassignment::getRole($data['user_id']);
            
        if($auth['admin'] == TRUE || $auth['staff'] == TRUE){
          $com = DB::table('communication_user as a')
                        ->Join('communication as b','a.communication_id','=','b.id')
                        ->select('b.id','a.user_id','a.is_read','b.case_id','b.subject','b.message','b.created_by','b.entry_date')
                        ->where($query)
                        // ->whereMonth('entry_date', '=', $entry_time)
                        ->orderBy('b.id', 'desc')
                        // ->where('case_id','<>','null')
                        ->get();     
            $coms= $this->getMail($id,$com,'Starred');
            $returns = Helper::paginatorHelper($data['page_no'],$coms);   
            if(count($returns)){
                return response()->json(['data'=>$returns,'count'=>count($coms),'status'=>TRUE], 200);
            }else{
                return response()->json(['status'=>FALSE], 400);
            }
        }else{
                return response()->json(['Message'=>'User not allowed.','status'=>FALSE], 400);
        }
    }

    public function otsImportantMail(Request $request){
        $data = $request->all();
        $validator = Validator::make($data, [
            'user_id' => 'required|max:255',
            'message_type' => 'required|max:255',
            'page_no' => 'required|max:255',
        ]);

        if ($validator->fails()) {            
            return response()->json(['Message'=>'Please check Api Parameter','status'=>FALSE], 400);
        }
            $id = $data['user_id'];
       
        // if(!isset($data['entry_time'])) $data['entry_time'] = date('Y-m-d h:i:s',strtotime('now'));

        // $entry_time = date('m',strtotime(''.$data['entry_time']));
        $query['is_flag'] = 1;
        $query['a.user_id'] = $id;
        $query['message_type'] = $data['message_type'];
        $auth = Authassignment::getRole($data['user_id']);
            
        if($auth['admin'] == TRUE || $auth['staff'] == TRUE){
          $com = DB::table('communication_user as a')
                        ->Join('communication as b','a.communication_id','=','b.id')
                        ->select('b.id','a.user_id','a.is_read','b.case_id','b.subject','b.message','b.created_by','b.entry_date')
                        ->where($query)
                        // ->whereMonth('entry_date', '=', $entry_time)
                        ->orderBy('b.id', 'desc')
                        // ->where('case_id','<>','null')
                        ->get();     
            $coms= $this->getMail($id,$com,'Important');
            $returns = Helper::paginatorHelper($data['page_no'],$coms);   
            if(count($returns)){
                return response()->json(['data'=>$returns,'count'=>count($coms),'status'=>TRUE], 200);
            }else{
                return response()->json(['status'=>FALSE], 400);
            }
        }else{
                return response()->json(['Message'=>'User not allowed.','status'=>FALSE], 400);
        }
    }

    // public function ctssendMail(Request $request){
    //     $data = $request->all();
    //     $validator = Validator::make($data, [
            
    //         'sender_id' => 'required',
    //         'receiver_id' => 'required',
    //         'message' => 'required',
    //     ]);
        

    //     if ($validator->fails()) {
    //         return response()->json(['Message'=>'Please check Api Parameter','status'=>FALSE], 400);
    //     }else{

    //         $message = $data['message'];
    //         $receiver_id = $data['receiver_id'];
    //         $sender_id = $data['sender_id'];
    //         if(isset($data['subject']))
    //             $subject = $data['subject'];
    //         else
    //             $subject = '';

    //         $receiver_id = explode(',', $receiver_id);
            
    //         $authassignment = Authassignment::getAuthassignment($sender_id);
    //         if(!$authassignment->isEmpty()){ // check if sender exist as user
    //             // foreach ($authassignment as $key => $auth) {
                   
    //                         $datas['created_by'] = $sender_id;
    //                         $datas['message'] = $message;
    //                         $datas['subject'] = $subject;
    //                         $datas['entry_date'] = date_create();
    //                         $datas['created'] = date_create();
    //                         $datas['modified'] = '0000-00-00';
    //                             // if(isset($data['message_type'])){
    //                             //     if($data['message_type'] == 'Internal'){
    //                             //         $datas['message_type'] = 'Internal';
    //                             //     }else{
    //                                     // $datas['message_type'] = 'Client'; // for cts and client messaging
    //                                 // }
    //                             // }else{
    //                                 // return response()->json(['Not a Client'=>FALSE],200);
    //                                 $datas['message_type'] = 'Client';
    //                             // }
    //                         if(isset($data['in_reply'])){
    //                             $datas['in_reply'] = $data['in_reply'];
    //                         }
    //                         if(!isset($data['tz'])){
    //                             $datas['tz'] = 'US/Pacific';
    //                         }
    //                         //insert
    //                         $com = new Communication;
    //                         $com->fill($datas);
    //                         if($com->save()){
    //                             if(is_array($receiver_id)){
    //                                 foreach ($receiver_id as $key => $reciever) {                        
    //                                     $auths = Authassignment::getAuthassignment($reciever);
    //                                     if(!$auths->isEmpty()){ 
    //                                     // check if reciept exist as user
    //                                         $comuser = new Communication_user;
    //                                         $comuser->fill(['user_id'=>$reciever,'communication_id'=>$com->id]);
    //                                         if($comuser->save()){
    //                                             // $return[] = ['com_id'=>$com->id,'comuser_id'=>$comuser->id,'status'=>TRUE];
    //                                             $return[] = TRUE;
    //                                         }else{
    //                                             // $return[] = ['com_id'=>$com->id,'comuser_id'=>FALSE]; // failed to save to communicaion_user
    //                                             $return[] = FALSE;
    //                                         }
    //                                     }else{
    //                                         // $return[] = ['status'=>FALSE,'Message'=>'Recipient not register']; // Recipient not register
    //                                         $return[] = FALSE;
    //                                     }
    //                                 }
    //                             }else{
    //                                 $auths = Authassignment::getAuthassignment($receiver_id);
    //                                     if(!$auths->isEmpty()){ 
    //                                     // check if reciept exist as user
    //                                         $comuser = new Communication_user;
    //                                         $comuser->fill(['user_id'=>$receiver_id,'communication_id'=>$com->id]);
    //                                         if($comuser->save()){
    //                                             // $return[] = ['com_id'=>$com->id,'comuser_id'=>$comuser->id,'status'=>TRUE];
    //                                             $return = TRUE;
    //                                         }else{
    //                                             // $return[] = ['com_id'=>$com->id,'comuser_id'=>FALSE]; // failed to save to communicaion_user
    //                                             $return = FALSE;
    //                                         }
    //                                     }else{
    //                                         // $return[] = ['status'=>FALSE,'Message'=>'Recipient not register']; // Recipient not register
    //                                         $return = FALSE;
    //                                     }
    //                             }
                               
    //                         }else{
    //                             // $return[] = ['com_id'=>FALSE,'comuser_id'=>FALSE]; // failed to save to communication
    //                             $return = FALSE;
    //                         }
    //                         //end insert
    //             // }
    //         }else{
    //             // $return[] = ['status'=>FALSE,'Message'=>'Sender not register']; // Sender not register
    //             $return = FALSE;                
    //         }
    //             if($return)
    //                 return response()->json(['status'=>TRUE],200);    
    //             else
    //                 return response()->json(['status'=>FALSE],400);    
    //     }
    // }
}
