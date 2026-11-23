<?php

namespace App\Http\Middleware;

use App\Models\Property;
use App\Models\Charge;
use App\Models\Expense;
use App\Models\MaintenanceTicket;
use App\Models\RecurringExpenseItem;
use App\Support\PropertyVisibility;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePropertyIsVisible
{
    public function __construct(private readonly PropertyVisibility $propertyVisibility)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $property = $request->route('property');

        if ($property instanceof Property) {
            abort_unless($this->propertyVisibility->canView($request->user(), $property), 403);
        }

        foreach (['charge', 'expense', 'maintenance', 'recurringExpenseItem'] as $parameter) {
            $model = $request->route($parameter);
            if ($model instanceof Charge || $model instanceof Expense || $model instanceof MaintenanceTicket || $model instanceof RecurringExpenseItem) {
                if ($model->property) {
                    abort_unless($this->propertyVisibility->canView($request->user(), $model->property), 403);
                }
            }
        }

        $propertyId = $request->input('property_id');
        if ($this->propertyVisibility->shouldLimitToOwnProperties($request->user()) && is_scalar($propertyId) && ctype_digit((string) $propertyId)) {
            $submittedProperty = Property::query()->find($propertyId);
            if ($submittedProperty) {
                abort_unless($this->propertyVisibility->canView($request->user(), $submittedProperty), 403);
            }
        }

        return $next($request);
    }
}
