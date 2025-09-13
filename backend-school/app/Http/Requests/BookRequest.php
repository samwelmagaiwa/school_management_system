<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in controller
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $bookId = $this->route('book')?->id;
        
        return [
            'school_id' => 'required|exists:schools,id',
            'category_id' => 'nullable|exists:book_categories,id',
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'isbn' => [
                'nullable',
                'string',
                'max:20',
                'unique:books,isbn' . ($bookId ? ",{$bookId}" : '')
            ],
            'publisher' => 'nullable|string|max:255',
            'publication_year' => 'nullable|integer|min:1800|max:' . (date('Y') + 1),
            'total_copies' => 'required|integer|min:1|max:1000',
            'available_copies' => 'nullable|integer|min:0|lte:total_copies',
            'price' => 'nullable|numeric|min:0|max:99999.99',
            'description' => 'nullable|string|max:1000',
            'language' => 'nullable|string|max:50',
            'edition' => 'nullable|string|max:50',
            'pages' => 'nullable|integer|min:1|max:10000',
            'location' => 'nullable|string|max:100',
            'condition' => 'nullable|in:new,good,fair,poor',
            'cover_image' => 'nullable|string|max:500'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'school_id.required' => 'School is required',
            'school_id.exists' => 'Selected school does not exist',
            'category_id.exists' => 'Selected category does not exist',
            'title.required' => 'Book title is required',
            'title.max' => 'Book title cannot exceed 255 characters',
            'author.required' => 'Author name is required',
            'author.max' => 'Author name cannot exceed 255 characters',
            'isbn.unique' => 'This ISBN already exists',
            'isbn.max' => 'ISBN cannot exceed 20 characters',
            'publication_year.integer' => 'Publication year must be a valid year',
            'publication_year.min' => 'Publication year cannot be before 1800',
            'publication_year.max' => 'Publication year cannot be in the future',
            'total_copies.required' => 'Total copies is required',
            'total_copies.min' => 'Total copies must be at least 1',
            'total_copies.max' => 'Total copies cannot exceed 1000',
            'available_copies.min' => 'Available copies cannot be negative',
            'available_copies.lte' => 'Available copies cannot exceed total copies',
            'price.numeric' => 'Price must be a valid number',
            'price.min' => 'Price cannot be negative',
            'price.max' => 'Price cannot exceed 99,999.99',
            'pages.integer' => 'Pages must be a valid number',
            'pages.min' => 'Pages must be at least 1',
            'pages.max' => 'Pages cannot exceed 10,000',
            'condition.in' => 'Condition must be one of: new, good, fair, poor'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $data = [];
        
        // Set available_copies to total_copies if not provided (for new books)
        if (!$this->has('available_copies') && $this->has('total_copies')) {
            $data['available_copies'] = $this->total_copies;
        }
        
        // Ensure numeric fields are properly cast
        if ($this->has('total_copies')) {
            $data['total_copies'] = (int) $this->total_copies;
        }
        
        if ($this->has('available_copies')) {
            $data['available_copies'] = (int) $this->available_copies;
        }
        
        if ($this->has('publication_year')) {
            $data['publication_year'] = (int) $this->publication_year;
        }
        
        if ($this->has('pages')) {
            $data['pages'] = (int) $this->pages;
        }
        
        if ($this->has('price')) {
            $data['price'] = (float) $this->price;
        }
        
        if (!empty($data)) {
            $this->merge($data);
        }
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'school_id' => 'school',
            'category_id' => 'category',
            'total_copies' => 'total copies',
            'available_copies' => 'available copies',
            'publication_year' => 'publication year',
            'cover_image' => 'cover image'
        ];
    }
}