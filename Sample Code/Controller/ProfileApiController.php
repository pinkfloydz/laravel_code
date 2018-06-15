<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Client;
use App\Address;
use App\Profile;
use App\Country;
use App\Authassignment;
use App\User;
use App\Personal;
use App\Spouse;
use App\Child;
use App\Education;
use App\Employment;
use App\State;

class ProfileApiController extends Controller
{
    public function getProfile(Request $request)
    {
    	$data = $request->all();
        
    	if(isset($data['user_id'])){
    		$id = $data['user_id'];
            
            $auth = Authassignment::getAuthassignment($id);
            foreach ($auth as $key => $auth) {
                if($auth->itemname == 'Client'){
                    $profile = Client::getClientProfile($id);        
                }else{
                    $profile = Profile::getProfile($id);
                }
            }
            
            // $profile = Profile::getProfile($id);
    		// $client = Client::getClient($id);
            $user = User::getUserbyID($id);            
            $data = array_merge($profile->toArray(),$user->toArray());
            //address
            $address = Address::getAddress($id); 
            if($address){
                $country = Country::getCountry($address->country);   
                $address->country = $country;
                $data = array_merge($data,$address->toArray());
            }
            //spouse
            $spouse = Spouse::getSpouse($profile->id);
            if($spouse){
                $data = array_merge($data,$spouse->toArray());
            }
            

            $education = Education::getEducation($profile->id);
            if($education){
                 $data = array_merge($data,$education->toArray());
            }

            $employment = Employment::getEmployment($profile->id);
            if($employment){
                 $state = State::getState($employment->state);
                 // $state = State::getState($employment->state);
                 $employment->state = $state;
                 $data = array_merge($data,$employment->toArray());
            }
            //adding Address in Image
            $data['image'] = "http://www.plawza.net/profile/".$data['image'];

            //child
            $child = Child::getChild($profile->id);
            if($child){
                $data['child'] = $child;
            }
    		// $return['addresss'] = $address;
      //       $return['profile'] = $profile;
            // $return['client'] = $client;
            
			return response()->json(['status'=>TRUE,'data'=>$data], 200);	
    	}else{
			return response()->json(['Message'=>'Please check Api Parameter','status'=>FALSE], 400);
    	}
    }

    public function tester(Request $request)
    {
    	return response()->json($request, 200);	
    }
}
