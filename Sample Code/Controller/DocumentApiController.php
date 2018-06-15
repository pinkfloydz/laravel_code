<?php
namespace Symfony\Component\HttpFoundation\File\UploadedFile;
namespace App\Http\Controllers;


// use Illuminate\Http\Request;
use Request;
use App\Http\Requests;
use App\Authassignment;
use App\Client;
use App\Profile;
use App\Documents;
use App\DocumentUser;
use App\Cases;
use App\CaseUser;
use Validator;
use Storage;
use DB;
class DocumentApiController extends Controller
{
    public function getDocs(Request $request)
    {
    	$data = $request::all();
    	if(isset($data['user_id'])){
    		$user_id = $data['user_id'];
    		$docs = Documents::getDocs($user_id);
    		if(!$docs->isEmpty()){
    			foreach ($docs as $key => $value) {
    				$auth = Authassignment::getAuthassignment($value->created_by);
    				$client = 0;
    				$att = 0;
    				$admin = 0;
		            foreach ($auth as $key => $auth) {
		            	if($auth->itemname == 'Client'){
		            		$client = 1;
		            	}
		            	if($auth->itemname == 'Attorney'){
		            		$att = 1;
		            	}
		            	if($auth->itemname == 'Client'){
		            		$admin = 1;
		            	}
		            }
		                if($client == 1){
		                    $profile1 = Client::getClientProfile($value->created_by);
		                }else{
		                    $profile1 = Profile::getProfile($value->created_by);                                
		                }         
		                $created_by= ucwords($profile1->last_name.', '.$profile1->first_name.' '.$profile1->middle_name);
		                $value->created_by = $created_by;

		          
    			}
	    			           
	            return response()->json(['data'=>$docs,'status'=>TRUE], 200);
	        }else{
	        	return response()->json(['Message'=>'No document available','status'=>FALSE], 400);
	        }
	        
        }else{
        	return response()->json(['Message'=>'Please check Api Parameter','status'=>FALSE], 400);
        }

    }

    public function uploadDocs(Request $request)
    {
    	
		// if (Request::hasFile('file'))
		// {
			$data = $request::all();
			$rules = array(
				'file' => 'required|max:10000|mimes:jpg,jpeg,gif,png,doc,docx,pdf,ppt,pptx,odt,odp,rtf,wpd',
			);
			$validator = Validator::make(
			    $data,
			    [
			        'file'      => 'required',
			        'file'      => 'required|mimes:jpg,jpeg,gif,png,doc,docx,pdf,ppt,pptx,odt,odp,rtf,wpd',
			        'dname'	=> 'required',
			        'case_id'	=> 'required',
			    ]
			);
			if (!$validator->fails()) {
				$auth = Authassignment::getAuthassignment($data['user_id']);
	    		if($auth->isEmpty()) return response()->json(['Message'=>'Not a User','status'=>FALSE], 400);
	    		foreach ($auth as $key => $auth) {
	    			if($auth->itemname == 'Client'){
	    				$role = 'Client';
	    			}else{
	    				if($auth->itemname == 'Attorney'){
	    					$role = 'Attorney';
	    				}else{
	    					$role = 'Admin';
	    				}
	    			}
	    		}
				$file = $request::file('file');
				$document = new Documents;
				if(!isset($data['display_name']))	$data['display_name'] = '';
				if(!isset($data['notes']))	$data['notes'] = '';
				$data['doc_type'] = $file->guessClientExtension();
				$data['doc_name'] = $file->getClientOriginalName();
				$data['created'] = date_create();
				$data['modified'] = date_create();
				


				$data['doc_size'] = $file->getClientSize();
				$data['filename'] = strtolower(str_replace(' ', '', uniqid().'-'.$data['doc_name']));
				// $data['slug'] = strtolower(str_replace(' ', '', 'doc.'.uniqid().'.'.$data['doc_name']));

				$data['slug'] = uniqid('doc.',true);
				if($role != 'Client'){
					$data['created_by'] = $data['user_id'];
					$data['user_id'] = $data['client_id'];
					unset($data['client_id']);
				}else{
					$data['to_client'] = 0;
					$data['created_by'] = $data['user_id'];
				}
				$document->fill($data);
				
				if($document->save()){
					$request::file('file')->move(storage_path('documents'),$data['filename']);
					return response()->json(['status'=>TRUE], 200);
				}else{
					return response()->json(['Message'=>'FAILED to Save','status'=>FALSE], 400);
				}
		    } else {
		    	return response()->json(['Message'=>$validator->errors(),'status'=>FALSE], 400);
		    }
			
		// }else{
		// 	return response()->json(['Message'=>'Please check Api Parameter','status'=>FALSE], 400);
		// }
    }

    public function getCTSdocs(Request $request)
    {
    	$data = $request::all();
    	$validator = Validator::make($data, [
            'case_id' => 'required|max:255',
            'user_id' => 'required|max:255',
            'client_id' => 'required|max:255',
        ]);


        if ($validator->fails()) {            
            return response()->json(['Message'=>'Please check Api Parameter','status'=>FALSE], 400);
        }else{

        	if(!isset($data['entry_time'])){ 
    			// $data['entry_time'] = date('Y-m-d H:i:s', strtotime( date('Y-m-d')." - 1 day" ));
    			$data['entry_time'] = date('Y-m-d 00:00:00',strtotime('now'));
    		}
    		$user_id = $data['user_id'];
    		$case_id = $data['case_id'];
    		$auth = Authassignment::getAuthassignment($user_id);
    		if($auth->isEmpty()) return response()->json(['Message'=>'Not a User','status'=>FALSE], 400);
    		// foreach ($auth as $key => $auth) {
    				$client = 0;
    				$att = 0;
    				$admin = 0;
    			foreach ($auth as $key => $auth) {
		            	if($auth->itemname == 'Client'){
		            		$client = 1;
		            	}
		            	if($auth->itemname == 'Attorney'){
		            		$att = 1;
		            	}
		            	if($auth->itemname == 'Admin'){
		            		$admin = 1;
		            	}
		            }
    			if($att == 1 || $admin == 1){
    				// if($att == 1){
    				$query1['id'] = $data['case_id'];
    				$c_profile = Client::getClientProfile($data['client_id']);  
    				 				
    				if(!$c_profile){
    					return response()->json(['Message'=>'No Client Found','status'=>FALSE], 400);
    				}
    				$query1['user_id'] = $c_profile->id;
    				$case = Cases::getCasebyQuery($query1);
    				
    				if($case->isEmpty()){
    					return response()->json(['Message'=>'No document available','status'=>FALSE], 400);
    				}

    				$docs = Documents::getCTSDocs($user_id,$case_id,$data['entry_time']);

	    				if(!$docs->isEmpty()){
			    			foreach ($docs as $key => $value) {
			    				$client = 0;
			    				$att = 0;
			    				$admin = 0;
			    				$auth = Authassignment::getAuthassignment($value->user_id);
			    				foreach ($auth as $key => $auth) {
					            	if($auth->itemname == 'Client'){
					            		$client = 1;
					            	}
					            	if($auth->itemname == 'Attorney'){
					            		$att = 1;
					            	}
					            	if($auth->itemname == 'Admin'){
					            		$admin = 1;
					            	}
					            }
					            	if($att == 1 || $admin == 1){
					                    $profile1 = Profile::getProfile($value->user_id);
					                }else{
					                	$profile1 = Client::getClientProfile($value->user_id);
					                }
					                	$client_name = ucwords($profile1->last_name.', '.$profile1->first_name.' '.$profile1->middle_name);
					                	$value->client_name = $client_name;
			    			}
			    			unset($value->created_by);
				            return response()->json(['data'=>$docs,'status'=>TRUE], 200);
				        }else{
				        	return response()->json(['Message'=>'No document available','status'=>FALSE], 400);
				        }

			        // }else{
			        // 	 return response()->json(['Message'=>'Admin not allowed','status'=>FALSE], 400);
			        // }
    			}else{
    				return response()->json(['Message'=>'Client not allowed','status'=>FALSE], 400);
    			}
    		
    		
		
        }
    }

    public function setCTSdocs(Request $request)
    {
    	$data = $request::all();
			$rules = array(
				'file' => 'required|max:10000|mimes:jpg,jpeg,gif,png,doc,docx,pdf,ppt,pptx,odt,odp,rtf,wpd',
			);
			$validator = Validator::make(
			    $data,
			    [
			        'file'      => 'required',
			        'file'      => 'required|mimes:jpg,jpeg,gif,png,doc,docx,pdf,ppt,pptx,odt,odp,rtf,wpd',
			        'dname'	=> 'required',
			        'case_id'	=> 'required',
			        'client_id' => 'required',
			        'to_client' => 'required',
			    ]
			);


		if (!$validator->fails()) {
				$return = $this->uploadDocs($request);
				return $return;
		} else {
		    	return response()->json(['Message'=>$validator->errors(),'status'=>FALSE], 400);
		    }
		
    }

    public function getOTSdocs(Request $request)
    {
    	$data = $request::all();
    	$validator = Validator::make($data, [
            'user_id' => 'required|max:255',
        ]);


        if ($validator->fails()) {            
            return response()->json(['Message'=>'Please check Api Parameter','status'=>FALSE], 400);
        }else{
        	$profile = Profile::getProfile($data['user_id']);
				if(!$profile)
					return response()->json(['Message'=>'ID is not a client / Invalid ID','status'=>FALSE], 400);
        	if(!isset($data['entry_time'])){ 
    			// $data['entry_time'] = date('Y-m-d H:i:s', strtotime( date('Y-m-d')." - 1 day" ));
    			$data['entry_time'] = date_create();
    		}
    		
    		$document = Documents::where('created','>=',$data['entry_time'])
    		->where(['created_by'=>$data['user_id']])
    		->orwhere(['user_id'=>$data['user_id']])
    		->where('created','>=',$data['entry_time'])
    		->get([
                'user_id',
                'id',
                'dname',
                'doc_name',
                'doc_scope',
                'notes',
                'created',
                'created_by',
                'to_client',
        	]);
    		
        	if(!$document->isEmpty()){
        		
				foreach ($document as $key => $value) {
					$profile1 = Profile::getProfile($value->created_by);
					$value->created_by = [
						'last_name'=>$profile1->last_name,
						'first_name'=>$profile1->first_name,
						'middle_name'=>$profile1->middle_name
					];
				}
        		return response()->json(['Message'=>$document,'status'=>TRUE], 200);
        	}else{
        		return response()->json(['Message'=>'No available document','status'=>FALSE], 400);
        	}
		
        }
    }

    public function setOTSdocs(Request $request)
    {
    	$data = $request::all();
			$rules = array(
				'file' => 'required|max:10000|mimes:jpg,jpeg,gif,png,doc,docx,pdf,ppt,pptx,odt,odp,rtf,wpd',
			);
			$validator = Validator::make(
			    $data,
			    [
			        'file'      => 'required',
			        'file'      => 'required|mimes:jpg,jpeg,gif,png,doc,docx,pdf,ppt,pptx,odt,odp,rtf,wpd',
			        'dname'	=> 'required',
			        'user_id'	=> 'required',
			    ]
			);


		if (!$validator->fails()) {
				$profile = Profile::getProfile($data['user_id']);
				if(!$profile)
					return response()->json(['Message'=>'ID is not a client / Invalid ID','status'=>FALSE], 400);
				$document = new Documents;
				$file = $request::file('file');
				if(!isset($data['display_name']))	$data['display_name'] = '';
				if(!isset($data['notes']))	$data['notes'] = '';
				$data['doc_type'] = $file->guessClientExtension();
				$data['doc_name'] = $file->getClientOriginalName();
				$data['created'] = date_create();
				$data['modified'] = date_create();
				
				$data['doc_size'] = $file->getClientSize();
				$data['doc_name'] = preg_replace('/[^A-Za-z0-9.\-]/', '', $data['doc_name']);
				$data['filename'] = strtolower(str_replace(' ', '', uniqid().'-'.$data['doc_name']));
				$data['created_by'] = $data['user_id'];
				// $data['slug'] = strtolower(str_replace(' ', '', 'doc.'.uniqid().'.'.$data['doc_name']));
				$data['slug'] = uniqid('doc.',true);
				$data['user_id'] = null;
				$document->fill($data);
				
				if($document->save()){
					if(isset($data['send_to'])){
						foreach ($data['send_to'] as $key => $value) {
							$docuser = new DocumentUser;
							$docuser->document_id = $document->id;
							$docuser->user_id = $value;
							if(!$docuser->save()){
								return response()->json(['Message'=>'Error on sending to users','status'=>FALSE], 400);
							}
						}						
						
					}
					$request::file('file')->move(storage_path('documents'),$data['filename']);
					return response()->json(['Message'=>'Successfully Save','status'=>TRUE], 200);
				}else{
					return response()->json(['Message'=>'FAILED to Save','status'=>FALSE], 400);
				}
		} else {
		    	return response()->json(['Message'=>$validator->errors(),'status'=>FALSE], 400);
		    }
		
    }
}	

