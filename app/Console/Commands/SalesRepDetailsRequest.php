<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class SalesRepDetailsRequest extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'request:sales-rep-details-request';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command makes request for sales rep details from warehouse';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }
    public function fetchWarehouseRepDetails()
    {
        // Create a stream
        set_time_limit(0);
        // $parameters = [
        //     "rep" => $user_id
        // ];

        // $params =  http_build_query($parameters);

        $opts = array(
            'http' => [
                'method'  => 'POST',
                'header'  => 'Content-type: application/x-www-form-urlencoded',
                // 'content' => $params
            ]
        );

        // DOCS: https://www.php.net/manual/en/function.stream-context-create.php
        $context = stream_context_create($opts);

        // Open the file using the HTTP headers set above
        // DOCS: https://www.php.net/manual/en/function.file-get-contents.php
        // $customers =  file_get_contents('http://localhost:8001/api/fetch-reps-details', false, $context);
        $customers =  file_get_contents('https://gpl.3coretechnology.com/api/fetch-reps-details', false, $context);
        // $products =  file_get_contents('http://localhost:8001/api/rep-stock', false, $context);
        $customers_in_json =  json_decode($customers);
        $reps = $customers_in_json->reps;
        $this->storeReps($reps);
    }
    private function storeReps($reps)
    {
        //
        foreach ($reps as $rep) {
            $id = $rep->id;
            $repUser = $rep->user;
            $email = $repUser->email;
            $name_array = explode(' ', $repUser->name);
            $last_name = $name_array[0];
            $first_name = $name_array[1];
            $user = User::where('email', $email)->first();
            if (!$user) {
                $user = new User();
                $user->password = bcrypt('password');
                $user->password_status = 'default';
                $user->rep_ids = addSingleElementToString('', $id);
            }
            $user->rep_ids = addSingleElementToString($user->rep_ids, $id);
            $user->first_name = $first_name;
            $user->last_name = $last_name;
            $user->name = $repUser->name;
            $user->username = $repUser->email;
            $user->email = $repUser->email;
            $user->phone = $repUser->phone;
            $user->user_type = 'staff';

            if ($user->save()) {
                // $role = Role::where('name', $request->role)->first();
                $user->syncRoles(['sales_rep']);
                $user->flushCache();
            }
        }
    }
    public function handle()
    {
        //
        $this->fetchWarehouseRepDetails();
    }
}
