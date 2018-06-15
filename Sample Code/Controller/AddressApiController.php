<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Support\Facades\Auth;
use App\Address;
class AddressApiController extends Controller
{
   	public function getAddress()
    {
    	$id = Auth::id();
    	$address = Address::getAddress($id);
    	if($address){
    		return response()->json(['address'=>$address,'status'=>TRUE],200);
    	}else{
    		return response()->json(['Message'=>'No Address has been set.','status'=>FALSE],400);
    	}
    }

    public function setAddress(Request $request)
    {
    	# code...
    }

    public function updateAddress(Request $request)
    {

    }

    public function deleteAddress()
    {
    	echo Auth::id();
    }
}
