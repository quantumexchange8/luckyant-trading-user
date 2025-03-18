<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\ApplicationForm;
use App\Models\ApplicantTransport;
use App\Models\User;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ApplicationController extends Controller
{
    public function index()
    {
        return Inertia::render('Application/ApplicationListing', [
            'applicationsCount' => ApplicationForm::where('status', 'Active')->count()
        ]);
    }

    public function getApplicationForms(Request $request)
    {
        // fetch limit with default
        $limit = $request->input('limit', 6);

        // Fetch parameter from request
        $search = $request->input('search', '');
        $start_date = $request->input('start_date', '');
        $end_date = $request->input('end_date', '');

        $query = ApplicationForm::with([
            'leaders',
            'applicants'
        ]);

        if (!empty($search)) {
            $keyword = '%' . $search . '%';
            $query->where(function ($q) use ($keyword) {
                $q->where('subject', 'like', $keyword)
                    ->orWhere('details', 'like', $keyword);
            });
        }

        if (!empty($start_date) && !empty($end_date)) {
            $startDate = Carbon::parse($start_date)->addDay()->startOfDay();
            $endDate = Carbon::parse($end_date)->addDay()->endOfDay();

            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        // Get total count of masters
        $totalRecords = $query->count();

        // Fetch paginated results
        $applications = $query->latest()
            ->paginate($limit);

        return response()->json([
            'applications' => $applications,
            'totalRecords' => $totalRecords,
            'currentPage' => $applications->currentPage(),
        ]);
    }

    public function application_form($id)
    {
        $pending_application = Applicant::where([
            'user_id' => Auth::id(),
            'application_form_id' => $id,
            'status' => 'pending'
        ])
            ->get();

        if ($pending_application->isNotEmpty()) {
            return to_route('application')->with('toast', [
                'title' => trans("public.warning"),
                'message' => trans('public.toast_warning_pending_application'),
                'type' => 'warning',
            ]);
        }

        return Inertia::render('Application/ApplyForm', [
            'application' => ApplicationForm::find($id)
        ]);
    }

    public function submitApplicationForm(Request $request)
    {
        $pending_application = Applicant::where([
            'user_id' => Auth::id(),
            'application_form_id' => $request->application_form_id,
            'status' => 'pending'
        ])
            ->get();

        if ($pending_application->isNotEmpty()) {
            return to_route('application')->withErrors('count', 'count')->with('toast', [
                'title' => trans("public.warning"),
                'message' => trans('public.toast_warning_pending_application'),
                'type' => 'warning',
            ]);
        }

        $form_step = $request->step;

        $rules = [
            'application_form_id' => ['required'],
            'applicants_count' => ['required', 'numeric', 'min:1'],
        ];

        $attributeNames = [
            'applicants_count' => trans('public.applicant_enrollment_count'),
        ];

        switch ($form_step) {
            case 1:
                Validator::make($request->all(), $rules)
                    ->setAttributeNames($attributeNames)
                    ->validate();
                return back();

            case 2:
                for ($index = 1; $index <= $request->applicants_count; $index++) {
                    $rules["applicant_details.$index.name"]  = ['required', 'regex:/^[\p{L}\p{N}\p{M}. @]+$/u', 'max:255'];
                    $rules["applicant_details.$index.email"] = ['required', 'email'];
                    $rules["applicant_details.$index.gender"] = ['required'];
                    $rules["applicant_details.$index.country"] = ['required'];
                    $rules["applicant_details.$index.phone_number"] = ['required'];
                    $rules["applicant_details.$index.identity_number"] = ['required'];
                }

                // Set friendly attribute names for error messages.
                $customAttributeNames = [];
                for ($index = 1; $index <= $request->applicants_count; $index++) {
                    $customAttributeNames["applicant_details.$index.name"]  = trans('public.name');
                    $customAttributeNames["applicant_details.$index.email"] = trans('public.email');
                    $customAttributeNames["applicant_details.$index.gender"] = trans('public.gender');
                    $customAttributeNames["applicant_details.$index.country"] = trans('public.country');
                    $customAttributeNames["applicant_details.$index.phone_number"] = trans('public.mobile_phone');
                    $customAttributeNames["applicant_details.$index.identity_number"] = trans('public.ic_passport_number');
                }

                Validator::make($request->all(), $rules)
                    ->setAttributeNames($customAttributeNames)
                    ->validate();
                return back();

            case 3:
                $transportRules = [];
                $customAttributeNames = [];

                for ($i = 1; $i <= $request->applicants_count; $i++) {
                    if (!empty($request->applicant_details[$i]['requires_transport'])) {
                        $transportRules["applicant_details.$i.transport_details.name"] = ['required', 'regex:/^[\p{L}\p{N}\p{M}. @]+$/u', 'max:255'];
                        $transportRules["applicant_details.$i.transport_details.gender"] = ['required'];
                        $transportRules["applicant_details.$i.transport_details.country"] = ['required'];
                        $transportRules["applicant_details.$i.transport_details.dob.year"] = ['required'];
                        $transportRules["applicant_details.$i.transport_details.dob.month"] = ['required'];
                        $transportRules["applicant_details.$i.transport_details.dob.day"] = ['required'];
                        $transportRules["applicant_details.$i.transport_details.phone_number"] = ['required'];
                        $transportRules["applicant_details.$i.transport_details.identity_number"] = ['required'];
                        $transportRules["applicant_details.$i.transport_details.departure_address"] = ['required'];
                        $transportRules["applicant_details.$i.transport_details.return_address"] = ['required'];

                        $customAttributeNames["applicant_details.$i.transport_details.name"] = trans('public.name_on_ic_passport');
                        $customAttributeNames["applicant_details.$i.transport_details.gender"] = trans('public.gender');
                        $customAttributeNames["applicant_details.$i.transport_details.country"] = trans('public.country');
                        $customAttributeNames["applicant_details.$i.transport_details.dob.year"] = trans('public.year');
                        $customAttributeNames["applicant_details.$i.transport_details.dob.month"] = trans('public.month');
                        $customAttributeNames["applicant_details.$i.transport_details.dob.day"] = trans('public.day');
                        $customAttributeNames["applicant_details.$i.transport_details.phone_number"] = trans('public.mobile_phone');
                        $customAttributeNames["applicant_details.$i.transport_details.identity_number"] = trans('public.ic_passport_number');
                        $customAttributeNames["applicant_details.$i.transport_details.departure_address"] = trans('public.departure_address');
                        $customAttributeNames["applicant_details.$i.transport_details.return_address"] = trans('public.return_address');

                        // Get the DOB values from the request
                        $year = $request->applicant_details[$i]['transport_details']['dob']['year'] ?? null;
                        $month = $request->applicant_details[$i]['transport_details']['dob']['month'] ?? null;
                        $day = $request->applicant_details[$i]['transport_details']['dob']['day'] ?? null;

                        // Validate date if all components are present
                        if ($year && $month && $day) {
                            if (!checkdate($month, $day, $year)) {
                                throw ValidationException::withMessages([
                                    "applicant_details.$i.transport_details.dob.year" => trans('public.invalid_date')
                                ]);
                            }
                        }
                    }
                }

                $rules = array_merge($rules, $transportRules);

                Validator::make($request->all(), $rules)
                    ->setAttributeNames($customAttributeNames)
                    ->validate();

                return back();

            default:

                break;
        }

        $application_form = ApplicationForm::find($request->application_form_id);

        if ($application_form) {
            $applicants = $request->applicant_details;

            foreach ($applicants as $detail) {
                $applicant = Applicant::create([
                    'application_form_id' => $application_form->id,
                    'user_id' => Auth::id(),
                    'type' => 'flight',
                    'name' => $detail['name'],
                    'gender' => $detail['gender'],
                    'country_id' => $detail['country']['id'],
                    'email' => $detail['email'],
                    'phone_number' => $detail['phone_number'],
                    'identity_number' => $detail['identity_number'],
                    'requires_transport' => $detail['requires_transport'],
                    'requires_accommodation' => $detail['requires_accommodation'] ?? false,
                    'requires_ib_training' => $detail['requires_ib_training'] ?? false,
                    'status' => 'pending',
                ]);

                if ($applicant->requires_transport) {
                    $transport = $detail['transport_details'];

                    $dob = $transport['dob']['year'] . '-' . $transport['dob']['month'] . '-' . $transport['dob']['day'];

                    ApplicantTransport::create([
                        'application_form_id' => $application_form->id,
                        'applicant_id' => $applicant->id,
                        'user_id' => Auth::id(),
                        'type' => 'flight',
                        'name' => $transport['name'],
                        'gender' => $transport['gender'],
                        'country_id' => $transport['country']['id'],
                        'dob' => $dob,
                        'phone_number' => $transport['phone_number'],
                        'identity_number' => $transport['identity_number'],
                        'departure_address' => $transport['departure_address'],
                        'return_address' => $transport['return_address'],
                    ]);
                }
            }
        }

        return to_route('application')->with('toast', [
            'title' => trans("public.success"),
            'message' => trans('public.toast_success_submit_application'),
            'type' => 'success',
        ]);
    }

    public function getApplicants(Request $request)
    {
        $applicants = Applicant::with([
            'application_form',
            'country',
            'transport_detail',
            'transport_detail.country'
        ])
            ->where([
                'application_form_id' => $request->application_id,
                'user_id' => Auth::id(),
            ])
            ->get()
            ->toArray();

        return response()->json([
            'applicants' => $applicants,
        ]);
    }
}
