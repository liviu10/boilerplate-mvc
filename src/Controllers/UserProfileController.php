<?php

namespace LiviuVoica\BoilerplateMVC\Controllers;

use LiviuVoica\BoilerplateMVC\Core\Validation;
use LiviuVoica\BoilerplateMVC\Models\User;

class UserProfileController
{
    private User $user;

    public function __construct()
    {
        $this->user = new User();
    }

    public function show(string $id): array
    {
        $data = [
            'title' => 'Show ' . $this->user->getTable(),
        ];

        if (!$id) {
            $data['results'] = [];
            $data['message'] = "You need to provide the user's ID in order to see its details.";

            return $data;
        }

        $query = $this->user->fetch((int)$id);

        if ($query === null) {
            $data['results'] = [];
            $data['message'] = "User with ID {$id} was not found.";
        } else {
            $data['result'] = $query;
        }

        return $data;
    }

    public function update(string $id, array $payload): array
    {
        $data = [
            'title' => 'Update ' . $this->user->getTable(),
        ];

        if (!$id) {
            $data['results'] = [];
            $data['message'] = "You need to provide the user's ID in order to update its details.";

            return $data;
        }

        $rules = [
            'name' => ['sometimes', 'string', 'min:5', 'max:255', 'regex:/^[a-zA-Z\s-]+$/'],
            'email' => ['sometimes', 'string', 'min:5', 'max:255'],
        ];
        $validate = (new Validation())->validate($rules, $payload);
        if (!empty($validate)) {
            $data['results'] = [];
            $data['message'] = $validate;

            return $data;
        }

        $query = $this->user->update((int)$id, $data);

        if (!$query) {
            $data['results'] = [];
            $data['message'] = "User with ID {$id} was not found.";
        } else {
            $data['results'] = $query;
        }

        return $data;
    }
}
