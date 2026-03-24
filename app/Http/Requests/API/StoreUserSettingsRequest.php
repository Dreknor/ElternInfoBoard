<?php

namespace App\Http\Requests\API;

use App\Services\UserAppSettingsService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'settings' => 'required|array',
            'settings.version' => 'required|string',

            // Theme
            'settings.theme' => 'required|array',
            'settings.theme.mode' => ['required', 'string', Rule::in(['light', 'dark', 'auto'])],
            'settings.theme.compact_view' => 'sometimes|boolean',

            // Navigation
            'settings.navigation' => 'required|array',
            'settings.navigation.footer_items' => 'required|array|min:1|max:5',
            'settings.navigation.footer_items.*.id' => [
                'required',
                'string',
                Rule::in(UserAppSettingsService::getValidFooterItemIds())
            ],
            'settings.navigation.footer_items.*.label' => 'required|string',
            'settings.navigation.footer_items.*.icon' => 'required|string',
            'settings.navigation.footer_items.*.route' => 'required|string',
            'settings.navigation.footer_items.*.order' => 'required|integer|min:1|max:5',
            'settings.navigation.footer_items.*.visible' => 'required|boolean',
            'settings.navigation.start_page' => 'required|string',

            // Dashboard
            'settings.dashboard' => 'required|array',
            'settings.dashboard.widgets' => 'required|array',
            'settings.dashboard.widgets.*.id' => [
                'required',
                'string',
                Rule::in(UserAppSettingsService::getValidWidgetIds())
            ],
            'settings.dashboard.widgets.*.label' => 'required|string',
            'settings.dashboard.widgets.*.visible' => 'required|boolean',
            'settings.dashboard.widgets.*.order' => 'required|integer|min:1',
            'settings.dashboard.quick_access' => 'required|array',
            'settings.dashboard.quick_access.*.id' => [
                'required',
                'string',
                Rule::in(UserAppSettingsService::getValidQuickAccessIds())
            ],
            'settings.dashboard.quick_access.*.label' => 'required|string',
            'settings.dashboard.quick_access.*.route' => 'required|string',
            'settings.dashboard.quick_access.*.visible' => 'required|boolean',
            'settings.dashboard.quick_access.*.order' => 'required|integer|min:1',
            'settings.dashboard.style' => ['required', 'string', Rule::in(['expanded', 'compact'])],

            // Modules
            'settings.modules' => 'required|array',
            'settings.modules.timetable' => 'sometimes|array',
            'settings.modules.timetable.default_view' => ['sometimes', 'string', Rule::in(['week', 'day'])],
            'settings.modules.timetable.highlight_substitutions' => 'sometimes|boolean',
            'settings.modules.messages' => 'sometimes|array',
            'settings.modules.messages.unread_first' => 'sometimes|boolean',
            'settings.modules.messages.push_notifications' => 'sometimes|boolean',
            'settings.modules.care' => 'sometimes|array',
            'settings.modules.care.default_tab' => ['sometimes', 'string', Rule::in(['children', 'checkin', 'schickzeiten'])],
            'settings.modules.care.show_sick_on_dashboard' => 'sometimes|boolean',
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'settings.required' => 'Settings are required',
            'settings.theme.mode.in' => 'The theme mode must be one of: light, dark, auto',
            'settings.navigation.footer_items.max' => 'Maximum 5 footer items allowed',
            'settings.navigation.footer_items.*.id.in' => 'Invalid footer item id',
            'settings.dashboard.widgets.*.id.in' => 'Invalid widget id',
            'settings.dashboard.quick_access.*.id.in' => 'Invalid quick access id',
            'settings.dashboard.style.in' => 'The style must be one of: expanded, compact',
        ];
    }
}


