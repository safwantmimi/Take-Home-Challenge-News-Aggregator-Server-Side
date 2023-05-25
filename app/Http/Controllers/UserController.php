<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Preference;

class UserController extends Controller
{
    public function update(Request $request)
    {

        
        $user = Auth::user();

        // Validate the input data
        $validator = $this->updateValidator($request->all());
        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 400);
        }

        // Update the user data
        $user->name = $request->input('name');
        $user->image = $request->input('image');
        $user->save();

        // Update preferences if provided
        $preferenceData = $request->input('preferences');
        if ($preferenceData) {
            if ($user->preferences) {
                // If the user already has a preference, update it
                $preference = $user->preferences;
                $preference->source_id = json_encode($preferenceData['source_id']);
                $preference->category_id = json_encode($preferenceData['category_id']);
                $preference->author_id = json_encode($preferenceData['author_id']);
                $preference->save();
            } else {
                // If the user does not have a preference, create a new one
                $preference = new Preference();
                $preference->user_id = $preferenceData['user_id'];
                $preference->source_id = json_encode($preferenceData['source_id']);
                $preference->category_id = json_encode($preferenceData['category_id']);
                $preference->author_id = json_encode($preferenceData['author_id']);
                $user->preferences()->save($preference);
            }
        }

        


        return response()->json(['message' => 'Profile updated successfully', 'user' => $user], 200);
    }

    protected function updateValidator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'string'],
            'preference.source_id' => ['nullable', 'integer'],
            'preference.category_id' => ['nullable', 'integer'],
            'preference.author_id' => ['nullable', 'integer'],
        ]);
    }

}
