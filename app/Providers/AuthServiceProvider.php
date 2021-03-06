<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [

        // 注册回复策略
		 \App\Models\Reply::class => \App\Policies\ReplyPolicy::class,

        //注册主题策略
		 \App\Models\Topic::class => \App\Policies\TopicPolicy::class,

        //注册用户策略
        \App\Models\User::class  => \App\Policies\UserPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {

        $this->registerPolicies();


        Passport::routes();

        Passport::tokensExpireIn(Carbon::now()->addDays(15));
        Passport::refreshTokensExpireIn(Carbon::now()->addDays(30));

        //设置站站权限
        \Horizon::auth(function($request){
            return \Auth::user()->hasRole('Founder');
        });

    }
}
