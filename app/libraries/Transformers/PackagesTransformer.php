<?php
/**
 * Created by PhpStorm.
 * User: Javed
 * Date: 7/10/15
 * Time: 7:23 PM
 */
namespace App\libraries\Transformers;
use App\PackagesModel;
use App\Addon;
use App\libraries\Transformers\AddonTransformer;
use Dingo\Api\Http\Request;
use Illuminate\Support\Facades\Input;
use League\Fractal\TransformerAbstract;

class PackagesTransformer extends TransformerAbstract{
    protected $defaultIncludes = ['Addons','Bonus','seller','category','tags'];
    public function transform(PackagesModel $package){
        return [
            'id'=>$package->id,
            'name'=>$package->candybrush_packages_name,
            'description'=>$package->candybrush_packages_description,
         /*   'sub_category'=>$package->candybrush_packages_sub_category,*/
            'price'=>(integer)$package->candybrush_packages_price,
            'deal_price'=>(integer)$package->candybrush_packages_deal_price,
            'available_date'=>$package->candybrush_packages_available_date,
            'term_condition'=>$package->candybrush_packages_term_condition,
            'payment_type'=>$package->candybrush_packages_payment_type,
            'maximum_delivery_days'=>$package->candybrush_packages_maximum_delivery_days
             ];
    }
    public function requestAdapter()
    {
        return [
            PackagesModel::NAME => Input::get('name',''),
            PackagesModel::DESCRIPTION => Input::get('description',''),
            PackagesModel::CATEGORY_ID => Input::get('category_id',''),
            PackagesModel::TAG_ID=>Input::get('tags_id',''),
            /*PackagesModel::SUB_CATEGORY => Input::get('sub_category',''),*/
            PackagesModel::PRICE => Input::get('price',''),
            PackagesModel::DEAL_PRICE => Input::get('deal_price',''),
            PackagesModel::AVAILABLE_DATE => Input::get('available_date',''),
            PackagesModel::TERM_CONDITION => Input::get('term_condition',''),
            PackagesModel::PAYMENT_TYPE => Input::get('payment_type',''),
            PackagesModel::MAXIMUM_DELIVERY_DAYS => Input::get('maximum_delivery_days',''),
            PackagesModel::User_ID=>Input::get('user_id',''),
            PackagesModel::STATUS=>Input::get('status','')
        ];
    }

    /**
     * for including Addons
     * @param PackagesModel $package
     * @return \League\Fractal\Resource\Collection
     */
    public function includeAddons(PackagesModel $package){
        return $this->collection($package->addons()->get(),new AddonTransformer());
    }
    
    public function includeBonus(PackagesModel $package){
        return $this->collection($package->bonus()->get(),new BonusTransformer());
    }

    public function includeSeller(PackagesModel $package){
        return $this->item($package->seller()->first(),new UserTransformer());
    }

    public function includeCategory(PackagesModel $package){
        return $this->item($package->category()->first(),new CategoryTransformer());
    }
    public function includeTags(PackagesModel $package){
        return $this->collection($package->tags()->get(),new TagTransformer());
    }
}