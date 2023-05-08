<?php
function workingDays($day = null)
{
    $arr = [
        '1' => 'Monday',
        '2' => 'Tuesday',
        '3' => 'Wednesday',
        '4' => 'Thursday',
        '5' => 'Friday'

    ];

    if ($day) {
        return $arr[$day];
    }
    return $arr;
}

function workingDaysStr($day = null)
{
    $arr = [
        'Monday'    => '1',
        'Tuesday'   => '2',
        'Wednesday' => '3',
        'Thursday'  => '4',
        'Friday'    => '5'

    ];

    if ($day) {
        return $arr[$day];
    }
    return $arr;
}

function getDateFormatWords($dateTime)
{
    return date('M d, Y', strtotime($dateTime));
}

function todayDate()
{
    return date('Y-m-d', strtotime('now'));
}

function addSingleElementToString($parent_string, $child_string)
{
    if ($parent_string == '') {
        $str =  $child_string;
    } else {
        $string_array = explode(',', $parent_string);
        if (!in_array($child_string, $string_array)) {
            $string_array[] = $child_string;
        }
        $str = implode(',', $string_array);
    }


    return $str;
}
function deleteSingleElementFromString($parent_string, $child_string)
{
    $string_array = explode(',', $parent_string);

    $count_array = count($string_array);

    for ($i = 0; $i < ($count_array); $i++) {

        if ($string_array[$i] == $child_string) {

            unset($string_array[$i]);
        }
    }
    return implode(',', array_unique($string_array));
}
function numbersArrayFromRange($lower_limit, $upper_limit)
{
    $number_array = [];
    for ($i = $lower_limit; $i <= $upper_limit; $i++) {

        $number_array[] = sprintf('%07d', $i);
    }
    return $number_array;
}

function mileToMetre($mile)
{
    return $mile * 1609.34;
}
/**
 * Calculates the great-circle distance between two points, with
 * the Haversine formula.
 * @param float $latitudeFrom Latitude of start point in [deg decimal]
 * @param float $longitudeFrom Longitude of start point in [deg decimal]
 * @param float $latitudeTo Latitude of target point in [deg decimal]
 * @param float $longitudeTo Longitude of target point in [deg decimal]
 * @param float $earthRadius Mean earth radius in [m]
 * @return float Distance between points in [m] (same as earthRadius)
 */
function haversineGreatCircleDistanceBetweenTwoPoints(
    $latitudeFrom,
    $longitudeFrom,
    $latitudeTo,
    $longitudeTo
) {

    $earthRadius = 3958.8; // in mile
    // 6371000  /*6378137*/ in meter
    // convert from degrees to radians
    $latFrom = deg2rad($latitudeFrom);
    $lonFrom = deg2rad($longitudeFrom);
    $latTo = deg2rad($latitudeTo);
    $lonTo = deg2rad($longitudeTo);

    $latDelta = $latTo - $latFrom;
    $lonDelta = $lonTo - $lonFrom;

    $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
        cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
    return $angle * $earthRadius;
}

/**
 * Calculates the great-circle distance between two points, with
 * the Vincenty formula.
 * @param float $latitudeFrom Latitude of start point in [deg decimal]
 * @param float $longitudeFrom Longitude of start point in [deg decimal]
 * @param float $latitudeTo Latitude of target point in [deg decimal]
 * @param float $longitudeTo Longitude of target point in [deg decimal]
 * @param float $earthRadius Mean earth radius in [m]
 * @return float Distance between points in [m] (same as earthRadius)
 */
function vincentyGreatCircleDistanceBetweenTwoPoints(
    $latitudeFrom,
    $longitudeFrom,
    $latitudeTo,
    $longitudeTo,
    $earthRadius = 6378137 /*6371000*/
) {
    // convert from degrees to radians
    $latFrom = deg2rad($latitudeFrom);
    $lonFrom = deg2rad($longitudeFrom);
    $latTo = deg2rad($latitudeTo);
    $lonTo = deg2rad($longitudeTo);

    $lonDelta = $lonTo - $lonFrom;
    $a = pow(cos($latTo) * sin($lonDelta), 2) +
        pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);
    $b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);

    $angle = atan2(sqrt($a), $b);
    return $angle * $earthRadius;
}



function getLocationFromLatLong($lat, $long)
{

    // echo urlencode($address);
    $apiKey = env('GOOGLE_API_KEY');
    $json = file_get_contents('https://maps.googleapis.com/maps/api/geocode/json?latlng=' . $lat . ',' . $long . '&key=' . $apiKey);

    $json = json_decode($json);
    // print_r($json);
    //return $json;
    $lat = $json->{'results'}[0]->{'geometry'}->{'location'}->{'lat'};
    $long = $json->{'results'}[0]->{'geometry'}->{'location'}->{'lng'};
    $formatted_address = $json->{'results'}[0]->{'formatted_address'};
    $street = '';
    $area = '';
    $address_components = $json->{'results'}[0]->{'address_components'};
    foreach ($address_components as $address_component) {
        $types = $address_component->types;
        foreach ($types as $key => $value) {
            if ($value === 'route') {
                $street = $address_component->long_name;
            }
            if ($value === 'administrative_area_level_2') {
                $area = $address_component->long_name;
            } else if ($value === 'locality') {
                $area = $address_component->long_name;
            }
        }
    }
    // $formatted_address = $json->{'results'}[0]->{'formatted_address'};
    // $street = $json->{'results'}[0]->{'address_components'}[1]->{'long_name'};
    // $area = $json->{'results'}[0]->{'address_components'}[4]->{'long_name'};
    return array($lat, $long, $formatted_address, $street, $area);
}
function getLocationFromAddress($address)
{
    $address = str_replace(',', '', $address);
    // echo urlencode($address);
    $apiKey = env('GOOGLE_API_KEY');
    $json = file_get_contents('https://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode($address) . '&key=' . $apiKey);

    $json = json_decode($json);
    // print_r($json);
    // return $json;
    $lat = $json->{'results'}[0]->{'geometry'}->{'location'}->{'lat'};
    $long = $json->{'results'}[0]->{'geometry'}->{'location'}->{'lng'};
    $formatted_address = $json->{'results'}[0]->{'formatted_address'};
    $street = '';
    $area = '';
    $address_components = $json->{'results'}[0]->{'address_components'};
    foreach ($address_components as $address_component) {
        $types = $address_component->types;
        foreach ($types as $key => $value) {
            if ($value === 'route') {
                $street = $address_component->long_name;
            }
            if ($value === 'administrative_area_level_2') {
                $area = $address_component->long_name;
            } else if ($value === 'locality') {
                $area = $address_component->long_name;
            }
        }
    }
    // $formatted_address = $json->{'results'}[0]->{'formatted_address'};
    // $street = $json->{'results'}[0]->{'address_components'}[1]->{'long_name'};
    // $area = $json->{'results'}[0]->{'address_components'}[4]->{'long_name'};
    return array($lat, $long, $formatted_address, $street, $area);
}

function setNextScheduleDate($current_date, $recurrence)
{
    if ($recurrence > 0) {

        $addition = '+1 week';
        if ($recurrence > 1) {
            $addition = '+' . $recurrence . ' weeks';
        }
        return  date('Y-m-d', strtotime($current_date . $addition));
    }
    return  date('Y-m-d', strtotime($current_date));
}
