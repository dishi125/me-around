<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Wedding;
use App\Models\WeddingMetaData;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Lang;
use Carbon\Carbon;

class WeddingController extends Controller
{
    public function index(Request $request)
    {
        try{
            $weddingData = Wedding::join('wedding_meta_data','wedding_meta_data.wedding_id', 'weddings.id')
                ->where(function($q){
                    $q->where('wedding_meta_data.meta_key', "wedding_date")
                    ->whereDate('wedding_meta_data.meta_value', '>=', Carbon::now());
                })
                ->select("weddings.*")
                ->with('weddingMeta:wedding_id,meta_key,meta_value')
                ->groupBy('weddings.id')
                ->paginate(config('constant.pagination_count'), "*", "wedding_page");

            $weddingData = $this->weddingFilter($weddingData);
            return $this->sendSuccessResponse(Lang::get('messages.wedding.get'), 200, $weddingData);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function weddingFilter($data)
    {
        $paginateData = $data->toArray();
        $filteredData = [];
        foreach ($paginateData['data'] as $key => $wedding) {
            $metaData = collect($wedding['wedding_meta'])->mapWithKeys(function ($value) {
                    return [$value['meta_key'] => $value['meta_value']];
                })->toArray();            
            $wedding = array_merge($wedding,$metaData);

            unset($wedding['wedding_meta']);

            $filteredData[] = $wedding;
        }
        $paginateData['data'] = array_values($filteredData);
        return $paginateData;
    }

    public function weddingDetail(Request $request,$id)
    {
        try{
            $weddingData = Wedding::find($id);
            if($weddingData){
                $metaData = WeddingMetaData::where('wedding_id',$id)->select('meta_value','meta_key')->get();
                $metaData = collect($metaData)->mapWithKeys(function ($value) {
                    return [$value->meta_key => $value->meta_value];
                })->toArray();
                $weddingData = array_merge($weddingData->toArray(),$metaData);
            }

            return $this->sendSuccessResponse(Lang::get('messages.wedding.get'), 200, $weddingData);
        }catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
}
