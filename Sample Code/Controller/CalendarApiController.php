<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Client;
use App\Cases;
use App\Calendar;
use App\Authassignment;
use App\Profile;
use App\CalendarUser;
use DB;
use Validator;
class CalendarApiController extends Controller
{
    public function getCalendar(Request $request){
    	$data = $request->all();
    	$validator = Validator::make($data, [
            'user_id' => 'required|max:255',
            'from_month' => 'required|max:255',
            'to_month' => 'required|max:255',
            'year' => 'required|max:255',
        ]);

        if ($validator->fails())   
        	return response()->json(['Message'=>'Please check Api Parameter','status'=>FALSE], 400);

      	$auth = Authassignment::getAuthassignment($data['user_id']);
            foreach ($auth as $key => $auth) {
                if($auth->itemname == 'Client'){
                	
                	$clients = Client::getClient($data['user_id']);

					foreach ($clients as $key => $client) {
						$data_client['first_name'] = $client->first_name;
						$data_client['middle_name'] = $client->middle_name;
						$data_client['last_name'] = $client->last_name;
						$cases = Cases::getCases($client->id);
						foreach ($cases as $key => $case) {
							$data_client['case'] = $case->case_no;
							$calendars = Calendar::getClientCalendar($case->id,$data['from_month'],$data['to_month'],$data['year']);
							foreach ($calendars as $key => $calendar) {
								$data_client['start_time'] = $calendar->start_time;
								$data_client['end_time'] = $calendar->end_time;
								$data_client['alert_date'] = $calendar->alert_date;
								$data_client['subject'] = $calendar->subject;
								$return[] = $data_client;
							}
						}
						// $cases_data[$client->id] = $cases;
					}


					// foreach ($cases_data as $key => $cases) {
					// 	foreach ($cases as $key => $case) {
					// 		$calendar_data[$case->id] = Calendar::getCalendar($case->id,$data['month'],$data['year']);
					// 	}
					// }
					
					
					// $return['client'] = $client;
					// $return['cases'] = $cases_data;
					// $return['calendar'] = $calendar_data;
					
					if(isset($return)){
						return response()->json(['status'=>TRUE,'data'=>$return],200);
					}else{
						return response()->json(['Message'=>'No Calendar','staus'=>FALSE], 400);
					}   
                }else{
                    return response()->json(['Message'=>'Not a Client','staus'=>FALSE], 400);
                }
            }
    }

    public function getCTSCalendar(Request $request){
    	$data = $request->all();
    	$validator = Validator::make($data, [
            'client_id' => 'required|max:255',
            'user_id' => 'required|max:255',
            'from_month' => 'required|max:255',
            'to_month' => 'required|max:255',
            'year' => 'required|max:255',
            'case_id' => 'required|max:255',
        ]);

        if ($validator->fails())    
        	return response()->json(['Message'=>'Please check Api Parameter','status'=>FALSE], 400);

      	$auth = Authassignment::getAuthassignment($data['user_id']);
      		$att = 0;
            foreach ($auth as $key => $auth) {
                if($auth->itemname != 'Client'){
                	$att = 1;
                }
            }
                if($att == 1){	
                	$clients = Client::getClient($data['client_id']);

					foreach ($clients as $key => $client) {
						$data_client['first_name'] = $client->first_name;
						$data_client['middle_name'] = $client->middle_name;
						$data_client['last_name'] = $client->last_name;
						$cases = Cases::getCasesByCaseID($data['case_id']);
						foreach ($cases as $key => $case) {
							$data_client['case'] = $case->case_no;
							$calendars = Calendar::getCTSClientCalendar($case->id,$data['from_month'],$data['to_month'],$data['year']);
							foreach ($calendars as $key => $calendar) {
								$data_client['start_time'] = $calendar->start_time;
								$data_client['end_time'] = $calendar->end_time;
								$data_client['alert_date'] = $calendar->alert_date;
								$data_client['subject'] = $calendar->subject;
								$return[] = $data_client;
							}
						}
						// $cases_data[$client->id] = $cases;
					}


					// foreach ($cases_data as $key => $cases) {
					// 	foreach ($cases as $key => $case) {
					// 		$calendar_data[$case->id] = Calendar::getCalendar($case->id,$data['month'],$data['year']);
					// 	}
					// }
					
					
					// $return['client'] = $client;
					// $return['cases'] = $cases_data;
					// $return['calendar'] = $calendar_data;
					
					if(isset($return)){
						return response()->json(['status'=>TRUE,'data'=>$return],200);
					}else{
						return response()->json(['Message'=>'No Calendar','staus'=>FALSE], 400);
					}   
                }else{
                    return response()->json(['Message'=>'Client not Allowed','staus'=>FALSE], 400);
                }
    }

    public function addCTSCalendar(Request $request){
    	$data = $request->all();
    	$validator = Validator::make($data, [
            'alert_date' => 'required|max:255',
            'start_time' => 'required|max:255',
            'end_time' => 'required|max:255',
            'subject' => 'required|max:255',
            'case_id' => 'required|max:255',
            'notes' => 'required|max:255',
            'client_id' => 'required|max:255',
            'user_id' => 'required|max:255',
            'to_client' => 'required|max:255',
        ]);

        if ($validator->fails()){    
        	return response()->json(['Message'=>'Please check Api Parameter','status'=>FALSE], 400);
        }else{
        	
        	$calendar = new Calendar;
        	
        	$data['created_by'] = $data['user_id'];
        	unset($data['user_id']);

        	$alert_date = date('Y-m-d 00:00:00',strtotime($data['alert_date']));
        	$start_time = date('Y-m-d H:i:s ',strtotime('today '.$data['start_time']));
        	$end_time = date('Y-m-d H:i:s ',strtotime('today '.$data['end_time']));
        	$data['start_time'] = $start_time;
        	$data['alert_date'] = $alert_date;
        	$data['end_time'] = $end_time;
        	$data['alert_type'] = 'Client';
        	
        	if($data['to_client'] == 0){
        		$data['description'] = $data['subject'];
        	}else{
        		$data['description'] = $data['notes'];
        	}
        	$calendar->fill($data);
        	if($calendar->save()){
        		if($data['to_client'] == 1){
        			$data2['user_id'] = $data['client_id'];
        			$data2['calendar_id'] = $calendar->id;
        			$calendar_user = new CalendarUser;
        			$calendar_user->fill($data2);
        			if($calendar_user->save()){
        				return response()->json(['status'=>TRUE], 200);		
        			}else{
        				$calendar->delete();
        				return response()->json(['message'=>'Error #2: Error on saving. Please contact support','status'=>FALSE], 400);
        			}
        		}else{
        			return response()->json(['status'=>TRUE], 200);		
        		}        			
        		
        	}else{
        		return response()->json(['message'=>'Error on saving. Please contact support','status'=>FALSE], 400);
        	}
        	
        }
    }

    public function otsMasterCalendar(Request $request){
        $data = $request->all();
        $validator = Validator::make($data, [
            'user_id' => 'required|max:255',
            'from' => 'required|max:255',
            'to' => 'required|max:255',
        ]);
        if ($validator->fails())   
            return response()->json(['Message'=>'Please check Api Parameter','status'=>FALSE], 400);

        $role = Authassignment::getRole($data['user_id']);

        if($role['admin'] || $role['staff'] || $role['att']){
            $from = date('Y-m-d',strtotime($data['from']));
            $to = date('Y-m-d',strtotime($data['to']));
            
            $calendar = Calendar::getMasterCalendar($from,$to);
            
            if(!$calendar->isEmpty()){               
                $return = $this->getOTSCalendar($calendar);
                return response()->json(['data'=>$return,'status'=>TRUE], 200);
            }else{
                return response()->json(['Message'=>'No calendar event','status'=>FALSE], 400);
            }

            
        }else{
            return response()->json(['Message'=>'User not allowed','status'=>FALSE], 400);
        }
    }

    public function otsMyCalendar(Request $request){
        $data = $request->all();
        $data = $request->all();
        $validator = Validator::make($data, [
            'user_id' => 'required|max:255',
            'from' => 'required|max:255',
            'to' => 'required|max:255',
        ]);
        if ($validator->fails())   
            return response()->json(['Message'=>'Please check Api Parameter','status'=>FALSE], 400);

        $role = Authassignment::getRole($data['user_id']);

        if($role['admin'] || $role['staff'] || $role['att']){
            $from = date('Y-m-d h:i:s',strtotime($data['from']));
            $to = date('Y-m-d h:i:s',strtotime($data['to']));
            
            $query['created_by'] = $data['user_id'];
            $calendar = Calendar::getMyCalendarbyQuery($from,$to,$query);
            if($calendar){
                $return = $this->getOTSCalendar($calendar);    
                return response()->json(['data'=>$return,'status'=>TRUE], 400);
            }else{
                return response()->json(['Message'=>'No available event','status'=>FALSE], 400);    
            }
            
        }else{
            return response()->json(['Message'=>'User not allowed','status'=>FALSE], 400);
        }
    }

    public function getOTSCalendar($calendar){

        foreach ($calendar as $key => $cal) {
                $cal_info = null;
                $created_profile = Profile::getProfile($cal->created_by);
                $created_by_info = [
                    'first_name'=>$created_profile->first_name,
                    'middle_name'=>$created_profile->middle_name,
                    'last_name'=>$created_profile->last_name
                ];
                // setting up for return
                    $cal_info['alert_date'] = $cal->alert_date;
                    $cal_info['start_time'] = $cal->start_time;
                    $cal_info['end_time'] = $cal->end_time;
                    $cal_info['subject'] = $cal->subject;
                    $cal_info['description'] = $cal->description;
                    $cal_info['alert_type'] = $cal->alert_type;
                    $cal_info['created_by'] = $created_by_info;
                    $cal_info['to_client'] = $cal->to_client;

                if($cal->case_id != null){
                    $case_info = Cases::getCasesByCaseID($cal->case_id);
                    foreach ($case_info as $key => $value) {
                        $cal->case_name = $value->case_no;
                    }
                    $client_profile = DB::table('cases as a')
                        ->Join('client as b','a.user_id','=','b.id')
                        ->select('b.first_name','b.middle_name','b.last_name')
                        ->where(['a.id'=>$cal->case_id])
                        ->orderBy('b.id', 'desc')
                        // ->where('case_id','<>','null')
                        ->first();                   
                    
                    // addting to return because it is a CTS 
                    $cal_info['client_name'] = $client_profile;
                    $cal_info['case_name'] = $cal->case_name;
                    
                }
                //merging returns
                $ret[] = $cal_info;
            }

        return $ret;
    }

    public function addOTSCalendar(Request $request){
        $data = $request->all();
        $validator = Validator::make($data, [
            'alert_date' => 'required|max:255',
            'start_time' => 'required|max:255',
            'end_time' => 'required|max:255',
            'subject' => 'required|max:255',
            'user_id' => 'required|max:255',
        ]);

        if ($validator->fails()){    
            return response()->json(['Message'=>'Please check Api Parameter','status'=>FALSE], 400);
        }else{
            
            $calendar = new Calendar;
            
            $data['created_by'] = $data['user_id'];
            unset($data['user_id']);

            $alert_date = date('Y-m-d 00:00:00',strtotime($data['alert_date']));
            $start_time = date('Y-m-d H:i:s ',strtotime('today '.$data['start_time']));
            $end_time = date('Y-m-d H:i:s ',strtotime('today '.$data['end_time']));
            $data['start_time'] = $start_time;
            $data['alert_date'] = $alert_date;
            $data['end_time'] = $end_time;
            $data['alert_type'] = 'Internal';
            $data['description'] = $data['subject'];
            $data['subject'] = $data['subject'];

            $calendar->fill($data);
            if($calendar->save()){
            //     // if($data['to_client'] == 1){
            //     //     $data2['user_id'] = $data['client_id'];
            //     //     $data2['calendar_id'] = $calendar->id;
            //     //     $calendar_user = new CalendarUser;
            //     //     $calendar_user->fill($data2);
            //     //     if($calendar_user->save()){
            //     //         return response()->json(['status'=>TRUE], 200);     
            //     //     }else{
            //     //         $calendar->delete();
            //     //         return response()->json(['message'=>'Error #2: Error on saving. Please contact support','status'=>FALSE], 400);
            //     //     }
            //     // }else{
                    return response()->json(['status'=>TRUE], 200);     
            //     // }        
            }else{
                return response()->json(['message'=>'Error on saving. Please contact support','status'=>FALSE], 400);
            }
        }
    }
}
