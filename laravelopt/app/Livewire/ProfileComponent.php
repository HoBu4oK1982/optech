<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class ProfileComponent extends Component
{
    public $name;
    public $email;
    public $password;
    public $password_confirmation;

    protected $rules = [
        'name' => 'required',
        'password' => 'confirmed',
    ];

    protected $messages = [
        'password' => 'Пароли не совпадают!',
    ];

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    public function mount(){
        $user = User::where('id', Auth::user()->id)->first();
        $this->name = $user->name;
        $this->email = $user->email;
    }

    public function updateUser() {
        $user = User::where('id', Auth::user()->id)->first();
        $user->name = $this->name;
        if($this->password != NULL){
            $user->forceFill([
                'password' => Hash::make($this->password),
            ]);
        }
        $validatedData = $this->validate();
        $user->save($validatedData);
        return to_route('dashboard');        
    }

    public function render()
    {
        $admin = User::where('id', Auth::user()->id)->first();
        return view('livewire.profile-component', [
            'admin' => $admin,
        ])->layout('layouts.main');
    }
}
