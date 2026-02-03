<?php
// app/Http/Controllers/CustomerController.php
namespace App\Http\Controllers;

use App\Http\Requests\CustomerStoreRequest;
use App\Http\Requests\CustomerUpdateRequest;
use App\Models\Customer;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class CustomerController extends Controller
{
    public function index(): Response|InertiaResponse
    {
        return Inertia::render('Customers/Index', [
            'customers' => Customer::query()
                ->latest('id')
                ->paginate(10)
                ->through(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'email' => $c->email,
                    'phone' => $c->phone,
                ]),
        ]);
    }

    public function create(): Response|InertiaResponse
    {
        return Inertia::render('Customers/Create');
    }

    public function store(CustomerStoreRequest $request): Response|InertiaResponse
    {
        Customer::create($request->validated());

        return redirect()->route('customers.index')
            ->with('success', 'Customer created.');
    }

    public function edit(Customer $customer): Response|InertiaResponse
    {
        return Inertia::render('Customers/Edit', [
            'customer' => $customer->only('id', 'name', 'email', 'phone', 'address'),
        ]);
    }

    public function update(CustomerUpdateRequest $request, Customer $customer): Response|InertiaResponse
    {
        $customer->update($request->validated());

        return redirect()->route('customers.index')
            ->with('success', 'Customer updated.');
    }

    public function destroy(Customer $customer): Response|InertiaResponse
    {
        $customer->delete();

        return redirect()->route('customers.index')
            ->with('success', 'Customer deleted.');
    }
}
