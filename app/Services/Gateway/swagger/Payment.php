<?php

namespace App\Services\Gateway\swagger;

use App\Models\Fund;
use Facades\App\Services\BasicCurl;
use Facades\App\Services\BasicService;

class Payment
{
    public static function prepareData($order, $gateway)
    {
        if ($order->type == 'wallet') {
            $success_route = route('wallet');
        } else {
            $appointment_id = $order->appointment->id;
            $appointment_type = $order->appointment->appointment_type->type;
            if ($order->appointment->doctor_id) {
                $user_name = $order->appointment->doctor->user_name;
                $success_route = '/doctor/profile/' . $user_name . '/book_appointment?type=' . $appointment_type . '&paymentSuccess=true&appointmentId=' . $appointment_id;
            } elseif ($order->appointment->clinic_id) {
                $user_name = $order->appointment->clinic->user_name;
                $success_route = '/clinic/profile/' . $user_name . '/book_appointment?type=' . $appointment_type . '&paymentSuccess=true&appointmentId=' . $appointment_id;
            }
        }
        $val['account'] =  $gateway->parameters->MAGUA_PAY_ACCOUNT;
        $val['order_id'] = $order->transaction;
        $val['amount'] = (int)round($order->final_amount);
        $val['currency'] = $order->gateway_currency;
        $val['recurrent'] = false;
        $val['purpose'] = "Online Payment";
        $val['patient_first_name'] = str_replace('.', '', optional($order->user)->firstname) ?? "John";
        $val['patient_last_name'] = optional($order->user)->lastname ?? "Doe";
        $val['patient_address'] = optional($order->user)->address ?? "10 Downing Street";
        $val['patient_city'] = optional($order->user)->city ?? "London";
        $val['patient_zip_code'] = optional($order->user)->zip_code ?? "121165";
        $val['patient_country'] = "GB";
        $val['patient_phone'] = optional($order->user)->phone ?? "+79000000000";
        $val['patient_email'] = optional($order->user)->email ?? "johndoe@mail.com";

        $val['patient_ip_address'] = request()->ip();
        $val['merchant_site'] = url('/');

        $val['success_url'] = $success_route;
        $val['fail_url'] = route('failed');
        $val['callback_url'] = route('ipn', $gateway->code);
        $val['status_url'] = route('ipn', $gateway->code);

        $url = "https://api-gateway.magua-pay.com/initPayment";
        $header = array();
        $header[] = 'Content-Type: application/json';
        $header[] = 'Authorization: Basic ' . base64_encode($gateway->parameters->MerchantKey . ":" . $gateway->parameters->Secret);

        $response = BasicCurl::curlPostRequestWithHeaders($url, $header, $val);

        $response = json_decode($response);
        if (isset($response->form_url)) {
            $send['redirect'] = true;
            $send['redirect_url'] = $response->form_url;
        } else {
            $send['error'] = true;
            $send['message'] = "Invalid Request";
        }
        return json_encode($send);
    }

    public static function ipn($request, $gateway, $order = null, $trx = null, $type = null)
    {
        $order = Fund::where('transaction', $request->orderId)->orderBy('id', 'DESC')->first();
        if ($order) {
            if ($request->status == 2 && $request->currency == $order->gateway_currency && ($request->amount == (int)round($order->final_amount)) && $order->status == 0) {
                BasicService::preparePaymentUpgradation($order);
            }
        }
    }
}
