<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exceptions;
use Exception;

class TransferController extends Controller
{
    function store() {
        $duitkuConfig = new \Duitku\Config(env("DUITKU_API_KEY"), env("DUITKU_MERCHANT_KEY"));
        // false for production mode
        // true for sandbox mode
        $duitkuConfig->setSandboxMode(true);
        // set sanitizer (default : true)
        $duitkuConfig->setSanitizedMode(true);
        // set log parameter (default : true)
        $duitkuConfig->setDuitkuLogs(true);

                // $paymentMethod      = ""; // PaymentMethod list => https://docs.duitku.com/pop/id/#payment-method
        $paymentAmount      = 10000; // Amount
        $email              = "customer@gmail.com"; // your customer email
        $phoneNumber        = "081234567890"; // your customer phone number (optional)
        $productDetails     = "Test Payment";
        $merchantOrderId    = time(); // from merchant, unique   
        $additionalParam    = ''; // optional
        $merchantUserInfo   = ''; // optional
        $customerVaName     = 'John Doe'; // display name on bank confirmation display
        $callbackUrl        = 'http://YOUR_SERVER/callback'; // url for callback
        $returnUrl          = 'http://YOUR_SERVER/return'; // url for redirect
        $expiryPeriod       = 60; // set the expired time in minutes

        // Customer Detail
        $firstName          = "John";
        $lastName           = "Doe";

        // Address
        $alamat             = "Jl. Kembangan Raya";
        $city               = "Jakarta";
        $postalCode         = "11530";
        $countryCode        = "ID";

        $address = array(
            'firstName'     => $firstName,
            'lastName'      => $lastName,
            'address'       => $alamat,
            'city'          => $city,
            'postalCode'    => $postalCode,
            'phone'         => $phoneNumber,
            'countryCode'   => $countryCode
        );

        $customerDetail = array(
            'firstName'         => $firstName,
            'lastName'          => $lastName,
            'email'             => $email,
            'phoneNumber'       => $phoneNumber,
            'billingAddress'    => $address,
            'shippingAddress'   => $address
        );

        // Item Details
        $item1 = array(
            'name'      => $productDetails,
            'price'     => $paymentAmount,
            'quantity'  => 1
        );

        $itemDetails = array(
            $item1
        );

        $params = array(
            'paymentAmount'     => $paymentAmount,
            'merchantOrderId'   => $merchantOrderId,
            'productDetails'    => $productDetails,
            'additionalParam'   => $additionalParam,
            'merchantUserInfo'  => $merchantUserInfo,
            'customerVaName'    => $customerVaName,
            'email'             => $email,
            'phoneNumber'       => $phoneNumber,
            'itemDetails'       => $itemDetails,
            'customerDetail'    => $customerDetail,
            'callbackUrl'       => $callbackUrl,
            'returnUrl'         => $returnUrl,
            'expiryPeriod'      => $expiryPeriod
        );

        try {
            // createInvoice Request
            $responseDuitkuPop = \Duitku\Pop::createInvoice($params, $duitkuConfig);

            header('Content-Type: application/json');
            $data = json_decode($responseDuitkuPop);
            return response()->json($data);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    function callback_detail() {
        try {
            // Pastikan Anda telah mengatur konfigurasi untuk Duitku POP di file .env atau dalam kode
            $duitkuConfig = new \Duitku\Config(env("DUITKU_API_KEY"), env("DUITKU_MERCHANT_KEY"));
            $duitkuConfig->setSandboxMode(true);

            $callback = \Duitku\Pop::callback($duitkuConfig);

            header('Content-Type: application/json');
            $notif = json_decode($callback);

            $deposit = Deposit::firstWhere("nomor", $notif->merchantOrderId);

            if ($notif->resultCode == "00") {
                $this->add_deposit($deposit->id);
                $deposit->update(["payment_status" => 2]);
                // Jika resultCode adalah "00" (berhasil), lakukan tindakan sukses di sini
                // Misalnya, update status transaksi atau lakukan aksi lainnya
            } else if ($notif->resultCode == "01") {
                $deposit->update(["payment_status" => 3]);
                // Jika resultCode adalah "01" (gagal), lakukan tindakan gagal di sini
                // Misalnya, batalkan transaksi atau lakukan aksi lainnya
            }

            // Balas dengan response JSON jika diperlukan
            return response()->json(['status' => $deposit]);
        } catch (Exception $e) {
            http_response_code(400);
            echo $e->getMessage();
        }
    }
}
