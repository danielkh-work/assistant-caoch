<?php

use \Illuminate\Support\Facades\File;
// use Twilio\Rest\Client;

if (! function_exists('uploadImage')) {
    function uploadImage($file, $path, $oldFilePath = null)
    {
        $prefixFolder = 'uploads';
        if (File::exists(public_path($oldFilePath))) {
            File::delete(public_path($oldFilePath));
        }
        $filename = md5(time() . rand(1000, 9999)) . '.' . $file->extension();

        // File upload location
        $location = $prefixFolder . '/' . $path;

        // Upload file
        $file->move($location, $filename);
        return $prefixFolder . '/' . $path . '/' . $filename;
    }
}


// if (! function_exists('sendSMS')) {
//     function sendSMS($to, $message)
//     {
//         $client = new Client(
//             config('services.twilio.twilio_account_sid'),
//             config('services.twilio.twilio_auth_token')
//         );

//         return $client->messages->create(
//             $to,
//             [
//                 'from' => config('services.twilio.twilio_number'),
//                 'body' => $message
//             ]
//         );
//     }
// }

