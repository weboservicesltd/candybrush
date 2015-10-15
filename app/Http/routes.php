<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

use LucaDegasperi\OAuth2Server\Authorizer;

Route::get('/', function () {
    return view('welcome');
});
/*
 *  Dingo Api Routes
 */
$api = app('Dingo\Api\Routing\Router');
$api->version('v1', /**
 * @param $api
 */
    function($api){
$api->get('users','App\Http\Controllers\UserController@index');
$api->get('users/{id}', 'App\Http\Controllers\UserController@show');
$api->post('auth/login', function (\Illuminate\Http\Request $request){
    $credentials = $request->only('email', 'password');
    try {
        // verify the credentials and create a token for the user
        if(!(\App\User::where('email','=',$credentials['email'])->get(['confirmed'])[0]['confirmed'])){
            return response()->json(['error'=>'Account not activated'],401);
        }
        if (! $token = \Tymon\JWTAuth\Facades\JWTAuth::attempt($credentials)) {
            return response()->json(['error' => 'invalid_credentials'], 401);
        }
    } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
        // something went wrong
        return response()->json(['error' => 'could_not_create_token'], 500);
    }

    // if no errors are encountered we can return a JWT
    return response()->json(compact('token'));
});
    /*
     * for user registration or sign up
     */
  $api->post('signup','App\Http\Controllers\RegistrationController@store');
    /*
     * * for user account activation
     */
    $api->post('users/activate/','App\Http\Controllers\RegistrationController@activateAccount');
    /*
     * * for creating storing user forgot password code
     */
    $api->post('users/forgotPassword/','App\Http\Controllers\RegistrationController@forgotPassword');
    /*
     * for validating the forgot password code i.e. this checks if forgot password code exist in the database
     */
    $api->post('users/validateForgotPasswordCode/','App\Http\Controllers\RegistrationController@validateCode');
    /*
     * for password reset
     */
    $api->post('users/resetPassword/','App\Http\Controllers\RegistrationController@resetPassword');
    /*
     *  for user profile update
     */
    $api->put('userProfile/update/{id}','App\Http\Controllers\UserProfileController@update');
    /*
     * for show user profile
     */
    $api->get('userProfile/{id}','App\Http\Controllers\UserProfileController@show');
    /**
     *  for show user portfolio and store portfolio
     */
    $api->get('userPortfolio/{id}','App\Http\Controllers\UserPortfolioController@show');
    $api->post('userPortfolio','App\Http\Controllers\UserPortfolioController@store');

    /***
     * for wallet transactions i.e. credit/debit
     */
    $api->put('users/walletTransaction','App\Http\Controllers\UserwalletController@update');
    /**
     * for activate and  inactivate user
     */
    $api->put('users/changeStatus','App\Http\Controllers\UserController@update');
    /**
     * for create tags
     */
    $api->post('tag','App\Http\Controllers\TagController@store');
    /**
     * for deleting tags
     */
    $api->delete('tag','App\Http\Controllers\TagController@destroy');
    /**
     * for update tags
     */
    $api->put('tag','App\Http\Controllers\TagController@update');
    /**
     *for get all tags
     */
    $api->get('tag','App\Http\Controllers\TagController@index');
    /**
     * for get specific tag
     */
    $api->get('tag/{id}','App\Http\Controllers\TagController@show');
    /**
     * for adding packages
     */
    $api->post('user/packages','App\Http\Controllers\PackagesController@store');
    /**
     * for creating new category
     */
    $api->post('category','App\Http\Controllers\CategoryController@store');
    /**
     * for updating categories
     */
    $api->put('category','App\Http\Controllers\CategoryController@update');
    /**
     * for getting all categories
     */
    $api->get('category','App\Http\Controllers\CategoryController@index');
    /**
     * for getting particular category
     */
    $api->get('category/{id}','App\Http\Controllers\CategoryController@show');
    /**
     * for delete category
     */
    $api->delete('category/{id}','App\Http\Controllers\CategoryController@destroy');
    /**
     *
     * for adding comment and rating package
     */
    $api->post('packages/reviews/store','App\Http\Controllers\ReviewController@store');
    $api->post('packages/reviews','App\Http\Controllers\ReviewController@index');
    $api->get('packages/reviews/adminVerify/{id}','App\Http\Controllers\ReviewController@admin_verified');

    /**
     * for sending message
     */
    $api->post('messages/send','App\Http\Controllers\MessageController@store');
    /**
     * for getting inbox messages
     */
    $api->post('messages/inbox','App\Http\Controllers\MessageController@inbox');
    /**
     * for getting out box messages
     */
    $api->post('messages/sentbox','App\Http\Controllers\MessageController@sentBox');
    /**
     * for deleting  messages
     */
    $api->post('messages/delete/{id}','App\Http\Controllers\MessageController@destroy');
        /**
         * get Inbox messages
         */
    $api->get('user/{id}/inbox/','App\Http\Controllers\UserController@getInboxMessages');
    /**
    * get Sent box messages
    */
    $api->get('user/{id}/sentMessages/','App\Http\Controllers\UserController@getSentMessages');
        /**
         * for deleting messages
         */
        $api->delete('message/{id}','App\Http\Controllers\MessageController@destroy');
});

/*
 * OAuth2 Server Routes
 */
Route::get('oauth/authorize', ['as' => 'oauth.authorize.get','middleware' => ['check-authorization-params', 'auth'], function() {
    // display a form where the user can authorize the client to access it's data
    $authParams = Authorizer::getAuthCodeRequestParams();
    $formParams = array_except($authParams,'client');
    $formParams['client_id'] = $authParams['client']->getId();
    return View::make('oauth.authorization-form', ['params'=>$formParams,'client'=>$authParams['client']]);
}]);
Route::post('oauth/authorize', ['as' => 'oauth.authorize.post','middleware' => ['csrf', 'check-authorization-params', 'auth'], function() {

    $params = Authorizer::getAuthCodeRequestParams();
    $params['user_id'] = Auth::user()->id;
    $redirectUri = '';

    // if the user has allowed the client to access its data, redirect back to the client with an auth code
    if(Input::get('approve') !== null) {
        $redirectUri = Authorizer::issueAuthCode('user', $params['user_id'], $params);
    }

    // if the user has denied the client to access its data, redirect back to the client with an error message
    if (Input::get('deny') !== null) {
        $redirectUri = Authorizer::authCodeRequestDeniedRedirectUri();
    }
    return Redirect::to($redirectUri);
}]);
Route::post('oauth/access_token', function() {
    return Response::json(Authorizer::issueAccessToken());
});
Route::controller('auth', 'Auth\AuthController');