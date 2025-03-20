<?php

use \Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

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

if (! function_exists('storeBase64Image')) {
    function storeBase64Image($request)
    {
        
        $image = $request; // Get the Base64 string
        $image = str_replace('data:image/png;base64,', '', $image); // Remove metadata (if exists)
        $image = str_replace(' ', '+', $image);
        
        $imageName = time() . '.png'; // Generate unique file name
        $imagePath = 'uploads/' . $imageName;
    
        Storage::disk('public')->put($imagePath, base64_decode($image)); // Save to storage/app/public/uploads
    
        return $imagePath;
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

