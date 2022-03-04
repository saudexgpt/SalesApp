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
        $str =  $parent_string . '~' . $child_string;
    }


    $string_array = array_unique(explode('~', $str));

    return $new_parent_str = implode('~', $string_array);
}
