<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Authassignment;
use App\Cases;
use App\CaseUser;
use App\Client;
use App\Profile;
use App\AdverseParty;
use App\Afap;
use App\Afap_attorney;
use App\Courtinfo;
use App\Otherss;
use App\Otherperent;
use App\Otherwitness;
use App\Otherexhibit;
use App\Otherdamage;
use App\Othersme;
use App\Imigrationinfo;
use App\Corporation;
use App\Incorporations;
use App\Officers;
use App\Subscriptions;
use App\Casecontracts;
use App\Criminallaw;
use App\Familylaw;
use App\Spouseinfo;
use App\Maritalinfo;
use App\Probateinfo;
use App\Realestateinfo;
use App\Bsbainfo;
use App\Pinjury;
use App\Pinjury_doctor;
use App\Pinjury_reponsible;
use App\Pinjury_witness;
use App\Personalinjury;
use App\Insuranceca;
use App\Medicalsprovider;
use App\Healthins;
use App\Defendentautoinfo;
use App\Propertydamage;
use App\Country;

use Validator;
use DB;


class CasesApiController extends Controller
{
    public function getCases(Request $request)
    {
    	$data = $request->all();
    	if(!isset($data['user_id']))
    		return response()->json(['data'=>$data,'status'=>FALSE],400);
    	else $id = $data['user_id'];

    	$auth = Authassignment::getAuthassignment($id);
    	foreach ($auth as $key => $auths) {
    		if($auths->itemname == 'Client'){
    			$case = Cases::getCasebyClient($id);
    			foreach ($case as $key => $case) {
    				$return[] = ['case_id'=>$case->id,'case_name'=>$case->case_no];		
    			}
    		}else{
    			return response()->json(['Message'=>'Not a Client','status'=>FALSE],400);
    		}
    	}

    	if(isset($return))
    		return response()->json(['data'=>$return,'status'=>TRUE],200);
    	else
    		return response()->json(['Message'=>'No Case available','status'=>FALSE],400);
    }

    public function getClientCases(Request $request)
    {
        // $data = $request->all();
        // $datas['user_id'] = $data['client_id'];
        // $request->request->add($datas);
        
        // $return = $this->getCases($request);
        // return $return;
        $data = $request->all();
        $validator = Validator::make($data, [
            'user_id' => 'required|max:255',
            'client_id'=> 'required|max:255',
        ]);

        if ($validator->fails()) { 
            return response()->json(['Message'=>'Please check Api Parameter','status'=>FALSE], 400);
        }else{
            $auth = Authassignment::getRole($data['user_id']);
            if($auth['none']){
                return response()->json(['Message'=>'User doesn\'t exist','status'=>FALSE], 400);  
            }

            if($auth['admin'] || $auth['att']){
                $return = $this->clientcase($data);
                return $return;
            }else{
                return response()->json(['Message'=>'User not allowed','status'=>FALSE], 400);  
            }
        }
    }
    public function clientcase($data)
    {
        $query['b.user_id'] = $data['user_id'];
         $client = Client::getClientProfile($data['client_id']);
            if($client){
                $query['a.user_id']= $client->id;
            
                $cases = DB::table('cases as a')
                        ->Join('case_user as b','b.case_id','=','a.id')
                        ->select('a.id as case_id','a.case_no')
                        ->where($query)
                        ->orderBy('a.id', 'desc')
                        ->get();  
            if(!$cases)            
                return response()->json(['Message'=>'No Case available','status'=>FALSE],400);
            else
                return response()->json(['data'=>$cases,'status'=>TRUE],200);
            }else{
                return response()->json(['Message'=>'Not a Client','status'=>FALSE],400);
            }
    }
    public function getCaseProfile(Request $request){
        $data = $request->all();
        $validator = Validator::make($data, [
            'case_id' => 'required|max:255',
            'user_id' => 'required|max:255',
            'client_id'=> 'required|max:255',
        ]);

        if ($validator->fails()) { 
            return response()->json(['Message'=>'Please check Api Parameter','status'=>FALSE], 400);
        }else{
            $user_id = $data['user_id'];
            $case_id = $data['case_id'];
            $client_id = $data['client_id'];
            $auth = Authassignment::getRole($user_id);
            if($auth['none']){
                return response()->json(['Message'=>'User doesn\'t exist','status'=>FALSE], 400);  
            }

            if($auth['admin'] || $auth['att']){
                $query['id'] = $case_id;
                $client = Client::getClientProfile($client_id);

                if(!$client) 
                    return response()->json(['Message'=>'Client doesn\'t exist','status'=>FALSE], 400);  
                
                $query['user_id'] = $client->id;                
                $case = Cases::getCasebyQuery($query);
                if(!$case->isEmpty()){                    
                    foreach ($case as $key => $case) {
                        $case = $case;
                    }
                    
                    $return['case_profile'] = $this->CaseProfile($case);
                    $return['case_profile']['client_name'] =[
                        'user_id'=>$client->user_id,
                        'first_name'=>$client->first_name,
                        'middle_name'=>$client->middle_name,
                        'last_name'=>$client->last_name,
                    ];
                    $aparty = AdverseParty::getAdversePartyByCaseID($case_id);
                    $return['adverse_party'] = $aparty;

                    if($aparty){
                        //setting up Attorney Adverseparty
                        $afap = Afap::getAfapByApartyID($aparty->id);                        
                    }else{
                        $afap = FALSE;
                    }
                    
                    if($afap){                    
                        $afap['adverse_name'] = $aparty->first_name.' '.$aparty->last_name;
                        $att_afap = Afap_attorney::getAfap_att($afap->id);
                        $afap['afap_att'] = $att_afap;
                        $afap['country'] = Country::getCountry($afap->country);
                    }else{
                        $afap = FALSE;
                    }
                    $return['Attorney_adversparty'] = $afap;
                                        
                    $court_info = Courtinfo::getCourtInfo($case_id);

                    if($court_info) 
                        $return['Court_info'] = $court_info; 
                    else 
                        $return['Court_info'] = FALSE;
                    
                    switch ($case->case_type) {
                        case 'Other':     
                                $others = Otherss::getOtherssByCaseId($case_id);
                                $return['Others'] = $others;
                                if($others){
                                    $return['otherPerson/Entity'] = Otherperent::getOtherPerEnt($others->id);
                                    $return['otherWitness'] = Otherwitness::getOtherWitness($others->id);
                                    $return['otherExhibit'] = Otherexhibit::getOtherExhibit($others->id);
                                    $return['otherDamage'] = Otherdamage::getOtherDamage($others->id);
                                    $return['Othersme'] = Othersme::getOtherme($others->id);
                                    
                                }else{
                                    $return['otherPerson/Entity'] = false;
                                    $return['otherWitness'] = false;
                                    $return['otherExhibit'] = false;
                                    $return['otherDamage'] = false;
                                }
                            break;
                        case 'Immigration':
                                $immigration = Imigrationinfo::getImigrationInfo($case_id);
                                $return['Immigration'] = $immigration;
                            break;
                        case 'Corporation':
                                $corp = Corporation::getCorporation($case_id);
                                if($corp){
                                    $incorporation = Incorporations::getIncorporation($corp->id);
                                    $officers = Officers::getOfficers($corp->id);
                                    $subscriptions = Subscriptions::getSubscriptions($corp->id);
                                    $corps['corporation']=$corp;
                                    $corps['incorporation'] =$incorporation;
                                    $corps['officers'] =$officers;
                                    $corps['subscriptions'] =$subscriptions;
                                }else{
                                   $corps = FALSE; 
                                }
                                $return['Corporation'] = $corps;
                            break;
                        case 'Contracts':
                                $return['Contracts'] = Casecontracts::getCaseContracts($case_id);
                            break;
                        case 'Criminal Law':
                                $return['Criminal Law'] = Criminallaw::getCriminallaw($case_id);
                            break;
                        case 'Family Law':
                                $famLaw = Familylaw::getFamilylaw($case_id);
                                if($famLaw){
                                    $query1['familylaw_id'] = $famLaw->id;
                                    $spouse = Spouseinfo::getSpousebyQuery($query1);
                                    $famLaws['spouse'] = $spouse;
                                    $maritalinfo = Maritalinfo::getMaritalinfobyQuery($query1);
                                    $famLaws['maritalinfo'] = $maritalinfo;
                                    $return['Family Law'] = $famLaws;
                                }else{
                                    $return['Family Law'] = $famLaw;
                                }
                            break;
                        case 'Probate':
                                $return['Probate'] = Probateinfo::getProbateinfo($case_id);
                            break;
                        case 'Real Estate':
                                $realestate = Realestateinfo::getRealestateinfo($case_id);
                                if($realestate){
                                    $rstate['Realestateinfo'] = $realestate;
                                    $rstate['bsba'] = Bsbainfo::getBsbainfo($realestate->id);
                                    $return['Realestateinfo'] = $rstate;
                                }else{
                                    $return['Realestateinfo'] = $realestate; //FALSE
                                }

                            break;
                        case 'Malpractice':
                                $pinjury = Pinjury::getPinjury($case_id);
                                if($pinjury){
                                $Malpractice['pinjury'] = $pinjury;
                                $Malpractice['pinjury_doctor'] = Pinjury_doctor::getPIdoctor($pinjury->id);
                                $Malpractice['pinjury_responsible'] = Pinjury_reponsible::getPIresponsible($pinjury->id);
                                $Malpractice['pinjury_witness']  = Pinjury_witness::getPIwitness($pinjury->id);
                                    $return['Malpractice'] = $Malpractice;
                                }else{
                                    $return['Malpractice'] = $pinjury; //FALSE
                                }
                            break;
                        case 'Personal Injury':
                                $pi = Personalinjury::getPI($case_id);
                                if($pi){

                                    $Personalinjury['Personalinjury'] = Insuranceca::getInsuranceca($pi->id);
                                    $Personalinjury['Propertydamage'] = Propertydamage::getPropertydamage($pi->id);
                                    $Personalinjury['Healthins'] = Healthins::getHealthins($pi->id);
                                    $Personalinjury['Defendant'] = Defendentautoinfo::getDefendentautoinfo($pi->id);
                                    $Personalinjury['Medicalsprovider'] = Medicalsprovider::getMedicalsprovider($pi->id);
                                    
                                    $return['Personal Injury'] = $Personalinjury;
                                }else{
                                    $return['Personal Injury'] = $pi;
                                }
                            break;
                        default:
                                return response()->json(['Message'=>'Invalid Case Type','status'=>FALSE], 400);
                            break;
                    }

                    return response()->json(['data'=>$return,'status'=>TRUE], 200);  

                }else{
                    return response()->json(['Message'=>'No case available','status'=>FALSE], 400);
                }
                
            }else{
                return response()->json(['Message'=>'User not allowed','status'=>FALSE], 400);  
            }
        }
    }

    public function CaseProfile($case)
    {
        $case_profile['case_number'] = $case->case_number;
                    $case_profile['case_name'] = $case->case_no;
                    $case_profile['case_type'] = $case->case_type;
                    $case_profile['case_natureofrep'] = $case->nature_of_representation;
                    $case_profile['statute_applicable'] = $case->statue_applicable;
                    $case_profile['statute_of_limitation'] = $case->statute_of_limitation;
                    $case_profile['retainer_deposit'] = $case->retainer_deposit;
                    //get attorney profile of this case
                    $att = DB::table('case_user as a')
                        ->Join('profile as b','b.user_id','=','a.user_id')
                        ->where('a.case_id','=',$case->id)
                        ->whereIn('a.user_id',
                            Authassignment::where('itemname','=','attorney')
                            ->orWhere('itemname','=','admin')
                            ->select('userid')
                            ->get())
                        ->select(
                            'b.user_id',
                            'b.first_name',
                            'b.middle_name',
                            'b.last_name')
                        ->get();     
                    if($att) 
                        $case_profile['case_attorney'] = $att;
                    else
                        $case_profile['case_attorney'] = FALSE;
                    //get staff profile of this case
                    $staff = DB::table('case_user as a')
                        ->Join('profile as b','b.user_id','=','a.user_id')
                        ->where('a.case_id','=',$case->id)
                        ->whereIn('a.user_id',
                            Authassignment::where('itemname','=','staff')
                            ->select('userid')
                            ->get())
                        ->select(
                            'b.user_id',
                            'b.first_name',
                            'b.middle_name',
                            'b.last_name')
                        ->get();     
                    if($staff) 
                        $case_profile['case_staff'] = $staff;
                    else
                        $case_profile['case_staff'] = FALSE;

        return $case_profile;
    }
}
