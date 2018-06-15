<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use App\Http\Requests;
use App\User;
use App\Profile;
use App\Address;
use App\AttorneyInfo;
use App\CalendarUser;
use App\Calendar;
use App\Cases;
use App\CaseUser;
use DB;

class ApiController extends Controller
{
    // public $token;
    // public function __construct(Request $request)
    // {
    //     $this->token['_token'] = $request->session()->get('_token');
    // }

    // public function getKey(Request $request)
    // {
    //   $return['token'] = $this->token;
    //   $return['token1'] = csrf_token();
    //   return response()->json($return, 200);
    // }


    public function login(Request $request){      
       /*
        @ getUser():
            1 = FAILED: user doesn't exist
            2 = FAILED: incorrect password
            3 = FAILED: user is deactivated
            4 = OK : successfully login (Authenticated user)

        */
      $data = $request->all();
      if(!isset($data['email']) && !isset($data['password']))
          return response()->json(['Message'=>'Please check Api Parameter','status'=>FALSE], 400);

      $user = User::getUser($data);     
      if($user['status'] == 4){ //authenticate user login, if not exist return ERROR INFO 
        return response()->json($user, 200);        
      }else{
         Auth::logout();
        return response()->json($user, 400);
      }

    }

    public function logout($value='')
    {
      Auth::logout();
      return response()->json(['Message'=>'successfully Logout','status'=>true], 200);
    }

    public function apiManual()
    {
      $login['name_required'] = ['email,password'];
      $login['return'] = ['Users table data'];
      
      $signup['name_required'] = ['email,password,firm_id'];
      $signup['return'] = ['user created'];
      
      $edit_user['name_required'] = ['_method=put,table users info'];
      $edit_user['return'] = ['true:success,otherwise false'];

      
      $data['GET']['/manual'] = ['return'=>'show the url and required name'];
      $data['POST']['api/login'] = $login;
      $data['POST']['/user'] = $signup;
        
      $data['PUT']['/user'] = $edit_user;

      $data['DELETE']['/user'] = 'update is_active=0 of current user login';
      return response()->json($data, 200);
    }


    public function dbtest()
    {
      $tables = DB::select('SHOW TABLES');
      // SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = "users"'
      // SELECT * FROM Northwind.INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = N'Customers'
        $columns = DB::select('SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = "users" ');
        if(DB::connection()){
          $db = DB::connection()->getDatabaseName();
           $data['db'] = 'Connected to:'.$db;
        }else{
           $data['db'] = 'wala';
        }
        $a = null;
        foreach ($columns as $key => $value) {
          $a .= ",'".$value->COLUMN_NAME."'";
        }
         $data['columns'] = $a;
         // $data['tables'] = $tables;
        
         return response()->json(['data'=>$data,'status'=>TRUE], 200);
    }

    public function index($value='')
    {
     
    }

}
