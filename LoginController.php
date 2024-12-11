<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CreatorGroupChapter;
use App\Models\CreatorGroupNote;
use App\Models\CreatorSubjectNote;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\EmailVerification;
use App\Models\FavoriteNote;
use App\Models\Note;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    /**
     * @Function:        <signUp>
     * @Author:          Bansari Lathiya( Sixty13 )
     * @Created On:      <01-08-2022>
     * @Last Modified By:Bansari Lathiya
     * @Last Modified:   <01-08-2022>
     * @Description:     <This function works for siging up Users>
     * @return \Illuminate\Http\Response
     */
    public function signUp(Request $request){
        try {
            $validator = Validator::make($request->all(), [
                'name' => ['required', 'max:30'],
                'username' => ['required', 'regex:/^(?!.*\.\.)(?!.*\.$)[^\W][\w.]{5,29}$/', 'min:5', 'unique:users', 'max:30'],
                'email' => ['required', 'email', 'unique:users', 'max:30'],
                'password' => ['required', 'regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/']
            ]);

            if($validator->fails()){
                return response()->json(['success' => false, "error" => $this->validationMessage($validator->errors()->toArray())], 422);
            }
            $data = $request->all();
            $data['fresh_login'] = 1;
            $data['password'] = bcrypt($request->password);
            $customer = User::create($data);
            if($request->keepme == true){
                $access_token = auth()->guard('customer-api')->login($customer);
                User::where(['id' => $customer->id])->update(
                    ['fresh_login' => 0]
                );
                return response()->json(['success' => true, 'data' => ['access_token' => $access_token, 'customer' => $customer, 'message' => 'Account successfully created and logged in.']],200);
            }else{
                return response()->json(['success' => true, 'data' => ['customer' => $customer, 'message' => 'Account successfully created.']],200);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => ['message' => 'Something went wrong.', 'error_message' => $e]], 500);
        }
    }

    /**
     * @Function:        <login>
     * @Author:          Bansari Lathiya( Sixty13 )
     * @Created On:      <01-08-2022>
     * @Last Modified By:Bansari Lathiya
     * @Last Modified:   <01-08-2022>
     * @Description:     <This function works for log in User>
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request){
        try {
            $validator = Validator::make($request->all(),[
                'username' => ['required'],
                'password' => ['required'],
            ]);
            if($validator->fails()){
                return response()->json(['success' => false, "error" => $this->validationMessage($validator->errors()->toArray())], 422);
            }
            $credentials = request(['username', 'password']);
            $fieldType = filter_var($credentials['username'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
            $customer = User::where($fieldType, $credentials['username'])->first();
            if($customer && $customer->status == 1) {
                if(Hash::check($credentials['password'], $customer->password)){
                    $access_token = auth()->attempt([
                        $fieldType => $credentials['username'],
                        'password' => $credentials['password']
                    ], ['exp' => Carbon::now()->addDays(365)->timestamp]);
                    User::where(['id' => $customer->id])->update(
                        ['fresh_login' => 0]
                    );
                    if ($access_token) {
                        return response()->json(['success' => true, 'data' => ['access_token' => $access_token, 'customer' => $customer, 'message' => 'Logged in successfully.']],200);
                    }else{
                        return response()->json(['success' => false, 'error' => $this->validationMessage(['message' => ['Your password is invalid.']])], 422);
                    }
                }else{
                    return response()->json(['success' => false, 'error' => $this->validationMessage(['message' => ['Your password is invalid.']])], 422);
                }
            }else{
                return response()->json(['success' => false, 'error' => $this->validationMessage(['message' => ['This user does not exist.']])], 422);
            }

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => ['message' => 'Something went wrong.', 'error_message' => $e]], 500);
        }
    }

     /**
     * @Function:        <forgotPassword>
     * @Author:          Bansari Lathiya( Sixty13 )
     * @Created On:      <02-08-2022>
     * @Last Modified By:Bansari Lathiya
     * @Last Modified:   <02-08-2022>
     * @Description:     <This function works for sending reset password OTP to user.>
     * @return \Illuminate\Http\Response
     */
    public function forgotPassword(Request $request){
        try {
            $validator = Validator::make($request->all(),[
                'email' => ['required', 'email'],
            ]);

            if($validator->fails()){
                return response()->json(['success' => false, "error" => $this->validationMessage($validator->errors()->toArray())], 422);
            }
            $customer = User::where('email', $request->email)->first();
            if($customer){
                $data = [];
                $data['code'] = mt_rand(1000, 9999);
                $emailVerification = new EmailVerification();
                $emailVerification->customer_id = $customer->id;
                $emailVerification->email = $request->email;
                $emailVerification->code = $data['code'];
                $emailVerification->save();
                
                Mail::send('email', $data, function ($message) use ($request) {
                    $message->to($request->email, $request->email)
                    ->subject('code for reset password');
                });
                return response()->json(['success' => true, 'data' => ['message' => 'Code sent successfully to your email', 'code' => $data['code']]],200);
            }
            else{
                return response()->json(['success' => false, 'error' => $this->validationMessage([ 'email' => ['This email is not registered with us.'] ])], 422);
            }

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => ['message' => 'Something went wrong.', 'error_message' => $e]], 500);
        }
    }

    /**
     * @Function:        <resetPassword>
     * @Author:          Bansari Lathiya( Sixty13 )
     * @Created On:      <02-08-2022>
     * @Last Modified By:Bansari Lathiya
     * @Last Modified:   <02-08-2022>
     * @Description:     <This function works for resetting customer's password>
     * @return \Illuminate\Http\Response
     */
    public function resetPassword(Request $request){
        try {
            $validator = Validator::make($request->all(),[
                'email' => ['required', 'email'],
                'code'  => ['required'],
                'new_password' => ['required', 'min:8', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*(_|[^\w])).+$/'],
                'confirm_password' => ['same:new_password'],
            ]);

            if($validator->fails()){
                return response()->json(['success' => false, "error" => $this->validationMessage($validator->errors()->toArray())], 422);
            }

            $checkUser = User::where('email', $request->email);
            if($checkUser->exists()) {
                $verify = EmailVerification::where(['email' => $request->email, 'code' => $request->code])->first();
                if($verify) {
                    $checkUser->update(['password' => bcrypt($request->new_password)]);
                    $verify->delete();
                    return response()->json(['success' => true, 'data' => ['message' => 'Password reset successfully.']],200);
                } else {
                    return response()->json(['success' => false, 'error' => $this->validationMessage(['code' => ['The code is incorrect']])], 422);
                }
            } else {
                return response()->json(['success' => false, 'error' => $this->validationMessage([ 'email' => ['Please enter correct email']])], 422);
            }

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => ['message' => 'Something went wrong.', 'error_message' => $e]], 500);
        }
    }

    /**
     * @Function:        <logout>
     * @Author:          Bansari Lathiya( Sixty13 )
     * @Created On:      <02-08-2022>
     * @Last Modified By:Bansari Lathiya
     * @Last Modified:   <02-08-2022>
     * @Description:     <This function works for log the user out>
     * @return \Illuminate\Http\Response
     */
    public function logout()
    {
        Auth::guard('customer-api')->logout();
        return response()->json(['success' => true, 'data' => ['message' => 'Log out successfully.']], 200);
    }

    
    /**
     * @Function:        <deleteAccount>
     * @Author:          Bansari Lathiya( Sixty13 )
     * @Created On:      <04-08-2022>
     * @Last Modified By:Bansari Lathiya
     * @Last Modified:   <04-08-2022>
     * @Description:     <This function works for delete user account>
     * @return \Illuminate\Http\Response
     */
    public function deleteAccount(Request $request){
        try {
            $user_id = auth()->guard('customer-api')->user()->id;
            $customer = User::where('id', $user_id)->where('status', 1)->first();
            if($customer){
                // $customer['status'] = 0;
                // $customer->save();
                User::where(['id' => $user_id])->delete();
                return response()->json(['success' => true, 'data' => ['message' => 'Account deleted successfully.']],200);
            }else{
                return response()->json(['success' => false, 'error' => ['message' => 'Something went wrong']], 422);
            }

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => ['message' => 'Something went wrong.', 'error_message' => $e]], 500);
        }
    }   
}