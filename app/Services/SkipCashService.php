<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class SkipCashService
{


    public function createPayment(
        float $amount,
        string $reference,
        $customer
    ) {
        $payload = [

            "uid" =>
            $reference,

            "keyId" =>
            config('services.skipcash.key_id'),

            "amount" =>
            number_format($amount, 2, '.', ''),

            "firstName" =>
            $customer->name ?? '',

            "lastName" =>
            $customer->name ?? '',

            "phone" => $customer->phone,
            "email" =>'info@stonecafe.qa',
            "street"=>'Doha',
            "city" =>'Thumama',
            'country'=>'QA',
            "postalCode"=>"000",
            "transactionId"=>$reference,
        ];

        $signatureString =
            "Uid=".$payload['uid'].
            ",KeyId=".$payload['keyId'].
            ",Amount=".$payload['amount'].
            ",FirstName=".$payload['firstName'].
            ",LastName=".$payload['lastName'].
            ",Phone=".$payload['phone'].
            ",Email=".$payload['email'].
            ",Street=".$payload['street'].
            ",City=".$payload['city'].
            ",Country=".$payload['country'].
            ",PostalCode=".$payload['postalCode'].
            ",TransactionId=".$payload['transactionId'];


        Log::debug('SkipCash signature string', [
            'string' => $signatureString,
            'key' => config('services.skipcash.key_id'),
        ]);



        $hash =
            hash_hmac(
                'sha256',
                $signatureString,
                config('services.skipcash.secret'),
                true
            );


        $signature =
            base64_encode($hash);


        $response =
            Http::withHeaders([

                "Authorization" =>
                    $signature,

                "Content-Type" =>
                    "application/json"

            ])
            ->post(
                config('services.skipcash.base_url')
                . "/api/v1/payments",
                $payload
            );


            Log::debug('SkipCash Response', [
                'status' => $response->status(),
                'body' => $response->body(),
                'json' => $response->json(),
            ]);
        if (!$response->successful()) {
            Log::error(
                'SkipCash Error',
                [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]
            );
            throw new \Exception(
                "SkipCash payment failed"
            );
        }
        return $response->json()['resultObj'];
    }
}
