<?php

namespace App\Http\Requests;

use App\Models\QuickDropUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class QuickDropProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        $user = Auth::guard('quickdrop')->user();
        
        return [
            'name'  => ['required', 'string', 'max:255'],
            'email' => [
                'required', 
                'string', 
                'lowercase', 
                'email', 
                'max:255', 
                Rule::unique(QuickDropUser::class)->ignore($user ? $user->id : null)
            ],
        ];
    }
    
    /**
     * Get the QuickDrop user instance for the request.
     */
    public function user($guard = null)
    {
        return Auth::guard('quickdrop')->user();
    }
}