<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

class LocationController extends Controller
{   
    public function get_suntime_info($latitude, $longitude, $date)
    {
        #Format for the api to find sunset and sunrise
        $sun_time_format = 'https://api.sunrise-sunset.org/json?lat=%s&lng=%s&date=%s';

        #Gets a response from the api, using latitude, longitude, and date
        $sun_response = Http::get(sprintf($sun_time_format,$latitude,$longitude,$date));
            
        #Making an object, a list with indexes, based on the response body
        $sun_obj=json_decode($sun_response->body(),true);

        #Gets sunset and sunrise from the object and returns the date, sunset time, and sunrise, both times in UTC
        $sunset=$sun_obj['results']['sunset'];
        $sunrise=$sun_obj['results']['sunrise'];
        return ['date'=>$date,'sunset'=>$sunset,'sunrise'=>$sunrise];            
    }

    public function get_suntimes_info($latitude, $longitude)
    {
        $sun_times=[];
        for ($i=0; $i < 7; $i++) { 
            #Gets the date from today to 7 days ahead
            $date=date('Y-m-d', time() + ($i * 24 * 60 * 60));
            $sun_times[]=$this->get_suntime_info($latitude, $longitude, $date);       
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

            #Creates a format to use the geocode api
            $google_geocode = 'https://maps.googleapis.com/maps/api/geocode/json?address=%s&key=%s';
            #Uses the format, with the location from the input and the api, to make a HTTP GET call
            $location_response = Http::get(sprintf($google_geocode,$location,$google_api));

            #Creating an object, a list with indexes from the resulting json body and inserting the results to a variable
            $location_obj=json_decode($location_response->body(),true);
            $locations=$location_obj['results'];


            #If more than 1 or no result is found, use a list of locations, otherwise use the 1 location
            if(count($locations)!=1){
                #Inserting each of the formated addresses into a list, inputting it to the context, to use on the index blade.
                $locations_formated=[];

                foreach ($locations as $location) {
                    $locations_formated[]=$location['formatted_address'];
                }
                $context['locations']=$locations_formated;
            }
            else{
                #Take the single location and find it's address, latitude, and longitude                
                $context['location']=$locations[0]['formatted_address'];
                $latitude=$locations[0]['geometry']['location']['lat'];
                $longitude=$locations[0]['geometry']['location']['lng'];

                $date = request('date');

                if(isset($date)){
                    #If date is set, only find that date
                    $context['sun_times']=[$this->get_suntime_info($latitude,$longitude,$date)];
                }
                else{
                    #Get this weeks sun times, using the latitude and longitude, and inserts into the context
                    $context['sun_times']=$this->get_suntimes_info($latitude,$longitude);
                }

                #The format for google map images 
                $google_map = 'https://maps.googleapis.com/maps/api/staticmap?zoom=13&size=300x300&key=%s&markers=%s,%s&maptype=roadmap';
                #Using the api, latitude and longitude to get a map image
                $context['image_link'] = sprintf($google_map,$google_map_api,$latitude,$longitude);
            }
        }  
        
        return view('index',$context);
    }
}
