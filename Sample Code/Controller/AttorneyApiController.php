<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\AttorneyInfo;
use App\Client;
use App\Cases;
use App\CaseUser;

use App\Profile;
use App\User;
use App\Address;
use App\Country;
use App\Firm;
use App\Authassignment;
use Validator;

class AttorneyApiController extends Controller
{
	
	public function getAttorney(Request $request)
	{
		$data = $request->all();
		if(isset($data['user_id'])){
		
		$user_id = $data['user_id'];
		
		$authassignment = Authassignment::getAuthassignment($user_id);
		$profiles = null;
		foreach ($authassignment as $key => $authass) {
			if($authass->itemname == 'Client'){
				$clients = Client::getClient($authass->userid);
				foreach ($clients as $key => $client) {
					$cases = Cases::getCases($client->id);
					foreach ($cases as $key => $case) {
						$case_users = Caseuser::getCaseuser($case->id);
						foreach ($case_users as $key => $case_user) {
							if($case_user->utype != 'staff'){
								$profile = Profile::getProfile($case_user->user_id);
						    	$user = User::getUserbyID($case_user->user_id);
						    	$address = Address::getAddress($case_user->user_id);
						    	
						    	$profile_user = array_merge($profile->toArray(),$user->toArray());
						    	$firm = Firm::getFirm($user->firm_id);
						    	// $profile->firm_name = $firm->name;
						    	if($address==FALSE){
						    		$country = FALSE;
						    	}else{
						    		$country = Country::getCountry($address->country);	
						    		$address->country = $country;
						    		$profile_user = array_merge($profile_user,$address->toArray());
						    	}
						    	
						    	unset($profile_user['fax']);
						    	unset($profile_user['firm_id']);
						    	unset($profile_user['home_page']);
						    	$profile_user['website'] = $firm->website;
						    	// $datas['profile'] = $profile_user;
						    	
						    	// $data['profile'] = $profile;
						    	
						    	// $data['user'] = $user;
						    	// $data['address'] = $address;
						    	// $data['country'] = $country;
						    	// $data['firm'] = $firm->name;

						    	// $return = array_merge($profile->toArray(),$user->toArray(),$address->toArray());

								$profiles[$profile_user['id']]=$profile_user;	
								// $profiles[] = $case_users;
							}				
						}
						// $case_user_data['case'.$case->id] = $case_users;
					}
					// $cases_data['client-'.$client->id] = $cases;
				}
				if($profiles){
					// $return['attorney'] = $profiles;
					foreach ($profiles as $key => $value) {
						$ret[] = $value;
					}
					return response()->json(['status'=>TRUE,'data'=>$ret],200);
				}else{
					return response()->json(['Message'=>'No Attorney Exist.','status'=> FALSE],400);
				}
			}else{
							// $profile = Profile::getProfile($authass->userid);
					  //   	$user = User::find($authass->userid);
					  //   	$address = Address::getAddress($authass->userid);
					  //   	if($address==FALSE){
					  //   		$country = FALSE;
					  //   	}else{
					  //   		$country = Country::getCountry($address->country);	
					  //   	}
					  //   	$firm = Firm::getFirm($user->firm_id);

					  //   	$data['profile'] = $profile;
					  //   	$data['user'] = $user;
					  //   	$data['address'] = $address;
					  //   	$data['country'] = $country;
					  //   	$data['firm'] = $firm;
							// $profiles['authuser'.$authass->userid]=$data;
				$profile = Profile::getProfile($user_id);
						    	$user = User::getUserbyID($user_id);
						    	$address = Address::getAddress($user_id);
						    	
						    	$profile_user = array_merge($profile->toArray(),$user->toArray());
						    	$firm = Firm::getFirm($user->firm_id);
						    	// $profile->firm_name = $firm->name;
						    	if($address==FALSE){
						    		$country = FALSE;
						    	}else{
						    		$country = Country::getCountry($address->country);	
						    		$address->country = $country;
						    		$profile_user = array_merge($profile_user,$address->toArray());
						    	}
						    	
						    	unset($profile_user['fax']);
						    	unset($profile_user['firm_id']);
						    	unset($profile_user['home_page']);
						    	$profile_user['website'] = $firm->website;
						    	// $datas['profile'] = $profile_user;
						    	
						    	// $data['profile'] = $profile;
						    	
						    	// $data['user'] = $user;
						    	// $data['address'] = $address;
						    	// $data['country'] = $country;
						    	// $data['firm'] = $firm->name;

						    	// $return = array_merge($profile->toArray(),$user->toArray(),$address->toArray());

				return response()->json(['data'=>$profile_user,'status'=> FALSE],200);
			}
		}
			return response()->json(['Message'=>'No User exist','status'=> FALSE],400);
		}else{
			return response()->json(['Message'=>'Check Api Parameter','status'=> FALSE],400);
		}
		
		// $return['clients'] = $clients_data;
		// $return['cases'] = $cases_data;
		// $return['case_users'] = $case_user_data;		
	}


	public function getlawyerbyCaseID(Request $request){
		$data = $request->all();
		$validator = Validator::make($data, [
            'case_id' => 'required|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json(['Message'=>'Please check Parameter','status'=>FALSE], 400);
        }else{
						$case_users = Caseuser::getCaseuser($data['case_id']);
						foreach ($case_users as $key => $case_user) {
								$profile = Profile::getProfile($case_user->user_id);
								$datas['user_id'] = $case_user->user_id;
								$datas['first_name'] = $profile->first_name;
								$datas['middle_name']  = $profile->middle_name;
								$datas['last_name'] = $profile->last_name;
								$profiles[]=$datas;	
						}
					return response()->json(['data'=>$profiles,'status'=>TRUE], 200);
        }
	}

	public function getAllOTSstaff(Request $request){
		$data = $request->all();

		var_dump($request);
	}
}
