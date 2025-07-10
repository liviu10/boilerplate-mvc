<?php
declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class UsersSeeder extends AbstractSeed
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * https://book.cakephp.org/phinx/0/en/seeding.html
     */
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        $data = [
            [
                'name' => 'Administrator',
                'email'=> 'admin@localhost.com',
                'password' => password_hash('123@Admin', PASSWORD_DEFAULT),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        $this->table('users')->insert($data)->saveData();
    }
}
