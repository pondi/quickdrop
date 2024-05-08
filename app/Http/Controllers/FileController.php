<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\UploadRequest;
use App\Models\UploadObject;

class FileController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:zip,rar,pdf,doc,docx',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->getMessages();
            $formattedErrors = [];
            foreach ($errors as $field => $messages) {
                $formattedErrors[$field] = $messages;
            }
            return response()->json(['errors' => $formattedErrors], 400);
        }

        $file = $request->file('file');

        // Generate a random GUID for the filename
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

        // Store the file in local storage
        $file->storeAs('uploads', $filename);

        // Return the filename of the stored file
        return response($file->getClientOriginalName(), 200)->header('Content-Type', 'text/plain');
    }

    public function upload(Request $request, $unique_request_id)
    {
    $request->validate([
        'file' => 'required|mimes:zip,rar,pdf,doc,docx',
    ]);

    $file = $request->file('file');

    // Generate a unique ID for the filename
    $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

    // Store the file in local storage
    $path = $file->storeAs('uploads', $filename);

    // Get the upload request
    $uploadRequest = UploadRequest::where('unique_request_id', $unique_request_id)->firstOrFail();

    // Create a new upload object
    $uploadObject = new UploadObject;
    $uploadObject->owner_id = auth()->id();
    $uploadObject->original_name = $file->getClientOriginalName();
    $uploadObject->stored_name = $filename;
    $uploadObject->storage_path = $path;
    $uploadObject->mime_type = $file->getClientMimeType();
    $uploadObject->unique_id = Str::uuid();
    $uploadObject->file_size = $file->getSize();
    $uploadObject->file_extension = $file->getClientOriginalExtension();
    $uploadObject->file_hash = hash_file('sha256', $file->getRealPath());
    $uploadObject->save();

    // Associate the upload object with the upload request
    $uploadRequest->uploadObjects()->attach($uploadObject->id);

    return response()->json(['message' => 'File uploaded successfully'], 200);
    }
}
