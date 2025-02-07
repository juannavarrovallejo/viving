<?php

namespace App\Http\Controllers\Patients;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentMethods\StripeController;
use App\Http\Controllers\WalletController;

use App\Http\Requests\Patients\BookAppointmentRequest;
use App\Http\Resources\Web\AppointmentMedicineResource;
use App\Http\Resources\Web\BookAppointmentsResource;
use App\Http\Resources\Web\MedicinesResource;
use App\Http\Resources\Web\RecordMedicinesResource;
use App\Models\AppointmentMedicine;
use App\Models\AppointmentSchedule;
use App\Models\AppointmentScheduleSlot;
use App\Models\AppointmentStatus;
use App\Models\AppointmentType;
use App\Models\BankAccount;
use App\Models\BookAppointment;
use App\Models\FundBankTransfer;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laravel\Passport\Passport;

class BookAppointmentController extends Controller
{
    public function __construct()
    {
        // $this->middleware('auth');
        // $this->middleware('patient');
    }
    public function getter($req = null, $export = null)
    {

        $patient = auth()->user()->patient;
        if ($req != null) {
            $patient_appointments =  $patient->appointments()->withAll();
            if ($req->trash && $req->trash == 'with') {
                $patient_appointments =  $patient_appointments->withTrashed();
            }
            if ($req->trash && $req->trash == 'only') {
                $patient_appointments =  $patient_appointments->onlyTrashed();
            }
            if ($req->column && $req->column != null && $req->search != null) {
                $patient_appointments = $patient_appointments->whereLike($req->column, $req->search);
            } else if ($req->search && $req->search != null) {

                $patient_appointments = $patient_appointments->whereLike(['name', 'description'], $req->search);
            }

            if ($req->status_code) {
                $patient_appointments = $patient_appointments->where('appointment_status_code', $req->status_code);
            }

            if ($req->sort && $req->sort['field'] != null && $req->sort['type'] != null) {
                $patient_appointments = $patient_appointments->OrderBy($req->sort['field'], $req->sort['type']);
            } else {
                $patient_appointments = $patient_appointments->OrderBy('id', 'desc');
            }
            if ($export != null) { // for export do not paginate
                $patient_appointments = $patient_appointments->get();
                return $patient_appointments;
            }
            $totalPatientAppointments = $patient_appointments->count();
            $patient_appointments = $patient_appointments->paginate($req->perPage);
            $patient_appointments = BookAppointmentsResource::collection($patient_appointments)->response()->getData(true);

            return $patient_appointments;
        }
        $patient_appointments = BookAppointmentsResource::collection($patient->appointments()->withAll()->orderBy('id', 'desc')->paginate(10))->response()->getData(true);
        return $patient_appointments;
    }

    public function bookAppointment(BookAppointmentRequest $request)
    {
        $data = $request->all();
        $user = Auth()->user();
        $patient = $user->patient->id;
        $appointment_type = AppointmentType::where('id', $request->appointment_type_id)->first();
        if ($appointment_type->is_schedule_required) {
            $schedule_slot = AppointmentScheduleSlot::with('appointment_schedule')->where('id', $request->selected_schedule_id)->first();
            $data['start_time'] = $schedule_slot->start_time;
            $data['end_time'] = $schedule_slot->end_time;
            $data['fee'] = $schedule_slot->appointment_schedule->fee;
        } else {
            if (isset($request->doctor_id)) {
                $appointment_schedule = AppointmentSchedule::where('doctor_id', $request->doctor_id)->where('appointment_type_id', $request->appointment_type_id)->first();
            }
            if (isset($request->clinic_id)) {
                $appointment_schedule = AppointmentSchedule::where('clinic_id', $request->clinic_id)->where('appointment_type_id', $request->appointment_type_id)->first();
            }

            $data['start_time'] = null;
            $data['end_time'] = null;
            $data['fee'] = $appointment_schedule->fee;
        }

        $data['patient_id'] = $patient;
        $data['appointment_status_code'] = AppointmentStatus::$Pending;
        if ($request->hasFile('attachment')) {
            $data['attachment_url'] = uploadFile($request, 'attachment', 'booked_appointments');
        }
        $request->merge([
            'amount' => $data['fee']
        ]);
        if ($request->gateway == 'bank-transfer') {

            $fund_request = PaymentController::addFundRequest($request);

            $data['fund_id'] = $fund_request['fund']['id'] ?? null;
            // dd($fund_request['fund']);

            $appointment = BookAppointment::create($data);
            triggerNotification('bookings', 'appointment_booked', $appointment->id);


            $request->merge(['fee' => $data['fee']]);
            request()->session()->flash('alert', [
                'type' => 'info',
                'message' => 'Appointment Booked Successfully',
            ]);
            return redirect(route('patients.appointment_bank_transfers', ['appointment_id' => $appointment->id]));
        }
        if ($request->gateway == 'stripe') {
            $fund_request = PaymentController::addFundRequest($request);

            $data['fund_id'] = $fund_request['fund']['id'] ?? null;
            // dd($fund_request['fund']);

            $appointment = BookAppointment::create($data);
            triggerNotification('bookings', 'appointment_booked', $appointment->id);


            $request->merge(['fee' => $data['fee']]);
            request()->session()->flash('alert', [
                'type' => 'info',
                'message' => 'Appointment Booked Successfully',
            ]);
            return Inertia::location(route('patients.appointment_stripe_transfers', ['appointment_id' => $appointment->id]));
        }
        if ($request->gateway == 'wallet') {
            $wallet = new WalletController();
            $wallet_response = $wallet->payThroughUserWallet($request->amount);
            $wallet_response = $wallet_response->getData();
            if ($wallet_response->status) {
                $data['is_paid'] = 1;
                $appointment = BookAppointment::create($data);
                triggerNotification('bookings', 'appointment_booked', $appointment->id);

                request()->session()->flash('alert', [
                    'type' => 'info',
                    'message' => 'Appointment Booked Successfully',
                ]);
                return redirect()->back()->withResponseData([
                    'appointment' => $appointment,
                ]);
            } else {
                request()->session()->flash('alert', [
                    'type' => 'error',
                    'message' => $wallet_response->msg
                ]);
                return redirect()->back();
            }
        } else {
            $fund_request = PaymentController::addFundRequest($request);
            $data['fund_id'] = $fund_request['fund']['id'] ?? null;
            if ($fund_request['fund'] ?? false) {
                $data['is_paid'] = 0;
                $appointment = BookAppointment::create($data);
                triggerNotification('bookings', 'appointment_booked', $appointment->id);
                $request->merge(['fee' => $data['fee']]);
                request()->session()->flash('alert', [
                    'type' => 'info',
                    'message' => 'Appointment Booked Successfully',
                ]);
                return redirect()->back()->withResponseData([
                    'appointment' => $appointment,
                    'fund' => $fund_request['fund']
                ]);
            } else {
                request()->session()->flash('alert', [
                    'type' => 'error',
                    'message' => $fund_request,
                ]);
                return redirect()->back()->withErrors($fund_request);
            }
        }
    }
    public function getFilteredAppointmentlogs(Request $request)
    {
        $appointments = $this->getter($request);

        $response = generateResponse($appointments, count($appointments['data']) > 0 ? true : false, 'Filter Appointment Logs Successfully', null, 'collection');
        return response()->json($response, 200);
    }
    public function showAppointmentLogDetailPage(Request $request, $id)
    {
        $user = Auth()->user();
        $patient_id = $user->patient->id;
        $appointment = BookAppointment::withAll()->where('id', $id)->where('patient_id', $patient_id)->first();

        $appointment = new BookAppointmentsResource($appointment);
        $gateway = $appointment?->fund?->gateway;

        $data = [
            'appointment' => $appointment,
            'gateway' => $gateway,
        ];

        return Inertia::render('AppointmentLogDetail', $data);
    }
    public function getBankTransfers(Request $request)
    {
        $bank_accounts = BankAccount::active()->get();
        $appointment = BookAppointment::find($request->appointment_id);
        $fund = $appointment->fund;
        return Inertia::render('BankAccounts', [
            'appointment' => $appointment,
            'bank_accounts' => $bank_accounts,
            'fund' => $fund,
        ]);
    }





    public function fundBankTransfer(Request $request)
    {

        //     $rules = [
        //     'bank_account_id' => 'required',
        //     'attachment' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
        // ];

        // // Define custom error messages (optional)
        // $messages = [
        //     'bank_account_id.required' => 'The bank account field is required.',
        //     'attachment.required' => 'The attachment field is required.',
        // ];

        // $validator = Validator::make($request->all(), $rules, $messages);
        // if ($validator->fails()) {
        //     return redirect()->back()
        //         ->withErrors($validator)
        //         ->withInput();
        // }
        $data = $request->all();
        if ($request->hasFile('attachment')) {
            $data['attachment'] = uploadFile($request, 'attachment', 'bank_transactions');
        }

        // $data['attachment_url'] = uploadFile($request,'attachment_url','bank_transactions');
        $request->merge(['date' => now()]);

        FundBankTransfer::create($data);
        request()->session()->flash('alert', [
            'type' => 'info',
            'message' => 'Bank Transactions were created successfully',
        ]);

        if ($request->is_fund) {
            return redirect()->route('wallet')->with('message', 'Fund Bank Transfer Created Successfully')->with('message_type', 'success');
        }
        if ($request->is_service) {
            return redirect()->route('service_log')->with('message', 'Fund Bank Transfer Created Successfully')->with('message_type', 'success');
        }

        return redirect()->route('appointment_log')->with('message', 'Fund Bank Transfer Created Successfully')->with('message_type', 'success');
    }
}
