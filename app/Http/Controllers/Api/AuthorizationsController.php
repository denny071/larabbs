<?php

namespace App\Http\Controllers\Api;

use Auth;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\Api\AuthorizationRequest;
use App\Http\Requests\Api\SocialAuthorizationRequest;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response as Psr7Response;

class AuthorizationsController extends Controller
{
//    public function store(AuthorizationRequest $request)
//    {
//        $username = $request->username;
//
//        filter_var($username, FILTER_VALIDATE_EMAIL) ?
//            $credentials['email'] = $username :
//            $credentials['phone'] = $username;
//
//        $credentials['password'] = $request->password;
//
//        if (!$token = \Auth::guard('api')->attempt($credentials)) {
//            return $this->response->errorUnauthorized(trans('auth.failed'));
//        }
//
//        return $this->respondWithToken($token);
//    }

//    public function update()
//    {
//        $token = Auth::guard('api')->refresh();
//        return $this->respondWithToken($token);
//    }


//    public function destroy()
//    {
//        Auth::logout();
//        return $this->response->noContent();
//    }

    public function store(AuthorizationRequest $originRequest, AuthorizationServer $server,ServerRequestInterface $serverRequest)
    {
        try {
            return $server->respondToAccessTokenRequest($serverRequest, new Psr7Response)->withStatus(201);
        }catch (OAuthServerException $e) {
            return $this->response->errorUnauthorized($e->getMessage());
        }
    }

    public function update(AuthorizationServer $server, ServerRequestInterface $serverRequest)
    {
        try {
            return $server->respondToAccessTokenRequest($serverRequest, new Psr7Response);
        } catch (OAuthServerException $e) {
            return $this->response->errorUnauthorized($e->getMessage());
        }
    }

    public function destroy()
    {
        $this->user()->token()->revoke();
        return $this->response->noContent();
    }

    public function socialStore($type, SocialAuthorizationRequest $request)
    {
        if (!in_array($type, ['weixin'])) {
            return $this->response->errorBadRequest();
        }

        $driver = \Socialite::driver($type);

        try {
            if ($code = $request->code) {
                $response = $driver->getAccessTokenResponse($code);
                $token = array_get($response, 'access_token');
            } else {
                $token = $request->access_token;

                if ($type == 'weixin') {
                    $driver->setOpenId($request->openid);
                }
            }

            $oauthUser = $driver->userFromToken($token);
        } catch (\Exception $e) {
            return $this->response->errorUnauthorized('参数错误，未获取用户信息');
        }

        switch ($type) {
            case 'weixin':
                $user = User::where('weixin_openid', $oauthUser->offsetGet('openid'))->first();
                // 没有用户，默认创建一个用户
                if (!$user) {
                    $user = User::create([
                        'name' => $oauthUser->getNickname(),
                        'avatar' => $oauthUser->getAvatar(),
                        'weixin_openid' => $oauthUser->getId(),
                    ]);
                }
                break;
        }
        $token = Auth::guard('api')->fromUser($user);
        return $this->respondWithToken($token);
    }



    protected function respondWithToken($token)
    {
        return $this->response->array([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60
        ]);
    }
}
