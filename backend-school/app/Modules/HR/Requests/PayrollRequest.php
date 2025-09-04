<?php

namespace App\Modules\HR\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Modules\HR\Models\Payroll;

class PayrollRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Add authorization logic as needed
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'school_id' => 'required|exists:schools,id',
            'pay_period_start' => 'required|date',
            'pay_period_end' => 'required|date|after:pay_period_start',
            'pay_date' => 'nullable|date|after_or_equal:pay_period_end',
            'basic_salary' => 'required|numeric|min:0',
            'hours_worked' => 'nullable|numeric|min:0|max:744', // Max hours in a month
            'overtime_hours' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|in:' . implode(',', [
                Payroll::PAYMENT_BANK_TRANSFER,
                Payroll::PAYMENT_CASH,
                Payroll::PAYMENT_CHEQUE
            ]),
            'payment_reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'items' => 'nullable|array',
            'items.*.type' => 'required|in:earning,deduction',
            'items.*.name' => 'required|string|max:255',
            'items.*.code' => 'nullable|string|max:50',
            'items.*.amount' => 'required|numeric',
            'items.*.quantity' => 'nullable|numeric|min:0',
            'items.*.rate' => 'nullable|numeric|min:0',
            'items.*.is_taxable' => 'boolean',
            'items.*.description' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'employee_id.required' => 'Employee is required',
            'employee_id.exists' => 'Selected employee does not exist',
            'school_id.required' => 'School is required',
            'school_id.exists' => 'Selected school does not exist',
            'pay_period_start.required' => 'Pay period start date is required',
            'pay_period_end.required' => 'Pay period end date is required',
            'pay_period_end.after' => 'Pay period end date must be after start date',
            'pay_date.after_or_equal' => 'Pay date must be on or after pay period end date',
            'basic_salary.required' => 'Basic salary is required',
            'basic_salary.min' => 'Basic salary cannot be negative',
            'hours_worked.max' => 'Hours worked cannot exceed 744 hours per month',
            'overtime_hours.min' => 'Overtime hours cannot be negative',
            'payment_method.in' => 'Invalid payment method',
            'items.*.type.required' => 'Item type is required',
            'items.*.type.in' => 'Item type must be earning or deduction',
            'items.*.name.required' => 'Item name is required',
            'items.*.amount.required' => 'Item amount is required',
            'items.*.amount.numeric' => 'Item amount must be a number',
            'items.*.quantity.min' => 'Item quantity cannot be negative',
            'items.*.rate.min' => 'Item rate cannot be negative',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default payment method
        if (!$this->payment_method) {
            $this->merge(['payment_method' => Payroll::PAYMENT_BANK_TRANSFER]);
        }

        // Calculate gross salary, deductions, and net salary if items are provided
        if ($this->items) {
            $totalEarnings = $this->basic_salary;
            $totalDeductions = 0;

            foreach ($this->items as $item) {
                if ($item['type'] === 'earning') {
                    $totalEarnings += $item['amount'];
                } else {
                    $totalDeductions += $item['amount'];
                }
            }

            $this->merge([
                'gross_salary' => $totalEarnings,
                'total_deductions' => $totalDeductions,
                'net_salary' => $totalEarnings - $totalDeductions,
            ]);
        } else {
            $this->merge([
                'gross_salary' => $this->basic_salary,
                'total_deductions' => 0,
                'net_salary' => $this->basic_salary,
            ]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Check for duplicate payroll in the same period
            if ($this->employee_id && $this->pay_period_start && $this->pay_period_end) {
                $existingPayroll = \App\Modules\HR\Models\Payroll::where('employee_id', $this->employee_id)
                    ->where(function ($query) {
                        $query->whereBetween('pay_period_start', [$this->pay_period_start, $this->pay_period_end])
                              ->orWhereBetween('pay_period_end', [$this->pay_period_start, $this->pay_period_end])
                              ->orWhere(function ($q) {
                                  $q->where('pay_period_start', '<=', $this->pay_period_start)
                                    ->where('pay_period_end', '>=', $this->pay_period_end);
                              });
                    });

                // Exclude current payroll if updating
                if ($this->route('payroll')) {
                    $existingPayroll->where('id', '!=', $this->route('payroll')->id);
                }

                if ($existingPayroll->exists()) {
                    $validator->errors()->add('pay_period_start', 'Payroll already exists for this period.');
                }
            }

            // Validate that net salary is not negative
            if (isset($this->net_salary) && $this->net_salary < 0) {
                $validator->errors()->add('items', 'Total deductions cannot exceed gross salary.');
            }

            // Validate payment reference for certain payment methods
            if (in_array($this->payment_method, [Payroll::PAYMENT_BANK_TRANSFER, Payroll::PAYMENT_CHEQUE])) {
                if (!$this->payment_reference) {
                    $validator->errors()->add('payment_reference', 'Payment reference is required for this payment method.');
                }
            }
        });
    }
}