<?php

namespace App\Http\Controllers;

use App\Models\ApplicationCandidate;
use App\Models\ApplicationForm;
use App\Models\ApplicationTransport;
use App\Models\User;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
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
            'user_applications'
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
        $pending_application = ApplicationCandidate::where([
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
        $pending_application = ApplicationCandidate::where([
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
            'candidates_count' => ['required', 'numeric', 'min:1'],
        ];

        $attributeNames = [
            'candidates_count' => trans('public.candidate_enrollment_count'),
        ];

        switch ($form_step) {
            case 1:
                Validator::make($request->all(), $rules)
                    ->setAttributeNames($attributeNames)
                    ->validate();
                return back();

            case 2:
                for ($index = 1; $index <= $request->candidates_count; $index++) {
                    $rules["candidate_details.$index.name"]  = ['required'];
                    $rules["candidate_details.$index.email"] = ['required', 'email'];
                    $rules["candidate_details.$index.gender"] = ['required'];
                    $rules["candidate_details.$index.country"] = ['required'];
                    $rules["candidate_details.$index.phone_number"] = ['required'];
                    $rules["candidate_details.$index.identity_number"] = ['required'];
                }

                // Set friendly attribute names for error messages.
                $customAttributeNames = [];
                for ($index = 1; $index <= $request->candidates_count; $index++) {
                    $customAttributeNames["candidate_details.$index.name"]  = trans('public.name');
                    $customAttributeNames["candidate_details.$index.email"] = trans('public.email');
                    $customAttributeNames["candidate_details.$index.gender"] = trans('public.gender');
                    $customAttributeNames["candidate_details.$index.country"] = trans('public.country');
                    $customAttributeNames["candidate_details.$index.phone_number"] = trans('public.mobile_phone');
                    $customAttributeNames["candidate_details.$index.identity_number"] = trans('public.ic_passport_number');
                }

                Validator::make($request->all(), $rules)
                    ->setAttributeNames($customAttributeNames)
                    ->validate();
                return back();

            case 3:
                $transportRules = [];
                $customAttributeNames = [];

                for ($i = 1; $i <= $request->candidates_count; $i++) {
                    if (!empty($request->candidate_details[$i]['requires_transport'])) {
                        $transportRules["candidate_details.$i.transport_details.name"] = ['required'];
                        $transportRules["candidate_details.$i.transport_details.gender"] = ['required'];
                        $transportRules["candidate_details.$i.transport_details.country"] = ['required'];
                        $transportRules["candidate_details.$i.transport_details.dob"] = ['required'];
                        $transportRules["candidate_details.$i.transport_details.phone_number"] = ['required'];
                        $transportRules["candidate_details.$i.transport_details.identity_number"] = ['required'];
                        $transportRules["candidate_details.$i.transport_details.departure_address"] = ['required'];
                        $transportRules["candidate_details.$i.transport_details.return_address"] = ['required'];

                        $customAttributeNames["candidate_details.$i.transport_details.name"] = trans('public.name_on_ic_passport');
                        $customAttributeNames["candidate_details.$i.transport_details.gender"] = trans('public.gender');
                        $customAttributeNames["candidate_details.$i.transport_details.country"] = trans('public.country');
                        $customAttributeNames["candidate_details.$i.transport_details.dob"] = trans('public.dob');
                        $customAttributeNames["candidate_details.$i.transport_details.phone_number"] = trans('public.phone_number');
                        $customAttributeNames["candidate_details.$i.transport_details.identity_number"] = trans('public.ic_passport_number');
                        $customAttributeNames["candidate_details.$i.transport_details.departure_address"] = trans('public.departure_address');
                        $customAttributeNames["candidate_details.$i.transport_details.return_address"] = trans('public.return_address');
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
            $candidate_details = $request->candidate_details;

            foreach ($candidate_details as $candidate) {
                $application_candidate = ApplicationCandidate::create([
                    'application_form_id' => $application_form->id,
                    'user_id' => Auth::id(),
                    'name' => $candidate['name'],
                    'gender' => $candidate['gender'],
                    'country_id' => $candidate['country']['id'],
                    'email' => $candidate['email'],
                    'phone_number' => $candidate['phone_number'],
                    'identity_number' => $candidate['identity_number'],
                    'requires_transport' => $candidate['requires_transport'],
                    'requires_accommodation' => $candidate['requires_accommodation'],
                    'requires_ib_training' => $candidate['requires_ib_training'],
                    'status' => 'pending',
                ]);

                if ($application_candidate->requires_transport) {
                    $transport = $candidate['transport_details'];

                    ApplicationTransport::create([
                        'application_form_id' => $application_form->id,
                        'application_candidate_id' => $application_candidate->id,
                        'user_id' => Auth::id(),
                        'name' => $transport['name'],
                        'gender' => $transport['gender'],
                        'country_id' => $transport['country']['id'],
                        'dob' => $transport['dob'],
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
}
