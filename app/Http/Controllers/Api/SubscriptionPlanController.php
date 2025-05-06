<?php

namespace App\Http\Controllers\Api;

use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Http\Responses\BaseResponse;
use App\Models\PackageSubscription;
use App\Models\User;
use Stripe\Stripe;
use App\Http\Services\StripeService;
use Exception;

class SubscriptionPlanController extends Controller
{
    public  $view,$currentVendor;
    public function __construct()
    {
        // Stripe::setApiKey(config('services.stripe.secret'));
        $this->currentVendor = auth()->user();

    }
    /**
     * Display a listing of the resource.
     */
    public function subscriptionPlane()
    {
        DB::beginTransaction();
        try {

            $subscriptionPlane = SubscriptionPlan::all();
            return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Subscription Plane List",$subscriptionPlane);
        } catch (\Exception $e) {
            return new BaseResponse(STATUS_CODE_NOTAUTHORISED, STATUS_CODE_NOTAUTHORISED, $e->getMessage());
        }
    }
    public function addSubscription(Request $request)
    {

        DB::beginTransaction();
        $id =  $request->id;
        $user = User::find(auth('api')->user()->id);
        // if(auth()->user()->is_subscribe)
        // {
        //     return new BaseResponse(STATUS_CODE_FORBIDDEN, STATUS_CODE_FORBIDDEN, 'You already Subsribe This Plan',$user);
        // }

        $packages = SubscriptionPlan::where('id', $id)->first();

        // if(!auth()->user()->stripe_token)
        // {
        //     return new BaseResponse(STATUS_CODE_NOTFOUND, STATUS_CODE_NOTFOUND, 'Please Add Card First');

        // }
        try {
            // $strip = new StripeService();

            // $isAmmountCharge = $strip::chargeIntentAmount($packages->amount ?? 500,auth()->user()->stripe_token,$request->card_id, "subscription buy successfullly.");

            // if ($isAmmountCharge?->id) {
            
                PackageSubscription::firstOrCreate([
                    'subscription_plan_id' => $id ?? 1,
                    'user_id' => auth()->user()->id,
                    'package_date' => now(),
                    'end_date' => now()->addMonth(),
                    'is_expire' => 0
                ]);
                $user->syncRoles([$packages->title]);

                $user->is_subscribe = 1;
                $user->subscription_id = $id;
                  $user->save();
                  $user['permissions'] = $user->getPermissionsViaRoles()->pluck('name');
              
                DB::commit();
                return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "subscription buy successfullly",$user);
            // }
            return new BaseResponse(STATUS_CODE_FORBIDDEN, STATUS_CODE_FORBIDDEN, 'Something went wrong');

        } catch (Exception $e) {
            DB::rollBack();
            return new BaseResponse(STATUS_CODE_FORBIDDEN, STATUS_CODE_FORBIDDEN, $e->getMessage());
        }
    }

    public function getPlane(Request $request)
    {
        $SubscriptionPlan =  SubscriptionPlan::find($request->id);
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "subscription Plane",$SubscriptionPlan);
    }

    public function cancelSubscription(Request $request)
    {
        $subscription =  PackageSubscription::where('user_id',auth()->user()->id)->where('is_expire',0)->first();
        $subscription->is_expire =1;
        $subscription->save();
        $user = auth()->user();
        $user->is_subscribed = 0;
        $user->save();
       return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Subscription Cancel successfullly");
    }

    public function updateSubscription(Request $request)
    {
        DB::beginTransaction();
        $id =  $request->id;
        $user = User::find(auth('api')->user()->id);
        // if(auth()->user()->is_subscribe)
        // {
        //     return new BaseResponse(STATUS_CODE_FORBIDDEN, STATUS_CODE_FORBIDDEN, 'You already Subsribe This Plan',$user);
        // }

        $packages = SubscriptionPlan::where('id', $id)->first();

        // if(!auth()->user()->stripe_token)
        // {
        //     return new BaseResponse(STATUS_CODE_NOTFOUND, STATUS_CODE_NOTFOUND, 'Please Add Card First');

        // }
        try {
            // $strip = new StripeService();

            // $isAmmountCharge = $strip::chargeIntentAmount($packages->amount ?? 500,auth()->user()->stripe_token,$request->card_id, "subscription buy successfullly.");

            // if ($isAmmountCharge?->id) {
            
                PackageSubscription::firstOrCreate([
                    'subscription_plan_id' => $id ?? 1,
                    'user_id' => auth()->user()->id,
                    'package_date' => now(),
                    'end_date' => now()->addMonth(),
                    'is_expire' => 0
                ]);
                $user->syncRoles([$packages->title]);

                $user->is_subscribe = 1;
                $user->subscription_id = $id;
                  $user->save();
                  $user['permissions'] = $user->getPermissionsViaRoles()->pluck('name');
              
                DB::commit();
                return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "subscription buy successfullly",$user);
            // }
            return new BaseResponse(STATUS_CODE_FORBIDDEN, STATUS_CODE_FORBIDDEN, 'Something went wrong');

        } catch (Exception $e) {
            DB::rollBack();
            return new BaseResponse(STATUS_CODE_FORBIDDEN, STATUS_CODE_FORBIDDEN, $e->getMessage());
        }
    }
}
