<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function get_suntime_info($latitude, $longitude)
    {
        $sun_times=[];
        for ($i=0; $i < 7; $i++) { 
            $date=date('Y-m-d', time() + ($i * 24 * 60 * 60));

            $sun_time_format = 'https://api.sunrise-sunset.org/json?lat=%s&lng=%s&date=%s';

            $sun_response = Http::get(sprintf($sun_time_format,$latitude,$longitude,$date));
            
            $sun_obj=json_decode($sun_response->body(),true);

            $sunset=$sun_obj['results']['sunset'];
            $sunrise=$sun_obj['results']['sunrise'];
            $sun_times[]=['date'=>$date,'sunset'=>$sunset,'sunrise'=>$sunrise];            
        }
        return $sun_times;
    }

    public function index()    {

        $location = request('location');
        $context=[];
        
        if(isset($location)){
            #Get the api keys needed
            $google_api = env('GOOGLE_API','');
            $google_map_api = env('GOOGLE_MAP_API','');


            $google_geocode = 'https://maps.googleapis.com/maps/api/geocode/json?address=%s&key=%s';
            $location_response = Http::get(sprintf($google_geocode,$location,$google_api));

            $location_obj=json_decode($location_response->body(),true);
            $locations=$location_obj['results'];


            #If more than 1 or no result is found, use a list of locations, otherwise use the 1 location
            if(count($locations)!=1){
                $locations_formated=[];
                foreach ($locations as $location) {
                    $locations_formated[]=$location['formatted_address'];
                }
                $context['locations']=$locations_formated;
            }
            else{
                $context['location']=$locations[0]['formatted_address'];
                $latitude=$locations[0]['geometry']['location']['lat'];
                $longitude=$locations[0]['geometry']['location']['lng'];

                $context['sun_times']=$this->get_suntime_info($latitude,$longitude);
                
                // $unix_time=mktime(11, 14, 54, 8, 12, 2014);
                $google_map = 'https://maps.googleapis.com/maps/api/staticmap?zoom=13&size=300x300&key=%s&markers=%s,%s&maptype=roadmap';
                
                $context['image_link'] = sprintf($google_map,$google_map_api,$latitude,$longitude);
            }
        }  
        
        return view('index',$context);
    }
}
